<?php

namespace app\modules\api\controllers;

use app\core\Controller;
use app\core\Model;
use app\helpers\Date;
use app\models\ApostasModel;
use app\models\HistoricoBancarioModel;
use app\modules\admin\controllers\apostas\caixaController as caixaAdmController;
use app\modules\api\API;
use app\vo\financeiro\PagamentoVO;
use app\vo\UserVO;
use Exception;

class caixaController extends Controller
{

    /**
     * @var PagamentoVO|null
     */
    private $ultimoPagamento;

    function indexAction(UserVO $user = null)
    {
        try {

            $user = $user ?: API::getUser();

            $places = $this->places();

            $totais = [
                'vendas' => 0,
                'premios' => 0,
                'premiosAberto' => 0,
                'comissoes' => 0,
                'comissoesGerente' => 0,
                'permiosAberto' => 0,
            ];

            $registros = caixaAdmController::instance()
                ->getRegistros($places['user'], $places['dataInicial'], $places['dataFinal']);

            foreach ($registros as $v) {

                $totais['vendas'] += (float)$v['valor'];
                $totais['premios'] += (float)$v['retorno'];
                $totais['premiosAberto'] += (float)$v['retornoEmAberto'];
                $totais['comissoes'] += (float)$v['comissao'];
                $totais['comissoesGerente'] += (float)$v['comissaoGerente'];

            }

            $results = [
                ['class' => 'success', 'label' => 'Total em apostas', 'valor' => (float)$totais['vendas']],
                ['class' => 'danger', 'label' => 'Prêmios', 'valor' => (float)$totais['premios']],
                ['class' => 'danger', 'label' => 'Prêmios em aberto', 'valor' => (float)$totais['premiosAberto']],
                ['class' => 'primary', 'label' => 'Comissões', 'valor' => (float)$totais['comissoes']],
                ['class' => 'success', 'label' => 'Total Banqueiro', 'valor' => (float)$totais['vendas'] - $totais['premios'] - $totais['comissoes']],
                ['class' => 'primary', 'label' => 'Total', 'valor' => (float)$totais['comissoes'] + $totais['premios']],
            ];

            if ($totais['comissoesGerente']) {
                $results[] = [
                    'color' => 'whitesmoke',
                    'label' => 'Total',
                    'valor' => (float)$totais['comissoesGerente'],
                ];
            }

            return [
                'results' => $results,
                'places' => $this->places(),
                'total' => $totais['premios'] + $totais['comissoes'] + $totais['comissoesGerente'],
                'result' => 1,
            ];

        } catch (Exception $ex) {
            return $ex;
        }
    }

    function places()
    {

        if (!inputPost('dataInicial') and $this->ultimoPagamento) {
            $dataInicial = $this->ultimoPagamento->getInsert();
        } else {
            $dataInicial = inputPost('dataInicial') ?: date('Y-m-01');
        }

        $dataFinal = inputPost('dataFinal') ?: date('Y-m-d');
        $cliente = (int)inputPost('cliente') ?: 0;

        $places = [
            'user' => API::getUser()->getId(),
            'dataInicial' => $dataInicial,
            'dataFinal' => $dataFinal,
            'cliente' => $cliente,
        ];

        return $places;
    }

    function premios(bool $emAberto = false)
    {
        $places = $this->places();

        $query = clone ApostasModel::getBuildQuery();
        $query->setSelectFields(['SUM(a.retornovalido) AS total']);
        $termos = "a.user = :user AND (:cliente = 0 OR a.cliente = :cliente) AND a.insert BETWEEN :dataInicial AND :dataFinal AND a.status = 1";

        if ($emAberto) {
            $termos .= " AND a.erros = 0 AND a.verificado = 0";
        } else {
            $termos .= " AND a.ganhou = 1";
        }

        $query->setWhere($termos);
        return (float)$query->execute($places)[0]['total'] ?? 0;
    }

    function comissoes()
    {
        $places = $this->places();
        unset($places['cliente']);
        $query = clone HistoricoBancarioModel::getBuildQuery();
        $query->setSelectFields(['SUM(a.valor) AS total']);
        $query->setWhere("a.user = :user AND a.insert BETWEEN :dataInicial AND :dataFinal AND a.reftype = 'comissao' AND a.status = 1");
        return (float)$query->execute($places)[0]['total'] ?? 0;
    }

    function vendas()
    {
        $places = $this->places();
        $query = clone ApostasModel::getBuildQuery();
        $query->setSelectFields(["SUM(a.valor) AS total"]);
        $query->setWhere("a.user = :user AND (:cliente = 0 OR a.cliente = :cliente) AND a.insert BETWEEN :dataInicial AND :dataFinal AND a.status = 1");
        return (float)$query->execute($places)[0]['total'] ?? 0;
    }

    function mesesAction(UserVO $user = null)
    {
        try {

            $user = $user ?: API::getUser();

            $termos = <<<SQL
SELECT a.data
FROM sis_apostas AS a
WHERE a.user = :user AND a.status != 99 AND a.status != 0
GROUP BY MONTH(a.data), YEAR(a.data)
ORDER BY a.data DESC
SQL;

            $places = [
                'user' => $user->getId(),
            ];

            $meses = [];

            foreach (Model::pdoRead()->FullRead($termos, $places)->getResult() as $v) {
                $time = strtotime($v['data']);
                $meses[] = [
                    'descricao' => Date::getMonthName($v['data']) . '/' . Date::year($v['data']),
                    'dataInicial' => date('Y-m-01 00:00:00', $time),
                    'dataFinal' => date('Y-m-t 23:59:59', $time),
                ];
            }

            if (!$meses) {
                $meses[] = [
                    'descricao' => Date::getMonthName(date('m')) . '/' . date('Y'),
                    'dataInicial' => date('Y-m-01 00:00:00'),
                    'dataFinal' => date('Y-m-t 23:59:59'),
                ];
            }

            return [
                'meses' => $meses,
                'result' => 1,
            ];
        } catch (Exception $ex) {
            return $ex;
        }
    }

}