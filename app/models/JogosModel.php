<?php

namespace app\models;

use app\core\crud\Conn;
use app\core\Model;
use app\helpers\BuildQuery;
use app\helpers\Date;
use app\helpers\Pagination;
use app\vo\ApostaJogoVO;
use app\vo\ApostaVO;
use app\vo\JogoVO;
use Exception;

class JogosModel extends Model
{

    const TEMPOS = [
        ['key' => '90', 'title' => 'Resultado final'],
        ['key' => 'pt', 'title' => 'Primeiro tempo'],
        ['key' => 'st', 'title' => 'Segundo tempo'],
    ];

    public function __construct()
    {
        $this->table = 'sis_jogos';
        $this->valueObject = JogoVO::class;
        $this->query = (new BuildQuery($this->table, 'a'))
            ->addInnerJoin(CampeonatosModel::getTable(), 'campeonato', 'campeonato.id = a.campeonato')
            ->addInnerJoin(TimesModel::getTable(), 'casa', 'casa.id = a.timecasa')
            ->addInnerJoin(TimesModel::getTable(), 'fora', 'fora.id = a.timefora');
    }

    /**
     * Efetua uma busca complexa a partir dos parâmetros passados
     * @param array $parans
     * @param int $page
     * @param int $forPage
     * @param string $order
     * @return Pagination
     */
    public static function busca(array $parans = null, $page = 1, $forPage = 10, $order = 'default')
    {
        $termos = 'WHERE a.status != 99';
        $places = [];

        # Order By
        switch ($order) {
            case 'apostas':
                $orderBy = 'a.apostas DESC';
                break;
            default:
                $orderBy = 'a.data ASC, a.hora ASC';
        }

        if ($parans) {
            foreach ($parans as $key => $value) {
                if (!isEmpty($value) and !empty($key)) {
                    switch ($key) {
                        case 'disponivel':
                            $termos .= " AND (a.data > :hoje OR (a.data = :hoje AND a.hora >= :hora)) AND a.status = 1 ";
                            $places['hoje'] = date('Y-m-d');
                            $places['hora'] = date('H:i:s');
                            break;
                        case 'datacadastro':
                        case 'data':
                            if ($value = Date::data($value)) {
                                $termos .= " AND a.{$key} = :{$key}";
                                $places[$key] = $value;
                            }
                            break;
                        case 'dataInicial':
                        case 'dataFinal':
                            if ($data = Date::data($value)) {
                                $termos .= " AND a.data " . ($key == 'dataFinal' ? '<' : '>') . "= :{$key}";
                                $places[$key] = $data;
                            }
                            break;
                        case 'status':
                        case 'id':
                        case 'timecasa':
                        case 'timefora':
                        case 'campeonato':
                        case 'ref':
                            $termos .= " AND a.{$key} = :{$key}";
                            $places[$key] = $value;
                            break;
                        case 'search':
                            $l = "LIKE CONCAT('%',:{$key},'%')";
                            $termos .= " AND (casa.title {$l} OR fora.title {$l})";
                            $places[$key] = $value;
                            break;
                    }
                }
            }
        }
        return self::listaPagination("{$termos} ORDER BY {$orderBy}", $places, $page, $forPage);
    }

    /**
     * @param JogoVO $jogo
     * @param bool $count
     * @return ApostaVO[]|int
     */
    public static function getApostas(JogoVO $jogo, bool $count = false)
    {
        $query = clone ApostasModel::getBuildQuery();
        $query->addInnerJoin(ApostaJogosModel::getTable(), 'jogo', 'jogo.aposta = a.id AND jogo.jogo = :jogo');
        $query->setWhere('a.status IN(1,2)');
        return ApostasModel::lista($query, [
            'jogo' => $jogo->getId(),
        ], $count);
    }

