<?php

namespace app\models;

use app\core\crud\Conn;
use app\core\Model;
use app\helpers\BuildQuery;
use app\helpers\Date;
use app\helpers\Number;
use app\helpers\Pagination;
use app\helpers\SMail;
use app\helpers\Utils;
use app\traits\core\view;
use app\vo\ApostaJogoVO;
use app\vo\ApostaVO;
use app\vo\IndicacaoVO;
use app\vo\JogoVO;
use app\vo\UserVO;

class ApostasModel extends Model
{

    use view;

    const STATUS_ATIVA = 1;
    const STATUS_CANCELADA = 0;
    const STATUS_AGUARDANDO_PAGAMENTO = 2;
    const STATUS_NPAGA = 3;
    const STATUS_EXCLUIDA = 99;

    public function __construct()
    {
        $this->table = 'sis_apostas';
        $this->valueObject = ApostaVO::class;
        $this->query = (new BuildQuery($this->table, 'a'))
            ->addLeftJoin(UsersModel::getTable(), 'user', 'user.id = a.user');
    }

    /**
     * Efetua uma busca complexa a partir dos parâmetros passados
     * @param array $parans
     * @param int $page
     * @param int $forpage
     * @param string $OrderBy
     * @return Pagination
     */
    static function busca(array $parans = null, $page = 1, $forpage = 10)
    {
        $termos = 'WHERE a.status != 99';

        $places = [];

        $orderby = 'a.insert DESC';

        if ($parans) {
            foreach ($parans as $key => $value) {
                if (!isEmpty($value) and !empty($key)) {
                    switch ($key) {
                        case 'situacao':
                            switch ($value) {
                                case 'ganhou':
                                    $termos .= " AND a.ganhou = 1 AND a.status = 1 AND a.user > 0";
                                    break;
                                case 'perdeu':
                                    $termos .= " AND a.erros > 0 AND a.status = 1";
                                    break;
                                case 'aguardando':
                                    $termos .= " AND a.erros = 0 AND a.verificado = 0 AND a.status = 1";
                                    break;
                                case 'cancelada':
                                    $termos .= " AND a.status = 0";
                                    break;
                                case 'possivel':
                                    $termos .= " AND a.verificado = 0 AND a.erros = 0 AND a.acertos > 0 AND a.status = 1 AND a.user > 0";
                                    break;
                            }
                            break;
                        case 'gerente':
                            $termos .= " AND (user.id = :{$key} OR user.user = :{$key})";
                            $places[$key] = $value;
                            break;
                        case 'ganhou':
                            $termos .= " AND a.ganhou = 1";
                            break;
                        case 'valorMinimo':
                        case 'valorMaximo':
                        case 'retornoMinimo':
                        case 'retornoMaximo':
                            $sinal = strpos($key, 'Minimo') !== false ? '>=' : '<=';
                            $field = strpos($key, 'retorno') !== false ? 'retornovalido' : 'valor';
                            $termos .= " AND a.{$field} {$sinal} :{$key}";
                            $places[$key] = Number::float($value, 2);
                            break;
                        case 'dataInicial':
                        case 'dataFinal':
                            if ($data = Date::data($value)) {
                                $termos .= " AND a.data " . ($key == 'dataFinal' ? '<' : '>') . "= :{$key}";
                                $places[$key] = $data;
                            }
                            break;
                        case 'apostadornome':
                            $l = "LIKE CONCAT('%',:{$key},'%')";
                            $termos .= " AND a.{$key} {$l}";
                            $places[$key] = $value;
                            break;
                        case 'search':
                            $l = "LIKE CONCAT('%', :{$key}, '%')";
                            $termos .= " AND (user.nome {$l} OR user.email {$l} OR user.login = :{$key} OR user.cidadetitle {$l})";
                            $places[$key] = $value;
                            break;
                        case 'pago':
                        case 'user':
                        case 'casadinha':
                        case 'jogos':
                        case 'verificado':
                        case 'id':
                        case 'status':
                        case 'ref':
                            $termos .= " AND a.{$key} = :{$key}";
                            $places[$key] = $value;
                            break;
                    }
                }
            }
        }

        return self::listaPagination("{$termos} ORDER BY {$orderby}", $places, $page, $forpage);
    }

