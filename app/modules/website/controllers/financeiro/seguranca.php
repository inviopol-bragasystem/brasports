<?php


namespace app\modules\website\controllers\financeiro;


use app\core\Controller;
use app\modules\admin\Admin;

class seguranca extends Controller
{

    public function __construct()
    {
        if (!Admin::getLogged()) {
            if (IS_POST) {
                exit('É necessário que esteja logado');
            } else {
                location();
            }
        }
    }

}