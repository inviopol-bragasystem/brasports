<?php

namespace app\modules\website\controllers\financeiro;

use app\core\Controller;
use app\helpers\Number;
use app\helpers\PixUp;
use app\helpers\VeoPag;
use app\models\DadosModel;
use app\models\financeiro\DepositosModel;
use app\models\helpers\ArquivosModel;
use app\modules\admin\Admin;
use Exception;

class depositoController extends seguranca
{

    protected $view = 'website/financeiro/deposito';

    function indexAction()
    {
        $dados = DadosModel::get();
        $gatewayAtivo = $dados->getGatewaypix();
        $this->view($this->view, [
            'modoDeposito' => $dados->getModoDeposito(),
            'gatewayPix' => $gatewayAtivo,
            'pixConfigurado' => $gatewayAtivo === 'veopag' ? VeoPag::isConfigurado() : PixUp::isConfigurado(),
            'depositoMinimo' => $dados->getDepositoMinimo(),
        ]);
    }

    function insertAction()
    {
        try {
            if (empty($_FILES['upfile'])) {
                throw new Exception("Anexe o comprovante de depósito.");
            }

            $valor = (float)str_replace(['.', ','], ['', '.'], inputPost('valor'));
            $deposito = DepositosModel::newValueObject([
                'user' => Admin::getIdLogged(),
                'valor' => $valor,
                'tipo' => 'manual',
                'status' => DepositosModel::STATUS_AGUARDANDO,
                'data' => date('Y-m-d'),
            ])->save();

            ArquivosModel::addArquivo($deposito, 'upfile');

            return [
                'message' => "Depósito manual enviado com sucesso.\nAssim que for confirmado pelo administrador, os créditos serão liberados.",
                'result' => 1,
            ];
        } catch (Exception $ex) {
            return $ex;
        }
    }

    function pixAction()
    {
        try {
            $user = Admin::getLogged();
            if (!$user) {
                throw new Exception("Você precisa estar autenticado para realizar depósitos.");
            }

            $rawValor = inputPost('valor');
            $valor = (float)str_replace(['.', ','], ['', '.'], $rawValor);
            if ($valor <= 0) {
                throw new Exception("Informe um valor válido para o depósito.");
            }

            $dados = DadosModel::get();
            $minimo = (float)$dados->getDepositoMinimo();
            if ($minimo > 0 && $valor < $minimo) {
                throw new Exception("O valor mínimo para depósito é de R$ " . Number::real($minimo));
            }

            $gateway = $dados->getGatewaypix();
            $gatewayConfigurado = $gateway === 'veopag' ? VeoPag::isConfigurado() : PixUp::isConfigurado();
            if (!$gatewayConfigurado) {
                throw new Exception("O gateway de PIX automático ainda não foi ativado ou configurado pelo administrador.");
            }

            // Cria o registro do depósito pendente
            $deposito = DepositosModel::newValueObject([
                'user' => $user->getId(),
                'valor' => $valor,
                'tipo' => $gateway,
                'status' => DepositosModel::STATUS_AGUARDANDO,
                'data' => date('Y-m-d'),
            ])->save();

            // Monta URL de webhook (postback)
            $postbackUrl = url($gateway === 'veopag' ? 'veopag/webhook' : 'pixup/webhook', null, 'api');
            if (!preg_match('/^https?:\/\//i', $postbackUrl)) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
                $postbackUrl = rtrim($protocol . $host, '/') . '/' . ltrim($postbackUrl, '/');
            }

            $payer = [
                'name' => $user->getNome() ?: $user->getLogin(),
                'document' => $user->getCpf() ?: '00000000000',
                'email' => $user->getEmail() ?: 'cliente@email.com',
            ];

            if ($gateway === 'veopag') {
                $veo = VeoPag::createDeposit($valor, $deposito->getToken(), $postbackUrl, $payer);
                $qrcode = $veo['qrcode'];
                $transactionId = $veo['transactionId'];
                $resPix = $veo['response'];
            } else {
                $resPix = PixUp::createQrCode($valor, $deposito->getToken(), $postbackUrl, $payer, "Depósito - " . $dados->getBanca());
                $qrcode = $resPix['qrcode'] ?? '';
                $transactionId = $resPix['transactionId'] ?? '';
            }

            $deposito->setTransacaoId($transactionId);
            $deposito->setQrcode($qrcode);
            $deposito->setPayload(json_encode($resPix));
            $deposito->save();

            return [
                'result' => 1,
                'message' => 'Cobrança PIX gerada com sucesso!',
                'token' => $deposito->getToken(),
                'depositoId' => $deposito->getId(),
                'valor' => $deposito->getValor(true),
                'qrcode' => $qrcode,
                'transacaoId' => $transactionId,
            ];
        } catch (Exception $ex) {
            return $ex;
        }
    }

    function statusPixAction()
    {
        try {
            $token = inputPost('token');
            if (!$token) {
                throw new Exception("Identificador do depósito não informado.");
            }

            $deposito = DepositosModel::getByLabel('token', $token);
            if (!$deposito || $deposito->getUser() != Admin::getIdLogged()) {
                throw new Exception("Depósito não encontrado.");
            }

            $aprovado = ($deposito->getStatus() == DepositosModel::STATUS_APROVADO);
            $user = Admin::getLogged();

            return [
                'result' => 1,
                'status' => (int)$deposito->getStatus(),
                'statusTitle' => $deposito->getStatusTitle(),
                'aprovado' => $aprovado,
                'saldo' => $user ? $user->getCredito(true) : '0,00',
            ];
        } catch (Exception $ex) {
            return $ex;
        }
    }

    function listAction()
    {
        $parans = inputPost() ?: [];
        $parans += ['page' => 1, 'forpage' => 20];
        $parans['user'] = Admin::getIdLogged();
        $busca = DepositosModel::busca($parans, $parans['page'], $parans['forpage']);
        $this->view($this->view, [
            'busca' => $busca,
        ], 'list');
    }

}