    /**
     * @param ApostaVO $aposta
     * @throws \Exception
     */
    public static function pagarComissoes(ApostaVO $aposta)
    {

        $user = $aposta->voUser();

        if ($user) {
            $graduacao = $user->voGraduacao();
            if ($graduacao) {

                $jogos = min(4, max(1, $aposta->getJogos()));
                $comissao = $graduacao->get('jogos' . $jogos);

                while (!$comissao and $jogos > 1) {
                    $jogos--;
                    $comissao = $graduacao->get('jogos' . $jogos);
                }

                if ($comissao > 0) {

                    $comissaoValor = $aposta->getValor() * $comissao / 100;
                    $aposta->setComissao($comissaoValor);
                    HistoricoBancarioModel::add($user, $comissaoValor, "Comissão de `{$graduacao->getTitle()}` referente a aposta #{$aposta->getId()}", $aposta, 'comissao');

                    if ($gerente = $user->voUser() and $gerente->getComissao() > 0) {
                        $comissaoValor = $comissaoValor * $gerente->getComissao() / 100;
                        $aposta->setComissaoGerente($comissaoValor);
                        $aposta->setGerente($gerente->getId());
                        HistoricoBancarioModel::add($gerente, $comissaoValor, "Comissão de gerente referente a aposta #{$aposta->getId()}", $aposta, 'comissao');
                    }

                    $aposta->save();

                }
            }
        }
    }

    /**
     * Cancela o jogo da aposta
     * @param ApostaJogoVO $jogo
     * @throws \Exception
     */
    public static function cancelarJogo(ApostaJogoVO $jogo)
    {

        $aposta = $jogo->voAposta();
        $partida = $jogo->voJogo();

        if (!$aposta) {
            throw new \Exception("Aposta inválida");
        } else if (!$partida) {
            throw new \Exception("Partida inválida");
        } else if ($aposta->getJogos() == 1) {
            throw new \Exception("Não é possível cancelar o jogo de uma aposta com um uníco jogo");
        } else if (!$jogo->getIsEditavel()) {
            throw new \Exception("O jogo `{$partida->getDescricao()}` não pode mais ser cancelado da aposta.");
        }

        $aposta->setCotacao($aposta->getCotacao() / $jogo->getCotacaoValor());
        $aposta->setRetornoMaximo(min($aposta->getRetorno() * 0.9, $aposta->getRetornoMaximo() * 0.9));

        $jogoId = (int)$jogo->getId();
        $apostaId = (int)$aposta->getId();
        $cotacao = (float)$aposta->getCotacao();
        $retorno = (float)$aposta->getRetorno();
        $retornoValido = (float)$aposta->getRetornoValido();
        $retornoMaximo = (float)$aposta->getRetornoMaximo();

        $conn = Conn::getConn();
        
        // Removendo o jogo
        $stmt1 = $conn->prepare("UPDATE `{$jogo->getTable()}` AS a SET a.status = 99 WHERE a.id = :id AND a.status = 1 AND a.verificado = 0 LIMIT 1");
        $stmt1->execute([':id' => $jogoId]);

        // Atualizando quantidade de jogos da aposta
        $stmt2 = $conn->prepare("UPDATE `{$aposta->getTable()}` AS a SET a.jogos = a.jogos - 1 WHERE a.id = :id LIMIT 1");
        $stmt2->execute([':id' => $apostaId]);

        // Verificando se a aposta foi verificada e atualizando a cotação
        $stmt3 = $conn->prepare("UPDATE `{$aposta->getTable()}` AS a SET a.cotacao = :cotacao, a.verificado = IF(a.jogos = a.jogosverificados, 1, 0), a.retorno = :retorno, a.retornovalido = :retornovalido, a.retornomaximo = :retornomaximo WHERE a.id = :id LIMIT 1");
        $stmt3->execute([
            ':cotacao' => $cotacao,
            ':retorno' => $retorno,
            ':retornovalido' => $retornoValido,
            ':retornomaximo' => $retornoMaximo,
            ':id' => $apostaId
        ]);

        // Verificando se a aposta está concluída
        $stmt4 = $conn->prepare("UPDATE `{$aposta->getTable()}` AS a SET a.ganhou = IF(a.acertos = a.jogos, 1, 0) WHERE a.id = :id LIMIT 1");
        $stmt4->execute([':id' => $apostaId]);
    }

    /**
     * @return string
     */
    public static function gerarCodigo()
    {
        $codigo = '';
        $tentativas = 10;

        while (!$codigo or self::lista('WHERE a.codigobilhete = :codigo LIMIT 1', ['codigo' => $codigo], true)) {
            $codigo = Utils::gerarCodigo(3 * 3, false, false, true);
            $tentativas--;
            if (!$tentativas) {
                throw new \Exception("Não foi possível gerar o código da aposta\n{$codigo}");
            }
        }

        return $codigo;
    }

