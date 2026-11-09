<?php

namespace app\modules\admin\controllers\config;

use app\helpers\SMail;
use app\models\HistoricoModel;
use app\models\SmtpModel;
use app\modules\admin\Admin;
use app\vo\admin\MenuVO;
use app\vo\SmtpVO;

class smtpController extends \app\core\Controller
{

    function indexAction(MenuVO $Pagina)
    {
        $this->view('admin/configuracoes/smtp', [
            'smtp' => SmtpModel::getConfig(),
        ]);
    }

    /** @return SmtpModel */
    function getModel()
    {
        return SmtpModel::Instance();
    }

    function save(SmtpVO $v)
    {
        try {

            if (!Admin::isMaster()) {
                throw new \Exception("Somente administradores");
            } else if (!$v->getId()) {
                throw new \Exception('Configurações inválidas.');
            }

            $v->save(inputPost());

            $mail = new SMail;

            $mail->addAddress($mail->getConfig()->getNome(), $mail->getConfig()->getEmail());
            $mail->setContent("Testando configurações SMTP", "Configurações validadas com sucesso :D");
            $mail->Send();

            HistoricoModel::add("Alterou as configurações de SMTP do sistema", $v, Admin::getLogged());

            json('Configurações salvas com sucesso.', 1);
        } catch (\Exception $ex) {
            return $ex;
        }
    }

}
    