    /**
     * Executa o cancelamento do jogo e das apostas associadas a ele
     * @param JogoVO $jogo
     * @throws Exception
     */
    public static function cancelar(JogoVO $jogo)
    {
        if ($jogo->getStatus() != 1) {
            throw new Exception("O jogo não pode mais ser excluído.");
        }

        $tbJogos = self::getTable();
        $tbApostaJogos = ApostaJogosModel::getTable();
        $tbApostas = ApostasModel::getTable();

        $termos = <<<SQL
        
UPDATE 
    `{$tbJogos}` AS partida, 
    `{$tbApostaJogos}` AS jogo,
    `{$tbApostas}` AS aposta

SET 
    -- Partida
    partida.status = 0,
    
    -- Jogo
    jogo.status = 0,

    -- Aposta
    aposta.retorno = aposta.cotacao / jogo.cotacaovalor * aposta.valor,
    aposta.retornovalido = LEAST(aposta.cotacao / jogo.cotacaovalor * aposta.valor, aposta.retornomaximo),
    aposta.cotacao = aposta.cotacao / jogo.cotacaovalor,
    aposta.status = IF(aposta.jogos = 1, 0, aposta.status)
   
WHERE

    -- Partida
    partida.id = :partida

    -- Jogo
    AND jogo.status = 1 AND jogo.jogo = partida.id
    
    -- Aposta
    AND aposta.id = jogo.aposta;
    
SQL;

        $prepare = Conn::getConn()->prepare($termos);
        $prepare->execute(['partida' => $jogo->getId()]);

        // Validando apostas que continham esse jogo
        ApostasModel::validarApostas($jogo);

        if (!$prepare->rowCount()) {

            $jogo->setStatus(0);
            $jogo->save();

        } else {

            $termos = clone ApostasModel::getBuildQuery();
            $termos->addInnerJoin(ApostaJogosModel::getTable(), 'jogo', 'jogo.aposta = a.id AND jogo.jogo = :partida AND jogo.status = 0');
            $termos->setWhere('a.status = 0 AND a.jogos = 0');

            /** @var ApostaVO $aposta */
            foreach (ApostasModel::lista($termos, ['partida' => $jogo->getId()]) as $aposta) {
                HistoricoBancarioModel::add($aposta->voUser(), $aposta->getValor(), "Extorno da aposta #{$aposta->getId()} pelo cancelamento do jogo `{$jogo->getDescricao()}`", $jogo, 'extorno');
            }
        }

    }

    /**
     * Lista de jogos disponíveis
     * @return JogoVO[]
     */
    public static function getDisponiveis()
    {

        $termos = <<<SQL
WHERE a.status = 1 AND (a.data > :hoje OR (a.data = :hoje AND a.hora > :hora)) 
ORDER BY a.data ASC, campeonato.title ASC, a.hora ASC 
SQL;

        $places = [
            'hoje' => date('Y-m-d'),
            'hora' => date('H:i:s'),
        ];

        return self::lista($termos, $places, false, true, true);
    }

    /**
     * Incrementa aposta no jogo
     * @param ApostaJogoVO $jogo
     * @return boolean
     */
    public static function incAposta(ApostaJogoVO $jogo)
    {
        $tbJogos = self::getTable();
        $termos = <<<SQL
UPDATE `{$tbJogos}` AS a
SET a.apostas = a.apostas + 1, a.valorapostas = a.valorapostas + :valor 
WHERE a.id = :id LIMIT 1;
SQL;
        $prepare = Conn::getConn()->prepare($termos);
        $prepare->execute(['id' => $jogo->getJogo(), 'valor' => $jogo->getValor()]);
        return $prepare->rowCount() ? true : false;
    }

    /**
     * Retorna lista de jogos abertos
     * @param int $limite
     * @return JogoVO[]
     */
    public static function getJogosDia($limite = 15)
    {
        $termos = "WHERE a.status = 1 AND (a.data = :data AND a.hora > :hora OR a.data > :data) ORDER BY a.data ASC, a.hora ASC LIMIT :limit";
        $places = ['data' => date('Y-m-d'), 'hora' => date('H:i:s'), 'limit' => (int)$limite];
        return self::lista($termos, $places);
    }

    /**
     * Redefini o placar
     * @param JogoVO $jogo
     * @param bool $return
     * @return null|array
     */
    public static function redefinirPlacar(JogoVO $jogo, bool $return = true)
    {
        if ($jogo->getStatus() == 2) {
            self::defazerResultadosApostas($jogo);
        }
        return self::definePlacar($jogo, $return);
    }

    /**
     * Zera o placar de um jogo já encerrado
     * @param JogoVO $jogo
     */
    public static function defazerResultadosApostas(JogoVO $jogo)
    {
        if ($jogo->getStatus() != 2) {
            throw new Exception('O jogo não está encerrado');
        } else if ($jogo->getDataBaixa()) {
            throw new Exception('Jogo já teve baixa em ' . str_replace(' ', ' às ', $jogo->getDataBaixa(true)));
        } else {

            $tbApostas = ApostasModel::getTable();
            $tbApostaJogos = ApostaJogosModel::getTable();

            self::verificaApostasJogos($jogo);

            $query = <<<SQL
UPDATE 
    `{$tbApostas}` AS aposta 

INNER JOIN 
    `{$tbApostaJogos}` AS apostaJogo 
        ON apostaJogo.aposta = aposta.id AND apostaJogo.verificado = 1 AND apostaJogo.status = 1

SET
    aposta.acertos = IF(apostaJogo.acertou = 1, aposta.acertos - 1, aposta.acertos),
    aposta.erros = IF(apostaJogo.acertou = 0, aposta.erros - 1, aposta.erros),
    aposta.jogosverificados = aposta.jogosverificados - 1,
    aposta.ganhou = 0,
    aposta.verificado = 0,
    aposta.status = 1,
    apostaJogo.verificado = 0,
    apostaJogo.acertou = 0
    
WHERE 
    apostaJogo.jogo = :jogo; 
SQL;

            $prepare = Conn::getConn()->prepare($query);
            $prepare->execute(['jogo' => $jogo->getId()]);

            $jogo->setStatus(1);
            $jogo->Save();
        }
    }