    /**
     * @param ApostaVO $aposta
     * @return IndicacaoVO[]
     */
    public static function bonusComercializacao(ApostaVO $aposta)
    {

        $user = $aposta->voUser();
        $indicadores = IndicacoesModel::getIndicadores($user);

        if ($user->estaEmDia() and $licenca = $user->voLicenca()) {

            $comissao = DadosModel::getBonusComercializacao($user, $aposta->getNivelBonusComissao());

            if ($comissao > 0) {
                $comissao = $aposta->getValor() * $comissao / 100;
                $user->setComercializacao($user->getComercializacao() + $comissao);
                $user->save();
                HistoricoBancarioModel::add($user, $comissao, "<b>COMISSÃO</b> - Comissão de “Comercialização” da aposta (#{$aposta->getId()}) do usuário “{$user->getLogin()}”", $aposta, "comercializacao");
            }
        }

        return $indicadores;
    }

    /**
     * Retorna todoas as apostas contendo o jogo
     * @param JogoVO $jogo
     * @return ApostaVO[]
     */
    static function getApostasJogo(JogoVO $jogo)
    {

        $table = ApostaJogosModel::getTable();

        $termos = <<<SQL
INNER JOIN 
    `{$table}` AS jogo ON jogo.aposta = a.id AND jogo.jogo = :jogo

WHERE 
    a.status != 99 
SQL;

        return self::lista($termos, ['jogo' => $jogo->getId()]);
    }

    /**
     * Faz uma revalidação das apostas em busca de resultados incorretos
     */
    static function revalidarApostas()
    {

        $tbApostas = self::getTable();
        $tbJogos = ApostaJogosModel::getTable();

        $termos = <<<SQL

UPDATE
    `{$tbApostas}` AS a,
    (
        SELECT
            a.id,
            SUM(IF(b.verificado = 1 AND b.acertou = 0, 1, 0)) AS erros,
            SUM(IF(b.verificado = 1 AND b.acertou = 1, 1, 0)) AS acertos,
            COUNT(DISTINCT b.id) AS jogos,
            SUM(IF(b.verificado = 1, 1, 0)) AS jogosverificados
            
        FROM
            `{$tbApostas}` AS a
                    
        INNER JOIN
            `{$tbJogos}` AS b
                ON b.aposta = a.id AND b.status = 1
                
        WHERE
            a.status != 99
                AND (
                    (
                        a.jogos < a.jogosverificados
                    )
                    OR
                    (
                        a.ganhou = 1 AND a.erros > 0
                    )
                    OR
                    (
                        a.jogosverificados != a.acertos + a.erros
                    )
                    OR 
                    (
                        a.jogosverificados >= a.jogos AND a.verificado = 0
                    )
                    OR
                    (
                        a.jogosverificados > 1 AND a.user = 0 AND a.status IN(1, 2)
                    )
                )
            
        GROUP BY 
            a.id
                
    ) AS b
        
SET
    a.erros = b.erros,
    a.acertos = b.acertos,
    a.jogos = b.jogos,
    a.jogosverificados = b.jogosverificados,
    a.ganhou = IF(b.jogos = b.acertos, 1, 0),
    a.verificado = IF(b.jogosverificados = b.jogos, 1, 0),
    a.status = IF(a.status = 1 AND a.user = 0 OR a.status = :apaga, :npaga, a.status)

WHERE
    a.id = b.id AND a.status != 99;
    
SQL;

        $prepare = Conn::getConn()->prepare($termos);

        $prepare->execute([
            'apaga' => self::STATUS_AGUARDANDO_PAGAMENTO,
            'npaga' => self::STATUS_NPAGA,
        ]);

        return $prepare->rowCount();

    }

