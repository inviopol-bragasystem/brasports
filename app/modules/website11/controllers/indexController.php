<?php

namespace app\modules\website11\controllers;

use app\core\Controller;
use app\models\CotacoesModel;
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
                $this->view('website11/page/texto', [
                    'pagina' => PaginasModel::getByRef('site-' . $ref),
                ]);
                break;
            default:
                location(url("apostar"));
        }
    }

    function logoutAction()
    {
        UsersModel::logOut();
        location();
    }

    function cotacoesAction()
    {
        $this->view('website11/page/cotacoes', [
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