    /**
     * Busca e Verifica apostas do jogo
     * @param JogoVO $jogo
     * @param bool $return
     * @return null|array
     */
    public static function verificaApostasJogos(JogoVO $jogo, bool $return = false)
    {

        $tbApostas = ApostasModel::getTable();
        $tbApostasJogos = ApostaJogosModel::getTable();

        self::_verificaJogos($jogo);
        ApostasModel::validarApostas($jogo);

        if ($return) {

            $termos = <<<SQL
SELECT 
GROUP_CONCAT(aposta.id) AS apostas,
SUM(1) AS jogos, 
SUM(IF(jogo.acertou = 1, 1, 0)) AS acertos,
SUM(IF(jogo.acertou = 0, 1, 0)) AS erros,
SUM(IF(aposta.erros > 0, 1, 0)) AS perdidas,
SUM(IF(aposta.verificado = 1 AND aposta.ganhou = 1, 1, 0)) AS ganhas,
SUM(IF(aposta.verificado = 0 AND aposta.erros = 0 AND aposta.acertos > 0, 1, 0)) AS possiveis
FROM `{$tbApostas}` AS `aposta`, `{$tbApostasJogos}` AS `jogo`
WHERE jogo.jogo = :jogo AND jogo.status = 1 AND jogo.aposta = aposta.id AND aposta.status = 1;
SQL;


            # Buscando os resultados
            return current(self::pdoRead()->FullRead($termos, ['jogo' => $jogo->getId()])->getResult());

        }
    }

    /**
     * Verifica os jogos
     * @param JogoVO $jogo
     */
    private static function _verificaJogos(JogoVO $jogo)
    {

        $cotacoes = CotacoesModel::getCotacoesAtivas();
        $tbApostas = ApostasModel::getTable();
        $tbApostasJogos = ApostaJogosModel::getTable();

        $tbJogos = self::getTable();

        $places = ['jogo' => $jogo->getId()];

        foreach (['90' => '', 'pt' => 'primeiro', 'st' => 'segundo'] as $tempo => $replace) {

            $query = <<<SQL
UPDATE `{$tbApostasJogos}` AS apostaJogo
INNER JOIN `{$tbJogos}` AS jogo ON jogo.id = apostaJogo.jogo 
SET apostaJogo.verificado = 1, apostaJogo.acertou = CASE apostaJogo.cotacaocampo 
SQL;

            # Cotações
            foreach ($cotacoes as $cotacao) {

                $key = 'cotacao' . $cotacao->getId();
                $places[$key] = $cotacao->getCampo();

                $condicao = $cotacao->getQuery();

                $condicao = str_replace([
                    '{timecasaplacar}',
                    '{timeforaplacar}',
                    '{ganhador}',
                    '{totalgols}',
                ], [
                    'timecasaplacar' . $replace,
                    'timeforaplacar' . $replace,
                    'ganhador' . $replace,
                    'totalgols' . $replace,
                ], $condicao);

                $query .= <<<SQL
\nWHEN :{$key} THEN IF({$condicao}, 1, 0)  
SQL;
            }
            $query .= "\nELSE 0 END ";

            # Condições Gerais
            $query .= <<<SQL
\nWHERE apostaJogo.jogo = :jogo AND apostaJogo.tempo = :tempo;
SQL;

            # Executando verificações das apostas
            $prepare = Conn::getConn()->prepare($query);
            $prepare->execute(['tempo' => $tempo] + $places);

        }
    }

    /**
     * Define o placar do jogo
     * @param JogoVO $jogo
     * @return null|array
     * @throws Exception
     */
    public static function definePlacar(JogoVO $jogo, bool $return = true)
    {

        # Já foi alterado ou excluído
        if ($jogo->getStatus() != 1) {
            throw new Exception("Não é possível alterar o placar do jogo.");
        }

        $jogo->save(['status' => 2], false);

        return self::verificaApostasJogos($jogo, $return);

    }

