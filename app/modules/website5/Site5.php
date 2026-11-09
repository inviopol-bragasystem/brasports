<?php

namespace app\modules\website5;

use app\models\DadosModel;
use app\modules\website\Site;

class Site5 extends Site
{
    public function __construct()
    {
        parent::__construct();
        view_vars('layout', 'website5/layout.twig');
    }
}
