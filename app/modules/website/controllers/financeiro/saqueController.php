<?php

namespace app\modules\website\controllers\financeiro;

use app\models\financeiro\SaquesModel;
use app\models\HistoricoModel;
use app\modules\admin\Admin;
use app\vo\financeiro\SaqueVO;

class saqueController extends seguranca
{

    protected $view = 'website/financeiro/saque';

    function indexAction()
    {
        $this->view($this->view);
    }

    function insertAction()
    {
        try {

            $user = Admin::getLogged();

            /** @var SaqueVO $saque */
            $saque = SaquesModel::newValueObject(inputPost());

            $saque->setUser(Admin::getIdLogged());

            if (inputPost('principal')) {
                $saque
                    ->voUser()
                    ->setDadosBancarios([
                        'tipo' => $saque->getTipo(),
                        'agencia' => $saque->getAgencia(),
                        'conta' => $saque->getConta(),
                        'banco' => $saque->getBanco(),
                        'nomecompleto' => $saque->getNomeCompleto(),
                        'cpf' => $saque->getCpf(),
                        'cnpj' => $saque->getCnpj(),
                        'contatipo' => $saque->getContaTipo(),
                        'variacao' => $saque->getVariacao(),
                    ])->save();
            }

            SaquesModel::addSaque($saque);

            HistoricoModel::add("Solicitou um saque no valor de R$ {$saque->getValor(true)}", $saque, $user);

            return [
                'message' => "Saque solicitado com sucesso.",
                'result' => 1,
            ];
        } catch (\Exception $ex) {
            return $ex;
        }
    }

    function listAction()
    {
        $parans = inputPost() ?: [];
        $parans += ['page' => 1, 'forpage' => 20];
        $parans['user'] = Admin::getIdLogged();
        $busca = SaquesModel::busca($parans, $parans['page'], $parans['forpage']);
        $this->view($this->view, [
            'busca' => $busca,
        ], 'list');
    }

}