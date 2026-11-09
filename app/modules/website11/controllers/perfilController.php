<?php

namespace app\modules\website11\controllers;

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

        $this->view('website11/page/perfil', [
            'user' => Admin::getLogged(),
        ]);
    }

    /**
     * Salva os dados do perfil do usuário logado.
     *
     * O template do tema envia o formulário para `perfil/salvar`
     * (o site9 enviava `perfil/atualizar`, que não existe e "parecia"
     * salvar porque o notfoundAction responde result=1 em AJAX).
     *
     * Só altera os campos permitidos: nome, telefone, celular, whatsapp
     * e e-mail. A senha atual é obrigatória para confirmar a alteração e
     * a nova senha é aplicada via setSenha() apenas quando preenchida.
     */
    function salvarAction()
    {
        try {
            $user = Admin::getLogged();
            if (!$user) {
                throw new Exception('Acesso restrito');
            }

            # Confirmação da senha atual
            if ($user->getSenha() != password(inputPost('senha'))) {
                throw new Exception('Senha atual incorreta.');
            }

            $user
                ->input('nome')
                ->input('telefone')
                ->input('celular')
                ->input('whatsapp')
                ->input('email');

            # Nova senha (opcional)
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

    /**
     * Compatibilidade: aceita também `/perfil/atualizar`
     * (endpoint usado pelo perfil dos temas anteriores).
     */
    function atualizarAction()
    {
        return $this->salvarAction();
    }
}
