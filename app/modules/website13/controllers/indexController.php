<?php

namespace app\modules\website13\controllers;

use app\core\Controller;
use app\models\CotacoesModel;
use app\models\DadosModel;
use app\models\helpers\PaginasModel;
use app\models\UsersModel;

class indexController extends Controller
{
    function indexAction()
    {
        switch ($ref = url_parans(0)) {
            case 'termos-uso':
            case 'regras':
            case 'perguntas-frequentes':
                $this->view('website13/page/texto', [
                    'pagina' => PaginasModel::getByRef('site-' . $ref),
                ]);
                break;
            default:
                $dados = DadosModel::get();
                if ($dados->getExibirCassino()) {
                    location(url("cassino"));
                } else {
                    location(url("apostar"));
                }
        }
    }

    function logoutAction()
    {
        UsersModel::logOut();
        location();
    }

    function cotacoesAction()
    {
        $this->view('website13/page/cotacoes', [
            'grupos' => CotacoesModel::GRUPOS,
            'cotacoes' => CotacoesModel::getCotacoesAtivas(),
        ]);
    }

    function twigAction()
    {
        $this->viewCacheClean();
        location(url_referer());
    }
}

