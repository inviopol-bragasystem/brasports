<?php

namespace app\modules\admin\controllers\fluxo;

use app\core\Controller;
use app\helpers\BuildQuery;
use app\helpers\Date;
use app\models\ApostasModel;
use app\models\UsersModel;
use app\vo\admin\MenuVO;

class apostasController extends Controller
{

    function indexAction(MenuVO $menu)
    {
        $this->view('admin/fluxo/apostas', [
            'usuarios' => UsersModel::options([UsersModel::TYPE_GERENTE]),
            'places' => [
                'dataInicial' => date('Y-m-01'),
                'dataFinal' => date('Y-m-t'),
            ],
        ]);
    }

    function listAction()
    {
        $query = new BuildQuery(ApostasModel::getTable(), 'a');

        $query->setSelectFields([
            'user.nome AS nome',
            'COUNT(*) AS qtdeApostas',
            'SUM(a.valor) AS valorApostado',
            'SUM(a.comissao) AS comissao',
            'SUM(a.comissaogerente) AS comissaoGerente',
        ]);

        $query->addInnerJoin(UsersModel::getTable(), 'user', 'user.id = a.user');

        $query->setGroup(['a.user']);

        $query->setWhere("a.status != 99 AND (:gerente = 0 OR user.user = :gerente) AND (:dataInicial IS NULL OR a.data >= :dataInicial) AND (:dataFinal IS NULL OR a.data <= :dataFinal)");

        $places = [
            'gerente' => inputPost('gerente') ?: 0,
            'dataInicial' => Date::data(inputPost('dataInicial')),
            'dataFinal' => Date::data(inputPost('dataFinal')),
        ];

        $registros = $query->execute($places);

        $this->view('admin/fluxo/apostas', [
            'registros' => $registros,
        ], 'list');
    }

}