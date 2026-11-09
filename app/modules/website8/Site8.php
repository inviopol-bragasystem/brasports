<?php

namespace app\modules\website8;

use app\models\DadosModel;
use app\modules\website\Site;

class Site8 extends Site
{
    public function __construct()
    {
        parent::__construct();
        view_vars('layout', 'website8/layout.twig');
    }
}
