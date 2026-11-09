<?php

namespace app\modules\website14;

use app\models\DadosModel;
use app\modules\website\Site;

class Site14 extends Site
{
    public function __construct()
    {
        parent::__construct();
        view_vars('layout', 'website14/layout.twig');
    }
}