    /**
     * Aplica o resultado as apostas que contem o jogo em questão
     * @param JogoVO $jogo
     * @return int
     */
    static function validarApostas(JogoVO $jogo)
    {

        $tbJogos = ApostaJogosModel::getTable();
        $tbApostas = self::getTable();

        $termos = <<<SQL
        
UPDATE
    `{$tbApostas}` AS a,
    (
        SELECT
            a.id,
            SUM(IF(b.verificado = 1 AND b.acertou = 0, 1, 0)) AS erros,
            SUM(IF(b.verificado = 1 AND b.acertou = 1, 1, 0)) AS acertos,
            COUNT(DISTINCT b.id) AS jogos,
            SUM(IF(b.verificado = 1, 1, 0)) AS jogosverificados
            
        FROM
            `{$tbApostas}` AS a
                    
        INNER JOIN
        `{$tbJogos}` AS jogo
            ON jogo.aposta = a.id AND jogo.jogo = :jogo
        
        INNER JOIN
            `{$tbJogos}` AS b
                ON b.aposta = a.id AND b.status = 1
                
        WHERE
            a.status != 99
            
        GROUP BY 
            a.id
                
    ) AS b
        
SET
    a.erros = b.erros,
    a.acertos = b.acertos,
    a.jogos = b.jogos,
    a.jogosverificados = b.jogosverificados,
    a.ganhou = IF(b.jogos = b.acertos, 1, 0),
    a.verificado = IF(b.jogosverificados = b.jogos, 1, 0),
    a.status = IF(a.status = 1 AND a.user = 0 OR a.status = :apaga, :npaga, a.status)

WHERE
    a.id = b.id AND a.status != 99;
    
SQL;


        $prepare = Conn::getConn()->prepare($termos);

        $prepare->execute([
            'jogo' => $jogo->getId(),
            'apaga' => self::STATUS_AGUARDANDO_PAGAMENTO,
            'npaga' => self::STATUS_NPAGA,
        ]);

        return $prepare->rowCount();

    }

    /**
     * @param ApostaVO $aposta
     * @param ApostaJogoVO[] $apostaJogos
     * @return ApostaVO
     * @throws \Exception
     */
    static function apostar(ApostaVO $aposta, array $apostaJogos): ApostaVO
    {

        // Configurações do sistema
        $config = DadosModel::get();

        // Apostador
        $user = $aposta->voUser();

        // Graduação do apostador
        $graduacao = $user ? $user->voGraduacao() : null;

        // Cotação total
        $tCotacao = 1;

        // Sem jogos na aposta
        if (!$apostaJogos) {
            throw new \Exception("Aposta sem jogos");
        }

        // Verificando limites
        $apostaMinima = ($user ? $user->getApostaMinima() : 0) ?: ($graduacao ? $graduacao->getApostaMinima() : 0) ?: $config->getApostaMinima();
        $apostaMaxima = ($user ? $user->getApostaMaxima() : 0) ?: ($graduacao ? $graduacao->getApostaMaxima() : 0) ?: $config->getApostaMaxima();
        $minJogos = ($user ? $user->getMinJogos() : 0) ?: ($graduacao ? $graduacao->getMinJogos() : 0) ?: $config->getMinJogos();

        $cotacaoMinima = ($graduacao ? $graduacao->getCotacaoMinima() : 0) ?: $config->getCotacaoMinima();
        $cotacaoMaxima = ($graduacao ? $graduacao->getCotacaoMaxima() : 0) ?: $config->getCotacaoMaxima();

        // Adicionar configurações a aposta
        $aposta->setJogos(count($apostaJogos));
        // Nenhum jogo foi apurado no momento em que o bilhete é criado.
        // Enviar explicitamente o zero evita depender de um DEFAULT do MySQL.
        $aposta->setJogosverificados(0);
        $aposta->setMinJogos($minJogos);
        $aposta->setCotacaoMaxima($cotacaoMaxima);
        $aposta->setRetornoMaximo($config->getRetornoMaximo());

        // Verificando saldo do apostador
        if ($user) {
            $aposta->setGerente($user->getUser());
            if ($user->getCredito() < $aposta->getValor()) {
                throw new \Exception("Saldo insuficiente para concluír a aposta");
            }
        }

        // Verificando o limite de jogos
        if ($aposta->getJogos() < $aposta->getMinJogos()) {
            throw new \Exception("Mínimo de {$aposta->getMinJogos()} jogos na aposta");
        }

        $apostaJogosIds = [];

        // Validando jogos
        foreach ($apostaJogos as $apostaJogo) {

            if (in_array($apostaJogo->getJogo(), $apostaJogosIds))
                throw new \Exception("Jogo repetido na aposta, você só pode apostar em uma cotação de cada jogo");

            $apostaJogosIds[] = $apostaJogo->getJogo();

            $jogo = $apostaJogo->voJogo();

            $limite = self::limiteJogo($apostaJogo);
            $limiteApostas = $jogo->getMaxApostas() ?: $config->getMaxApostas();
            $tCotacao *= $apostaJogo->getCotacaoValor();

            if (!$jogo instanceof JogoVO) {
                throw new \Exception("Jogo inválido");
            } else if (!$jogo->aceitaApostas()) {
                throw new \Exception("Jogo `{$jogo->getDescricao()}` não está mais recebendo apostas");
            } else if ($limite > 0 and $aposta->getValor() > $limite) {
                $limiteReal = Number::real($limite);
                throw new \Exception("O limite de aposta para o jogo {$jogo->getDescricao()} é de R$ {$limiteReal}");
            } else if ($limiteApostas > 0 and JogosModel::getApostas($jogo, true) >= $limiteApostas) {
                throw new \Exception("O jogo {$jogo->getDescricao()} já atingiu o limite de apostas");
            }

        }

        // Cotação mínima variável
        foreach ($config->getCondicaoCotacao(true) as $condicao) {
            if (($condicao['min'] or $condicao['max']) and $condicao['cotacao'] > 0) {
                if ($condicao['min'] <= $aposta->getValor() and $condicao['max'] >= $aposta->getValor()) {
                    $cotacaoMinima = $condicao['cotacao'];
                    break;
                }
            }
        }

        // Definindo a Cotação
        $aposta->setCotacao($tCotacao);

        if ($cotacaoMinima > $aposta->getCotacao()) {
            throw new \Exception("Você precisa atingir a cotação mínima de " . Number::real($cotacaoMinima));
        }

        // Valor da aposta
        if ($aposta->getValor() < $config->getApostaMinima()) {
            throw new \Exception("Aposta mínima é de R$ {$config->getApostaMinima(true)}");
        } else if ($aposta->getValor() > $config->getApostaMaxima()) {
            throw new \Exception("Aposta máxima é de R$ {$config->getApostaMaxima(true)}");
        }

        // Validando cotação
        if ($aposta->getCotacaoValida() < $config->getCotacaoMinima()) {
            throw new \Exception("Cotação mínima é de R$ {$config->getCotacaoMinima(true)}");
        }

        $aposta->save();

        // Salvando jogos
        foreach ($apostaJogos as $v) {
            $v->setAposta($aposta->getId());
            $v->save();
        }

        if ($config->getRetornoRisco() and $config->getStatus() == self::STATUS_ATIVA) {
            if ($aposta->getRetornoValido() >= $config->getRetornoRisco()) {
                self::sendAvisoRisco($aposta);
            }
        }

        return $aposta;

    }

