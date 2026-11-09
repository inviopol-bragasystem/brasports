<?php

namespace app\modules\api\controllers;

use app\core\Controller;
use app\models\financeiro\DepositosModel;
use app\models\HistoricoBancarioModel;
use app\models\HistoricoModel;
use app\vo\financeiro\DepositoVO;
use Exception;

class pixupController extends Controller
{

    /**
     * Webhook receptor de notificações de pagamento da PixUp
     * URL: http://dominio/api/pixup/webhook
     */
    public function webhookAction()
    {
        try {
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            if (!$data) {
                $data = $_POST;
            }

            // Normaliza o payload (aceita com ou sem encapsulamento requestBody)
            $body = $data['requestBody'] ?? $data;

            $status = strtoupper(trim($body['status'] ?? ''));
            $transactionId = trim($body['transactionId'] ?? '');
            $externalId = trim($body['external_id'] ?? '');
            $amount = (float)($body['amount'] ?? 0);

            if (empty($externalId) && empty($transactionId)) {
                return [
                    'result' => 0,
                    'message' => 'Nenhum identificador de transação informado.',
                ];
            }

            // Busca o depósito pelo token, ID ou transactionId
            $deposito = null;
            if (!empty($externalId)) {
                $deposito = DepositosModel::getByLabel('token', $externalId);
                if (!$deposito && is_numeric($externalId)) {
                    $deposito = DepositosModel::get((int)$externalId);
                }
            }
            if (!$deposito && !empty($transactionId)) {
                $deposito = DepositosModel::getByLabel('transacaoid', $transactionId);
            }

            if (!$deposito instanceof DepositoVO) {
                return [
                    'result' => 0,
                    'message' => 'Depósito não localizado.',
                    'external_id' => $externalId,
                    'transactionId' => $transactionId,
                ];
            }

            // Se o pagamento foi confirmado
            if ($status === 'PAID') {
                if ($deposito->getStatus() != DepositosModel::STATUS_APROVADO) {
                    $deposito->setStatus(DepositosModel::STATUS_APROVADO);
                    if ($transactionId) {
                        $deposito->setTransacaoId($transactionId);
                    }
                    $deposito->setPayload($rawInput);
                    $deposito->save();

                    // Credita saldo na conta do usuário
                    $user = $deposito->voUser();
                    if ($user) {
                        $valorCredito = (float)$deposito->getValor();
                        HistoricoBancarioModel::add(
                            $user,
                            $valorCredito,
                            "<b>DEPÓSITO PIX</b> - Pagamento aprovado automaticamente via PixUp (#{$deposito->getId()})",
                            $deposito,
                            "deposito"
                        );
                        HistoricoModel::add(
                            "Depósito PIX #{$deposito->getId()} no valor de R$ {$deposito->getValor(true)} aprovado automaticamente",
                            $deposito,
                            $user
                        );
                    }
                }

                return [
                    'result' => 1,
                    'message' => 'Pagamento aprovado e creditado com sucesso.',
                ];
            }

            return [
                'result' => 1,
                'message' => "Status {$status} registrado.",
            ];

        } catch (Exception $ex) {
            return [
                'result' => 0,
                'message' => $ex->getMessage(),
            ];
        }
    }

}
