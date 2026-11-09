<?php

namespace app\modules\website12\controllers;

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

        $this->view('website12/page/perfil', [
            'user' => Admin::getLogged(),
        ]);
    }

    function salvarAction()
    {
        try {
            $user = Admin::getLogged();
            if (!$user) {
                throw new Exception('Acesso restrito');
            }

            if ($user->getSenha() != password(inputPost('senha'))) {
                throw new Exception('Senha atual incorreta.');
            }

            $user
                ->input('nome')
                ->input('telefone')
                ->input('celular')
                ->input('whatsapp')
                ->input('email');

            if (inputPost('nsenha')) {
                if (inputPost('nsenha') != inputPost('nsenhar')) {
                    throw new Exception('A confirmação da nova senha não confere.');
                }
                $user->setSenha(inputPost('nsenha'));
            }

            $user->save();

            return [
                'message' => 'Perfil atualizado com sucesso.',
                'result' => 1,
            ];
        } catch (Exception $ex) {
            return $ex;
        }
    }

    function atualizarAction()
    {
        return $this->salvarAction();
    }
}