    /**
     * Retorna o limite de apostas do jogo
     * @param ApostaJogoVO $apostaJogo
     * @return float
     */
    static function limiteJogo(ApostaJogoVO $apostaJogo)
    {

        $config = DadosModel::get();

        $user = $apostaJogo->voUser();
        $jogo = $apostaJogo->voJogo();

        $limite = min(4, $apostaJogo->getJogos());

        return ($user ? $user->get("limite{$limite}") : 0)
            ?: ($jogo ? $jogo->get("limite{$limite}") : 0)
                ?: $config->get("limite{$limite}") ?: 0;
    }

    /**
     * @param ApostaVO $aposta
     * @return \Exception
     */
    static function sendAvisoRisco(ApostaVO $aposta)
    {
        try {

            $mail = new SMail;

            $mail->addAddress($mail->getConfig()->getNome(), $mail->getConfig()->getEmail());

            $mail->setContent('Aposta de risco', self::instance()->view('email/aposta-risco', [
                'aposta' => $aposta,
            ], null, true));

            $mail->Send();

        } catch (\Exception $ex) {
            error_log("AvisoRiscoError: {$ex->getMessage()}");
        }
    }

    /**
     * Retorna o total em apostas do jogo
     * @param ApostaJogoVO $jogo
     * @return float
     */
    static function valorTotalApostas(ApostaJogoVO $jogo)
    {

        switch ($jogo->getJogos()) {
            case 1:
                $query = 'a.jogos = 1';
                break;
            case 1:
                $query = 'a.jogos = 2';
                break;
            default:
                $query = 'a.jogos > 2';
        }

        $termos = "SELECT SUM(a.valor) AS total FROM `" . ApostaJogosModel::getTable() . "` AS a WHERE a.jogo = :jogo AND {$query} AND a.status = 1 GROUP BY a.jogo";
        $places = ['jogo' => $jogo->getJogo()];
        $total = Model::pdoRead()->FullRead($termos, $places)->getResult();

        return $total ? (float)$total[0]['total'] : 0;

    }

    /**
     * Retorna o total em apostas no dia
     * @param UserVO $user
     * @param date $data
     * @return float
     */
    static function totalDiario(string $data = null)
    {
        $total = Model::pdoRead()->FullRead("SELECT SUM(a.valor) AS total FROM `" . self::getTable() . "` AS a WHERE a.data = :data;", [
            'data' => $data ?: date('Y-m-d'),
        ])->getResult();

        return $total ? (float)$total[0]['total'] : 0;
    }

}
