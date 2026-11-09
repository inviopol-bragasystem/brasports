<?php

namespace app\modules\website\controllers;

use app\core\Controller;
use app\models\CotacoesModel;
use app\models\helpers\PaginasModel;
use app\models\JogosModel;
use app\models\UsersModel;

class indexController extends Controller
{

    protected $viewTexto = 'website/page/texto';

    function indexAction()
    {
        switch ($ref = url_parans(0)) {
            case 'termos-uso':
            case 'regras':
            case 'perguntas-frequentes':
                $this->view($this->viewTexto, [
                    'pagina' => PaginasModel::getByRef('site-' . $ref),
                ]);
                break;
            default:
                location(url('apostar'));
        }
    }

    function exemploAction()
    {
        $jogo = JogosModel::lista('WHERE a.status = 1 ORDER BY rand() LIMIT 1')[0];
        dd($jogo->toArray(true));
    }

    function listaCotacoesAction()
    {
        $cotacoes = CotacoesModel::getCotacoesAtivas();
        foreach ($cotacoes as $v) {
            echo <<<HTML
<div>
    <h4 style="margin: 0;">{$v->getTitulo()} ({$v->getCampo()})</h4>
    <small>{$v->getDescricao()}</small> 
    <div>{$v->getQuery()}</div>
    <hr />
</div>
HTML;

        }
    }

    function logoutAction()
    {
        UsersModel::logOut();
        location();
    }

    function cotacoesAction()
    {
        $this->view('website/page/cotacoes', [
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