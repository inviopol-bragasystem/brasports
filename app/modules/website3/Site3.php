<?php


namespace app\modules\website3;


use app\models\DadosModel;
use app\models\UsersModel;
use app\modules\website2\Site2;

class Site3 extends Site2
{

    public function __construct()
    {
        parent::__construct();

        view_vars('layout', 'website3/layout.twig');

    }

}