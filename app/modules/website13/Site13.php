<?php

namespace app\modules\website13;

use app\models\DadosModel;
use app\modules\website\Site;

class Site13 extends Site
{
    public function __construct()
    {
        parent::__construct();
        view_vars('layout', 'website13/layout.twig');
    }
}

