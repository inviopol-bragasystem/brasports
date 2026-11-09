<?php

namespace app\modules\website9;

use app\models\DadosModel;
use app\modules\website\Site;

class Site9 extends Site
{
    public function __construct()
    {
        parent::__construct();
        view_vars('layout', 'website9/layout.twig');
    }
}
