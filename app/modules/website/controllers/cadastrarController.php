<?php

namespace app\modules\website\controllers;

use app\core\Controller;
use app\helpers\Seo;
use app\models\financeiro\SaquesModel;
use app\models\UsersModel;
use app\vo\UserVO;

class cadastrarController extends Controller
{

    protected $view = 'website/page/cadastrar';

    /**
     * Verifica se a área de registro está visível
     * @return bool
     */
    private function registroVisivel()
    {
        return (bool)\app\models\DadosModel::get()->getExibirRegistro();
    }

    function indexAction()
    {
        if (!$this->registroVisivel()) {
            location(url());
        }

        Seo::setTitle('Cadastre-se');
        $this->view($this->view, [
            'bancos' => SaquesModel::optionsBancos(),
        ]);
    }

    function insertAction()
    {
        try {

            if (!$this->registroVisivel()) {
                throw new \Exception('A área de registro está desativada.');
            }

            $senha = inputPost('senha');
            $confirmar = inputPost('confirmar-senha');

            if (empty($senha)) {
                throw new \Exception('Por favor, informe uma senha.');
            }

            if ($senha !== $confirmar) {
                throw new \Exception('A confirmação da senha não confere com a senha digitada.');
            }

            /** @var UserVO $user */
            $user = UsersModel::newValueObject();

            $user
                ->setType(UsersModel::TYPE_CLIENTE)
                ->input('nome')
                ->input('cpf')
                ->input('sexo')
                ->input('nascimento')
                ->input('cep')
                ->input('logradouro')
                ->input('numero')
                ->input('bairro')
                ->input('cidade')
                ->input('telefone')
                ->input('celular')
                ->input('whatsapp')
                ->input('email')
                ->input('login')
                ->input('senha')
                ->save();

            UsersModel::setLogged($user);

            return [
                'message' => 'Cadastro realizado com sucesso',
                'result' => 1,
            ];
        } catch (\Exception $ex) {
            return $ex;
        }
    }

}