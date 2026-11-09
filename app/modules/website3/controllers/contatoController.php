<?php

namespace app\modules\website3\controllers;

use app\core\Controller;
use app\helpers\SMail;

class contatoController extends Controller
{

    function indexAction()
    {
        $this->view('website3/page/contato', [
            'title' => 'Fale Conosco',
        ]);
    }

    function insertAction()
    {
        try {

            $mail = new SMail();

            $mail->addAddress($mail->getConfig()->getNome(), $mail->getConfig()->getEmail());
            $mail->setFrom(inputPost('nome'), inputPost('email'));

            $mail->setContent(inputPost('assunto'), $this->view('website3/tpl/contato', [
                'nome' => inputPost('nome'),
                'assunto' => inputPost('assunto'),
                'email' => inputPost('email'),
                'telefone' => inputPost('telefone'),
                'mensagem' => nl2br(inputPost('mensagem')),
            ], null, true));

            $mail->Send();

            return [
                'message' => 'E-mail enviado com sucesso',
                'result' => 1,
            ];
        } catch (\Exception $ex) {
            return $ex;
        }
    }

    function listAction()
    {
        exit;
    }

}