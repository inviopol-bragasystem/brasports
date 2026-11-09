<?php

namespace app\modules\website\controllers;

use app\core\Controller;
use app\core\crud\Conn;
use app\core\Model;
use app\helpers\Date;
use app\helpers\Number;
use app\models\ApostaJogosModel;
use app\models\ApostasModel;
use app\models\CotacoesModel;
use app\models\DadosModel;
use app\models\helpers\OptionsModel;
use app\models\HistoricoBancarioModel;
use app\models\JogosModel;
use app\modules\admin\Admin;
use app\vo\ApostaJogoVO;
use app\vo\ApostaVO;
use app\helpers\TeamBadgeHelper;
use app\vo\helpers\OptionVO;
use app\vo\CotacaoVO;
use app\vo\JogoVO;

class apostarController extends Controller
{

    protected $tpl = 'website/page/apostar';

    function indexAction()
    {

        $config = DadosModel::get();

        $user = Admin::getLogged();

        $graduacao = $user ? $user->voGraduacao() : null;

        $cotacaoMaxima = ($graduacao ? $graduacao->getCotacaoMaxima() : 0) ?: $config->getCotacaoMaxima();
        $cotacaoMinima = ($graduacao ? $graduacao->getCotacaoMinima() : 0) ?: $config->getCotacaoMinima();
        $apostaMinima = ($user ? $user->getApostaMinima() : 0) ?: ($graduacao ? $graduacao->getApostaMinima() : 0) ?: $config->getApostaMinima();
        $apostaMaxima = ($user ? $user->getApostaMaxima() : 0) ?: ($graduacao ? $graduacao->getApostaMaxima() : 0) ?: $config->getApostaMaxima();
        $retornoMaximo = $config->getRetornoMaximo();
        $minJogos = ($user ? $user->getMinJogos() : 0) ?: ($graduacao ? $graduacao->getMinJogos() : 0) ?: $config->getMinJogos();

        $hoje = date('Y-m-d');
        $amanha = date('Y-m-d', strtotime('+1day'));

        $this->view($this->tpl, [
            'hoje' => $hoje,
            'amanha' => $amanha,
            'cotacaoMaxima' => $cotacaoMaxima ?: 999,
            'cotacaoMinima' => $cotacaoMinima,
            'apostaMinima' => $apostaMinima,
            'apostaMaxima' => $apostaMaxima ?: 9999,
            'retornoMaximo' => $retornoMaximo ?: 9999,
            'condicaoCotacao' => $config->getCondicaoCotacao(true),
            'minJogos' => $minJogos,
            'premioRevenda' => $config->getRevendaPaga(),
            'pageAposta' => true,
        ]);
    }

    function jogosAction()
    {
        $datas = [];
        $cotacoes = $this->cotacoes();
        $jogos = $this->jogos($datas);

        return [
            'cotacoes' => $cotacoes,
            'grupos' => CotacoesModel::GRUPOS,
            'paises' => $jogos,
            'datas' => is_array($datas) ? array_values($datas) : [],
        ];
    }

    function cotacoes()
    {

        $termos = <<<SQL
SELECT a.id, a.sigla, a.titulo AS title, LOWER(TRIM(a.campo)) AS campo, a.cor, a.grupo, a.principal
FROM `sis_cotacoes` AS a
WHERE a.status = 1 
ORDER BY a.ordem ASC, a.titulo ASC
SQL;

        return Model::pdoRead()->FullRead($termos)->getResult();
    }

