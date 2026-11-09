<?php

namespace app\modules\website11;

use app\models\DadosModel;
use app\modules\website\Site;

class Site11 extends Site
{
    public function __construct()
    {
        parent::__construct();
        view_vars('layout', 'website11/layout.twig');
    }
}
