<?php

namespace app\modules\website7;

use app\models\DadosModel;
use app\modules\website\Site;

class Site7 extends Site
{
    public function __construct()
    {
        parent::__construct();
        view_vars('layout', 'website7/layout.twig');
    }
}