    function jogos(&$datas = [])
    {

        $termos = <<<SQL
SELECT
    a.id,
    a.campeonato AS campeonatoId,
    d.pais,
    d.title AS campeonato,
    b.id AS timecasaId,
    c.id AS timeforaId,
    b.title AS casa,
    c.title AS fora,
    a.data,
    a.hora,
    a.cotacoes
    
FROM
    `sis_jogos` AS a
    
INNER JOIN
    `sis_times` AS b ON b.id = a.timecasa AND b.status = 1
    
INNER JOIN
    `sis_times` AS c ON c.id = a.timefora AND c.status = 1
    
INNER JOIN
    `sis_campeonatos` AS d ON d.id = a.campeonato AND d.status = 1
    
WHERE 
    a.status = 1
    AND (a.data > DATE(:corte) OR (a.data = DATE(:corte) AND a.hora > TIME(:corte)))
    
ORDER BY
    (CASE WHEN d.pais = 12 OR d.title LIKE '%Brasil%' OR d.title LIKE '%Brasileir%' THEN 0 ELSE 1 END),
    d.title ASC, a.data ASC, a.hora ASC
SQL;

        $minutos = (int)DadosModel::get()->getMinutosJogo() + 5;
        $corte = date('Y-m-d H:i:s', strtotime("+{$minutos}minutes"));

        $places = [
            'corte' => $corte,
        ];

        $hoje = date('Y-m-d');
        $amanha = date('Y-m-d', strtotime('+1day'));

        $paises = [];
        $campeonatos = [];
        $result = [];
        $registros = Model::pdoRead()->FullRead($termos, $places)->getResult();

        $exibirEscudos = (bool)\app\models\DadosModel::get()->getExibirEscudos();

        foreach ($registros as $i => $v) {

            if (!isset($datas[$v['data']])) {
                $dt = date('d/m', strtotime($v['data']));
                $datas[$v['data']] = [
                    'data' => $v['data'],
                    'title' => ($v['data'] == $hoje ? 'Jogos de hoje ' : ($v['data'] == $amanha ? 'Jogo de amanhã ' : 'Jogos de ')) . $dt,
                ];
            }

            $paises[] = $v['pais'];
            $registros[$i]['dateTime'] = date('c', strtotime("{$v['data']} {$v['hora']}"));
            $registros[$i]['cotacoes'] = $v['cotacoes'] = json_decode($v['cotacoes'], true);

            // Resolve imagens / bandeiras dos times e do campeonato (se ativado na personalização)
            $registros[$i]['imgCasa'] = $exibirEscudos ? TeamBadgeHelper::getTeamBadge((string)$v['casa'], (int)($v['timecasaId'] ?? 0), (int)($v['pais'] ?? 0)) : null;
            $registros[$i]['imgFora'] = $exibirEscudos ? TeamBadgeHelper::getTeamBadge((string)$v['fora'], (int)($v['timeforaId'] ?? 0), (int)($v['pais'] ?? 0)) : null;
            $registros[$i]['paisImg'] = TeamBadgeHelper::getCountryFlag((int)($v['pais'] ?? 0), (string)($v['campeonato'] ?? ''));

            if (!isset($campeonatos[(int)$v['campeonatoId']])) {
                $campeonatos[(int)$v['campeonatoId']] = [
                    'title' => $v['campeonato'],
                    'pais' => (int)$v['pais'],
                    'paisImg' => $registros[$i]['paisImg'],
                ];
            }
        }


        if ($paises = array_unique($paises)) {
            $paises = OptionsModel::lista('WHERE a.id IN(' . implode(', ', $paises) . ') ORDER BY (CASE WHEN a.id = 12 THEN 0 ELSE 1 END), a.ordem ASC, a.title ASC LIMIT :limit', [
                'limit' => count($paises),
            ]);
        }

        $i = 0;
        foreach ($campeonatos as $id => $campData) {
            $result[$i]['id'] = $id;
            $result[$i]['title'] = is_array($campData) ? $campData['title'] : $campData;
            $result[$i]['pais'] = is_array($campData) ? $campData['pais'] : 0;
            $result[$i]['paisImg'] = is_array($campData) ? $campData['paisImg'] : TeamBadgeHelper::getCountryFlag((int)$result[$i]['pais']);
            foreach ($registros as $v) {
                if ((int)$v['campeonatoId'] == $id) {
                    $result[$i]['pais'] = (int)$v['pais'];
                    $result[$i]['jogos'][] = $v;
                }
            }
            $i++;
        }

        $resultPaises = [];

        /** @var OptionVO $pais */
        foreach ($paises as $pais) {

            $dados = [
                'id' => $pais->getId(),
                'title' => $pais->getTitle(),
                'img' => $pais->imgCapa()->getSource(true),
                'campeonatos' => [],
            ];

            foreach ($result as $i => $c) {
                if ($c['pais'] == $pais->getId()) {
                    $dados['campeonatos'][] = $c;
                    unset($result[$i]);
                }
            }

            $resultPaises[] = $dados;
        }

        if ($result) {

            $dados = [
                'id' => 0,
                'title' => 'Outros',
                'img' => source_images('default.jpg'),
                'campeonatos' => []
            ];

            foreach ($result as $i => $v) {
                $dados['campeonatos'][] = $v;
                unset($result[$i]);
            }

            $resultPaises[] = $dados;
        }

        if (is_array($datas)) {
            ksort($datas);
        } else {
            $datas = [];
        }

        return $resultPaises;

    }

    function imprimirTabelaAction()
    {

        $places = [
            'disponivel' => 1,
            'data' => Date::data(inputGet('data')),
            'search' => trim(inputGet('search')) ?: null,
            'campeonato' => inputGet('campeonato') > 0 ? inputGet('campeonato') : null,
        ];

        $busca = JogosModel::busca($places, 1, 999);

        $campeonatos = [];

        /** @var JogoVO $v */
        foreach ($busca->getRegistros() as $v) {
            if (empty($campeonatos[$v->getCampeonato()])) {
                $campeonatos[$v->getCampeonato()] = $v->getCampeonatoTitle();
            }
        }

        $this->view('website/page/apostar-imprimir-tabela', [
            'campeonatos' => $campeonatos,
            'jogos' => $busca->getRegistros(),
        ]);

    }

