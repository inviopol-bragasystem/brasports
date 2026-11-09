<?php

namespace app\modules\website;

use app\helpers\Seo;
use app\models\DadosModel;
use app\modules\admin\Admin;

class Site
{

    public function __construct()
    {

        $dados = DadosModel::get();

        Seo::setTitle($dados->getBanca());

        view_vars('responsive', true);
        view_vars('dados', $dados);
        view_vars('layout', 'website/layout.twig');
        view_vars('user', Admin::getLogged());
        view_vars('isMaster', Admin::isMaster());
        view_vars('isDev', Admin::isDeveloper());
        view_vars('controller', \app\APP::getControllerName());

    }

}