    /**
     * @return array
     */
    public static function getResumoJogos()
    {

        $tb = self::getTable();

        $termos = <<<SQL
SELECT 

    COUNT(DISTINCT a.id) AS total,
    SUM(IF(a.data = :hoje, 1, 0)) AS hoje,
    SUM(IF(a.data BETWEEN :amanha AND :3dias, 1, 0)) AS 3dias

FROM `{$tb}` AS a

WHERE a.status = 1 AND (a.data > :hoje OR a.data = :hoje AND a.hora > :hora) ;
SQL;

        $places = [
            'hoje' => date('Y-m-d'),
            'amanha' => date('Y-m-d', strtotime('+1day')),
            'hora' => date('H:i:s'),
            '3dias' => date('Y-m-d', strtotime('+3days')),
        ];

        return current(self::pdoRead()->FullRead($termos, $places)->getResult());
    }

    /**
     * @param JogoVO $jogo
     * @return array
     */
    public static function getTotais(JogoVO $jogo)
    {

        $tbApostas = ApostasModel::getTable();
        $tbApostasJogos = ApostaJogosModel::getTable();

        $termos = <<<SQL
SELECT
    apostaJogo.jogo,
    COUNT(DISTINCT apostaJogo.id) AS apostas,
    SUM(aposta.valor) AS total
     
FROM 
    `{$tbApostas}` AS aposta

INNER JOIN  
    `{$tbApostasJogos}` AS apostaJogo
        ON apostaJogo.aposta = aposta.id AND apostaJogo.status = 1 AND apostaJogo.jogo = :jogo

WHERE
    aposta.status = 1

GROUP BY 
    apostaJogo.jogo
SQL;

        return current(self::pdoRead()->FullRead($termos, ['jogo' => $jogo->getId()])->getResult()) ?: [
            'jogo' => $jogo->getId(),
            'apostas' => 0,
            'total' => 0,
        ];

    }

    /**
     * Retorna os jogos esportivos em destaque para o hero showcase da home
     *
     * @param int $limit
     * @return array
     */
    public static function getJogosDestaqueHome(int $limit = 6): array
    {
        $db = Conn::getConn();
        $sql = "SELECT a.id, a.campeonato AS campeonatoId, d.title AS campeonato, d.pais AS paisId,
                       b.id AS timecasaId, b.title AS casa,
                       c.id AS timeforaId, c.title AS fora,
                       a.data, a.hora, a.cotacoes
                FROM sis_jogos a
                INNER JOIN sis_times b ON b.id = a.timecasa AND b.status = 1
                INNER JOIN sis_times c ON c.id = a.timefora AND c.status = 1
                INNER JOIN sis_campeonatos d ON d.id = a.campeonato AND d.status = 1
                WHERE a.status = 1
                  AND TIMESTAMP(a.data, a.hora) > DATE_ADD(NOW(), INTERVAL 10 MINUTE)
                ORDER BY (CASE WHEN d.pais = 12 OR d.title LIKE '%Brasil%' THEN 0 ELSE 1 END),
                         a.data ASC, a.hora ASC
                LIMIT {$limit}";

        try {
            $stmt = $db->query($sql);
            $games = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $t) {
            $games = [];
        }

        $result = [];
        foreach ($games as $g) {
            $cotacoes = !empty($g['cotacoes']) ? json_decode($g['cotacoes'], true) : [];
            $odd90 = $cotacoes['90'] ?? [];

            $imgCasa = \app\helpers\TeamBadgeHelper::getTeamBadge($g['casa'], (int)$g['timecasaId'], (int)($g['paisId'] ?? 0));
            $imgFora = \app\helpers\TeamBadgeHelper::getTeamBadge($g['fora'], (int)$g['timeforaId'], (int)($g['paisId'] ?? 0));

            $dtIso = $g['data'] . 'T' . $g['hora'] . date('P');

            $result[] = [
                'id' => (int)$g['id'],
                'campeonatoId' => (int)$g['campeonatoId'],
                'campeonato' => $g['campeonato'],
                'timecasaId' => (int)$g['timecasaId'],
                'timeforaId' => (int)$g['timeforaId'],
                'casa' => $g['casa'],
                'fora' => $g['fora'],
                'data' => $g['data'],
                'hora' => $g['hora'],
                'dateTime' => $dtIso,
                'imgCasa' => $imgCasa,
                'imgFora' => $imgFora,
                'paisImg' => \app\helpers\TeamBadgeHelper::getCountryFlag((int)($g['paisId'] ?? 0)),
                'cotacoes' => $cotacoes,
                'oddCasa' => isset($odd90['casa']) && (float)$odd90['casa'] > 1 ? number_format((float)$odd90['casa'], 2, ',', '.') : '-',
                'oddEmpate' => isset($odd90['empate']) && (float)$odd90['empate'] > 1 ? number_format((float)$odd90['empate'], 2, ',', '.') : '-',
                'oddFora' => isset($odd90['fora']) && (float)$odd90['fora'] > 1 ? number_format((float)$odd90['fora'], 2, ',', '.') : '-',
            ];
        }

        return $result;
    }

}
