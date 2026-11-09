<?php

namespace app\modules\website2\controllers;

use app\core\Controller;
use app\models\HistoricoModel;
use app\modules\admin\Admin;

class perfilController extends Controller
{

    function indexAction()
    {
        if (Admin::getLogged()) {
            $this->view('website2/page/perfil');
        } else {
            location();
        }
    }

    function atualizarAction()
    {
        try {

            $v = Admin::getLogged();

            # Verificando senha do cadastro
            if ($v->getSenha() != password(inputPost('senha'))) {
                throw new \Exception('Senha incorreta.');
            }

            $v
                ->input('nome')
                ->input('telefone')
                ->input('celular')
                ->input('whatsapp')
                ->input('email')
                ->input('cep')
                ->input('logradouro')
                ->input('numero')
                ->input('complemento')
                ->input('bairro')
                ->input('cidade');

            # Atualizando senha
            if (inputPost('nsenha')) {
                if (inputPost('nsenha') != inputPost('nsenhar')) {
                    throw new \Exception('Senhas incompatíveis.');
                } else {
                    $v->setSenha(inputPost('nsenha'), true);
                }
            }

            # Salvando
            $v->Save();

            # Foto
            $v->imgAddCapa('upfoto', null, true);

            HistoricoModel::add("Alterou os dados do usuário #{$v->getId()}", $v, Admin::getLogged());

            return [
                'message' => 'Dados atualizados com sucesso.',
                'result' => 1,
            ];
        } catch (\Exception $ex) {
            return $ex;
        }
    }

}