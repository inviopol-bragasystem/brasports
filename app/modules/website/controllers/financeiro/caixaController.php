<?php


namespace app\modules\website\controllers\financeiro;


use app\modules\admin\Admin;
use app\vo\UserVO;

class caixaController extends seguranca
{

    function indexAction()
    {

        $user = Admin::getLogged();

        if ($user)
            if (!empty($this->tpl))
                $this->view($this->tpl, [
                    'meses' => \app\modules\api\controllers\caixaController::instance()->mesesAction($user)['meses'],
                ]);

    }

    function listAction()
    {

        $user = Admin::getLogged();

        if ($user instanceof UserVO) {

            $relatorio = \app\modules\api\controllers\caixaController::instance()->indexAction($user);

            $this->view($this->tpl, [
                'relatorio' => $relatorio,
            ], 'list');

        }

    }

}