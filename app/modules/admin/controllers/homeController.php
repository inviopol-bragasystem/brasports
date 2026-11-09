<?php

namespace app\modules\admin\controllers;

use app\core\Controller;
use app\core\crud\Conn;
use app\core\Model;
use app\models\ApostaJogosModel;
use app\models\ApostasModel;
use app\models\DadosModel;
use app\models\JogosModel;
use app\models\TimesModel;
use app\models\UsersModel;
use app\modules\admin\Admin;

class homeController extends Controller
{

    function indexAction()
    {

        if (!Admin::isMaster())
            location();

        $config = DadosModel::get();

        if (date('Y-m-d') < $config->getPeriodoFinal()) {
            $diasRestantes = round(max(0, (strtotime($config->getPeriodoFinal()) - time()) / (24 * 60 * 60)));
            $diasRestantes = "({$diasRestantes} dias restantes)";
        } else {
            $diasRestantes = '';
        }

        $vars = [
            'apostasInfos' => $this->getApostasInfos(),
            'resumoCadastros' => UsersModel::getResumoCadastros(),
            'possiveisGanhadores' => $this->getPossiveisGanhadores(),
            'jogosMaisApostados' => $this->getJogosMaisApostados(),
            'resumoMes' => $this->getGrafico(),
            'resumoSistema' => $this->getResumoSistema(),
        ];

        $this->view('admin/sys/principal', $vars);

    }

    function getApostasInfos()
    {

        $table = ApostasModel::getTable();

        $termos = <<<SQL
SELECT 
    SUM(IF(a.data = :hoje, a.valor, 0)) AS valorApostas, 
    SUM(IF(a.erros = 0 AND a.verificado = 0, a.retornovalido, 0)) AS retorno,
    SUM(IF(a.erros = 0 AND a.verificado = 0, a.valor, 0)) AS emAberto
    
FROM 
    `{$table}` AS a 

WHERE 
    a.status = 1 
SQL;

        return Model::pdoRead()->FullRead($termos, [
            'hoje' => date('Y-m-d'),
        ])->getResult()[0];

    }

    function getPossiveisGanhadores()
    {
        $table = ApostasModel::getTable();
        $tbUsers = UsersModel::getTable();

        $termos = <<<SQL
SELECT DISTINCT
    a.id,
    a.token,
    user.nome AS apostador, 
    a.valor, 
    a.retornovalido AS retorno

FROM 
    `{$table}` AS a 
    
INNER JOIN 
    `{$tbUsers}` AS user ON user.id = a.user

WHERE
    a.acertos > 0 AND a.erros = 0 AND a.ganhou = 0 AND a.user > 0 AND a.status = 1

ORDER BY 
    a.update ASC 

LIMIT 20
SQL;


        return Model::pdoRead()->FullRead($termos)->getResult();
    }

    function getJogosMaisApostados()
    {
        $table = ApostaJogosModel::getTable();
        $tbJogos = JogosModel::getTable();
        $tbTimes = TimesModel::getTable();

        $termos = <<<SQL
SELECT a.jogo, jogo.token, COUNT(a.id) apostas, SUM(a.valor) AS valor, CONCAT(timecasa.title, ' x ', timefora.title) AS times 
FROM `{$table}` AS a 
INNER JOIN `{$tbJogos}` AS jogo ON jogo.id = a.jogo 
INNER JOIN `{$tbTimes}` AS timecasa ON timecasa.id = jogo.timecasa
INNER JOIN `{$tbTimes}` AS timefora ON timefora.id = jogo.timefora
WHERE jogo.status = 1 
GROUP BY a.jogo 
ORDER BY apostas DESC 
LIMIT 20
SQL;


        return Model::pdoRead()->FullRead($termos)->getResult();
    }

    function getGrafico()
    {

        $termos = <<<SQL
SELECT 
  a.data,
  SUM(a.valor) AS valor,
  SUM(IF(a.ganhou, a.retornovalido, 0)) AS premio
  
FROM 
  `sis_apostas` AS a
  
WHERE 
  a.status = 1 AND a.data BETWEEN :inicio AND :fim

GROUP BY
  a.data

ORDER BY
  a.data ASC
SQL;

        $places = [
            'inicio' => date('Y-m-01'),
            'fim' => date('Y-m-t'),
        ];

        $registros = Model::pdoRead()->FullRead($termos, $places)->getResult();

        $result = [
            'valor' => 0,
            'premio' => 0,
            'registros' => [],
        ];

        foreach ($registros as $v) {
            $result['valor'] += $v['valor'];
            $result['premio'] += $v['premio'];
            $result['registros'][] = $v;
        }

        $result['liquido'] = $result['valor'] - $result['premio'];

        return $result;

    }

    function getResumoSistema()
    {

        if (!Admin::isMaster())
            return null;

        $termos = <<<SQL
SELECT 
    SUM(IF(a.reftype IN('deposito', 'administrador'), a.valor, 0)) AS creditado,
    SUM(a.valor) AS saldoSistema
    
FROM 
    `sis_historico_bancario` AS a

INNER JOIN 
    `sis_users` AS b ON b.id = a.user AND b.status != 99

WHERE 
    a.status = 1

LIMIT 1
SQL;

        $places = [
        ];

        $result = current(Model::pdoRead()->FullRead($termos, $places)->getResult());

        $result['total'] = $result['creditado'] - $result['saldoSistema'];

        return $result;

    }

    function revisaApostasAction()
    {
        try {

            Conn::startTransaction();

            $alterados = ApostasModel::revalidarApostas();

            Conn::commit();

            if (!$alterados)
                $message = "Parabêns!!!\nSeu sistema não possuí apostas que necessitam ser analisadas.";

            return [
                'message' => $message ?? "{$alterados} apostas foram revisadas",
                'result' => 1,
            ];
        } catch (\Exception $ex) {
            Conn::rollBack();
            return $ex;
        }
    }

}
    