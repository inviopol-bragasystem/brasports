<?php

namespace app\modules\website14\controllers;

use app\core\Controller;
use app\models\DadosModel;

class cassinoController extends \app\modules\website\controllers\cassinoController
{
    protected $tpl = 'website14/page/cassino';

    function indexAction()
    {
        $dados = DadosModel::get();
        if (!$dados->getExibirCassino()) {
            location(url("apostar"));
        }
        parent::indexAction();
    }
}

