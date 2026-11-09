<?php

namespace app\modules\website2\controllers;

use app\core\Controller;

class comunicadosController extends Controller
{

    protected $view = 'website2/page/comunicados';

    function indexAction()
    {
        $this->view($this->view);
    }

}