<?php

namespace app\modules\website4;

use app\models\DadosModel;
use app\modules\website\Site;

class Site4 extends Site
{

    public function __construct()
    {
        parent::__construct();

        view_vars('layout', 'website4/layout.twig');
    }

}
