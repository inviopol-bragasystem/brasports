<?php

namespace app\modules\admin\controllers\apostas;

use app\core\Controller;
use app\core\Model;
use app\helpers\Date;
use app\modules\admin\Admin;

class caixaController extends Controller
{

    protected $tpl = 'admin/apostas/caixa';

    function indexAction()
    {
        $this->view($this->tpl);
    }

    function listAction()
    {

        ini_set('display_errors', true);

        $idUser = Admin::isMaster() ? inputPost('user') ?: 0 : Admin::getIdLogged();
        $dataInicial = Date::data(inputPost('dataInicial')) ?: date('Y-m-01');
        $dataFinal = Date::data(inputPost('dataFinal')) ?: date('Y-m-t');

        $registros = $this->getRegistros($idUser, $dataInicial, $dataFinal);

        $total = [
            'apostas' => 0,
            'valor' => 0,
            'retorno' => 0,
            'comissao' => 0,
            'liquido' => 0,
        ];

        foreach ($registros as &$v) {
            $v['comissao'] += (float)$v['comissaoGerente'];
            $v['liquido'] = $v['valor'] - $v['retorno'] - $v['comissao'];
            $total['valor'] += $v['valor'];
            $total['retorno'] += $v['retorno'];
            $total['apostas'] += $v['apostas'];
            $total['comissao'] += $v['comissao'];
            $total['liquido'] += $v['liquido'];
        }

        $this->view($this->tpl, [
            'registros' => $registros,
            'total' => $total,
        ], 'list');

    }

    function getRegistros(int $idUser, string $dataInicial, string $dataFinal)
    {

        $parans = [
            'user' => $idUser,
            'inicio' => $dataInicial,
            'fim' => $dataFinal,
        ];

        $termos = <<<SQL
SELECT    
    SUM(aposta.comissao) AS comissao,
    
    SUM(IF(aposta.ganhou, aposta.retornovalido, 0)) AS retorno,
    
    SUM(IF(aposta.erros = 0 AND aposta.verificado = 0, aposta.retornovalido, 0)) AS retornoEmAberto,
    
    SUM(aposta.valor) AS valor,
    
    COUNT(*) AS apostas,
    
    IF(user.type = 5, 1, 0) AS isGerente,
    
    (SELECT SUM(c.comissaogerente) AS comissaoGerente FROM `sis_apostas` AS c WHERE c.gerente = aposta.user AND c.status = 1) AS comissaoGerente,
    
    user.nome,
    
    user.id AS userId
    
FROM
    `sis_apostas` AS aposta
    
INNER JOIN
    `sis_users` AS user
        ON user.id = aposta.user AND user.status != 99

WHERE
    aposta.data BETWEEN :inicio AND :fim AND aposta.status = 1
    AND (:user = 0 OR aposta.gerente = :user OR aposta.user = :user)
    
GROUP BY
    aposta.user
    
ORDER BY
    user.nome ASC
SQL;

        return Model::pdoRead()->FullRead($termos, $parans)->getResult();

    }

}