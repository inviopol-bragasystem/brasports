<?php

namespace app\modules\website2;

use app\helpers\Seo;
use app\models\DadosModel;
use app\modules\admin\Admin;
use app\modules\website\Site;

class Site2 extends Site
{

    public function __construct()
    {

        parent::__construct();

        view_vars('layout', 'website2/layout.twig');

    }

}