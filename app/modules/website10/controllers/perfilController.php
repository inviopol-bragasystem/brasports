<?php

namespace app\modules\website10\controllers;

use app\core\Controller;
use app\models\UsersModel;
use app\modules\admin\Admin;
use Exception;

class perfilController extends Controller
{
    function indexAction()
    {
        if (!Admin::getLogged()) {
            location(url());
        }

        $this->view('website10/page/perfil', [
            'user' => Admin::getLogged(),
        ]);
    }

    function salvarAction()
    {
        try {
            if (!Admin::getLogged()) {
                throw new Exception('Acesso restrito');
            }

            $user = Admin::getLogged();
            $user->save(inputPost());

            return [
                'message' => 'Perfil atualizado com sucesso.',
                'result' => 1,
            ];
        } catch (Exception $ex) {
            return $ex;
        }
    }
}