    function imprimirAction()
    {
        try {
            $aposta = ApostasModel::getByLabel('token', url_parans(0));

            if (!$aposta instanceof ApostaVO) {
                throw new \Exception('Aposta inválida');
            }

            $this->view('website/page/pre-bilhete', [
                'aposta' => $aposta,
            ]);

        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }
    }

    function apostarAction()
    {
        try {

            Conn::startTransaction();

            $user = Admin::getLogged();

            $valor = Number::float(inputPost('valor'));
            $jogos = inputPost('jogos') ?: [];
            $dados = DadosModel::get();

            /** @var ApostaJogoVO[] $apostaJogos */
            $apostaJogos = [];

            foreach ($jogos as $v) {

                if (!is_array($v) || !isset($v['jogo'], $v['cotacao'], $v['tempo'])) {
                    throw new \Exception("Seleção inválida. Atualize a página e escolha as cotações novamente.");
                }

                $jogo = JogosModel::getByLabel('id', $v['jogo']);
                $cotacao = CotacoesModel::getAtivaBySelecao($v['cotacao'], $v['cotacaoId'] ?? null);

                if (!($jogo instanceof JogoVO)) {
                    throw new \Exception("Jogo inválido");
                } else if (!($cotacao instanceof \app\vo\CotacaoVO)) {
                    throw new \Exception("Cotação inválida");
                } else if (!$jogo->aceitaApostas()) {
                    throw new \Exception("Jogo `{$jogo->getDescricao()}` não está mais recebendo apostas");
                }

                $jogoCotacoes = $jogo->getCotacoes(true);
                $tempo = (string)$v['tempo'];
                $campo = $cotacao->getCampo();
                $valorRaw = isset($jogoCotacoes[$tempo]) && is_array($jogoCotacoes[$tempo])
                    ? ($jogoCotacoes[$tempo][$campo] ?? null)
                    : null;
                $valorCotacao = is_numeric($valorRaw) ? (float)$valorRaw : 0.0;

                if ($valorCotacao <= 1) {
                    throw new \Exception("A cotação selecionada não está mais disponível. Atualize a página e escolha outra opção.");
                }

                $apostaJogos[] = ApostaJogosModel::newValueObject([
                    'user' => $user ? $user->getId() : 0,
                    'jogo' => $jogo->getId(),
                    'valor' => $valor,
                    'tempo' => $tempo,
                    'tipo' => $cotacao->getCampo(),
                    'cotacaovalor' => $valorCotacao,
                    'cotacaotitle' => $cotacao->getTitulo(),
                    'cotacaogrupo' => $cotacao->getGrupo(),
                    'cotacaosigla' => $cotacao->getSigla(),
                    'jogos' => count($jogos),
                    'cotacaocampo' => $cotacao->getCampo(),
                ]);

            }

            /** @var ApostaVO $aposta */
            $aposta = ApostasModel::newValueObject();

            $aposta->setValor($valor);
            $aposta->setData(date('Y-m-d'));

            if ($user) {
                $aposta->setUser($user->getId());
                $aposta->setGerente($user->getUser());
                $aposta->setApostadorNome(inputPost('cliente') ?: $user->getNome());
                $aposta->setApostadorTelefone(inputPost('telefone') ?: $user->getTelefone());
                $aposta->setApostadorRevendedor(inputPost('revendedor') ?: $user->getTelefone());
                $aposta->setStatus(ApostasModel::STATUS_ATIVA);
            } else {
                $cliente = trim((string)inputPost('cliente')) ?: 'Cliente';
                $aposta->setApostadorNome($cliente);
                $aposta->setApostadorTelefone(inputPost('telefone'));
                $aposta->setApostadorRevendedor(inputPost('revendedor'));
                $aposta->setStatus(ApostasModel::STATUS_AGUARDANDO_PAGAMENTO);
            }

            ApostasModel::apostar($aposta, $apostaJogos);

            if ($user) {
                HistoricoBancarioModel::add($user, -$valor, "Pagamento de aposta #{$aposta->getId()}", $aposta, 'aposta');
                ApostasModel::pagarComissoes($aposta);
            }

            Conn::commit();

            $urlBilhete = url('apostas/bilhete', [$aposta->getToken()]);

            $resposta = [
                'message' => $user ? 'Aposta confirmada e saldo atualizado.' : 'Pré-bilhete gerado com sucesso. Procure um cambista para validar a aposta.',
                'logged' => (bool)$user,
                'result' => 1,
            ];

            if ($user) {
                $resposta['saldo'] = $user->getCredito();
                $resposta['historico'] = url('apostas');
            } else {
                $resposta['codigo'] = $aposta->getCodigoBilhete();
                $resposta['share'] = 'https://api.whatsapp.com/send?' . http_build_query(['text' => $urlBilhete]);
                $resposta['link'] = url('apostar/imprimir', [$aposta->getToken()]);
            }

            return $resposta;
        } catch (\Exception $ex) {
            Conn::rollBack();
            return $ex;
        }
    }

    function dateAction()
    {

        echo date("c");

    }

}
