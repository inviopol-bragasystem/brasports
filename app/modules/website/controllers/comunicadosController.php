<?php

namespace app\modules\website\controllers;

use app\core\Controller;
use app\models\ComunicadosModel;
use app\modules\admin\Admin;

class comunicadosController extends Controller
{

    protected $view = 'website/page/comunicados';

    function indexAction()
    {
        $this->view($this->view, [
            'comunicados' => ComunicadosModel::getComunicados(Admin::getLogged()),
        ]);
    }

}