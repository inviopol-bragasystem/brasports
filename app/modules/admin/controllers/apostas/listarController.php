<?php

namespace app\modules\admin\controllers\apostas;

use app\core\Controller;
use app\core\crud\Conn;
use app\helpers\STR;
use app\models\ApostasModel;
use app\models\JogosModel;
use app\models\UsersModel;
use app\modules\admin\Admin;
use app\vo\ApostaVO;

class listarController extends Controller
{

    function indexAction()
    {

        $usuarios = null;

        if (Admin::isMaster()) {
            $usuarios = UsersModel::options([UsersModel::TYPE_CLIENTE, UsersModel::TYPE_GERENTE]);
        }

        $this->view('admin/apostas/listar', [
            'type' => 'default',
            'usuarios' => $usuarios,
        ]);
    }

    function csvAction()
    {

        $parans = inputGet();

        if (!Admin::isMaster()) {
            $parans['gerente'] = Admin::getIdLogged();
        }

        $busca = ApostasModel::busca($parans, 1, 1000);

        header('Content-type: text/csv; charset=UTF-8');
        header("Content-disposition: attachament; filename=aposta.csv");

        echo "cod;data;hora;cambista;apostador;jogos;valor;retorno";

        /** @var ApostaVO $v */
        foreach ($busca->getRegistros() as $v) {
            echo PHP_EOL;
            echo STR::decode("{$v->getId()};{$v->getData()};{$v->getHora()};{$v->getUserNome()};{$v->getApostadorNome()};{$v->getJogos()};{$v->getValor()};{$v->getRetornoValido()}");
        }

    }

    function listAction()
    {

        $parans = inputPost();
        $parans += ['page' => 1, 'forpage' => 20];

        if (!Admin::isMaster()) {
            $parans['gerente'] = Admin::getIdLogged();
        }

        $busca = ApostasModel::busca($parans, $parans['page'], $parans['forpage']);

        $this->view('admin/apostas/listar', [
            'busca' => $busca,
            'parans' => $parans,
        ], 'list');
    }

}
    