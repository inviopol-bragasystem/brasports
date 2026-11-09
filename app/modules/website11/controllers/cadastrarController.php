<?php

namespace app\modules\website11\controllers;

use app\models\UsersModel;
use app\vo\UserVO;

class cadastrarController extends \app\modules\website\controllers\cadastrarController
{
    protected $view = 'website11/page/cadastrar';

    function insertAction()
    {
        try {
            if (!(bool)\app\models\DadosModel::get()->getExibirRegistro()) {
                throw new \Exception('A área de registro está desativada.');
            }

            $senha = inputPost('senha');
            $confirmar = inputPost('confirmar-senha') ?: inputPost('csenha');

            if (empty($senha)) {
                throw new \Exception('Por favor, informe uma senha.');
            }

            if ($senha !== $confirmar) {
                throw new \Exception('A confirmação da senha não confere com a senha digitada.');
            }

            $login = trim((string)(inputPost('login') ?: inputPost('usuario')));
            if (empty($login)) {
                throw new \Exception('Por favor, informe um usuário/login.');
            }

            $nome = trim((string)inputPost('nome'));
            if (empty($nome)) {
                throw new \Exception('Por favor, informe seu nome completo.');
            }

            $cpf = trim((string)inputPost('cpf'));
            if (empty($cpf)) {
                throw new \Exception('Por favor, informe seu CPF.');
            }

            $email = trim((string)inputPost('email'));
            if (empty($email)) {
                throw new \Exception('Por favor, informe seu e-mail.');
            }

            $celular = trim((string)(inputPost('celular') ?: inputPost('telefone')));
            if (empty($celular)) {
                throw new \Exception('Por favor, informe seu celular/telefone.');
            }

            $cidade = (int)inputPost('cidade');
            if (empty($cidade)) {
                // Se nao veio cidade (ex: cadastro modal), usa cidade padrao valida do banco
                $cidade = 1;
            }

            $nascimento = trim((string)inputPost('nascimento'));
            if (empty($nascimento)) {
                // Idade minima 18 anos
                $nascimento = '2000-01-01';
            }

            $cep = trim((string)inputPost('cep')) ?: '01001-000';
            $sexo = trim((string)inputPost('sexo')) ?: 'm';

            /** @var UserVO $user */
            $user = UsersModel::newValueObject();

            $user
                ->setType(UsersModel::TYPE_CLIENTE)
                ->setNome($nome)
                ->setCpf($cpf)
                ->setSexo($sexo)
                ->setNascimento($nascimento)
                ->setCep($cep)
                ->setLogradouro(inputPost('logradouro') ?: 'Centro')
                ->setNumero(inputPost('numero') ?: 'S/N')
                ->setBairro(inputPost('bairro') ?: 'Centro')
                ->setCidade($cidade)
                ->setTelefone($celular)
                ->setCelular($celular)
                ->setWhatsapp(inputPost('whatsapp') ?: $celular)
                ->setEmail($email)
                ->setLogin($login)
                ->setSenha($senha)
                ->save();

            UsersModel::setLogged($user);

            return [
                'message' => 'Cadastro realizado com sucesso!',
                'url' => url(),
                'result' => 1,
            ];
        } catch (\Exception $ex) {
            return $ex;
        }
    }
}
