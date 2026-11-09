<?php

namespace app\modules\api\controllers;

use app\core\Controller;
use app\core\crud\Conn;
use app\helpers\VeoPag;
use app\models\financeiro\DepositosModel;
use app\models\HistoricoBancarioModel;
use app\models\HistoricoModel;
use app\vo\financeiro\DepositoVO;
use Exception;

class veopagController extends Controller
{
    /** Endpoint para cadastrar no portal VeoPag: https://seu-dominio/api/veopag/webhook */
    public function webhookAction()
    {
        $pdo = null;
        $raw = file_get_contents('php://input');
        $cfg = VeoPag::getConfig();
        $secret = $cfg['webhook_secret'];
        $callbackToken = $cfg['webhook_token'];
        $signature = trim($_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '');
        $timestamp = trim($_SERVER['HTTP_X_WEBHOOK_TIMESTAMP'] ?? '');
        $authorization = trim($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if (!$authorization && function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) if (strtolower($name) === 'authorization') $authorization = trim($value);
        }

        if (!$secret && !$callbackToken) return $this->reject(503, 'Configure a assinatura ou token do webhook VeoPag no painel.');
        if ($callbackToken && !hash_equals('Bearer ' . $callbackToken, $authorization)) return $this->reject(401, 'Token do webhook inválido.');
        if ($secret) {
            if (!$timestamp || !ctype_digit($timestamp) || abs(time() - (int)$timestamp) > 300 || !$signature) return $this->reject(401, 'Assinatura ou timestamp inválido.');
            $expected = hash_hmac('sha256', $timestamp . '.' . $raw, $secret);
            if (!hash_equals($expected, strtolower($signature))) return $this->reject(401, 'Assinatura VeoPag inválida.');
        }

        try {
            $data = json_decode($raw, true);
            if (!is_array($data)) return $this->reject(400, 'Payload JSON inválido.');
            if (strtolower((string)($data['type'] ?? '')) !== 'deposit') return $this->reject(400, 'Evento não é um depósito.');
            $externalId = trim((string)($data['external_id'] ?? ''));
            $transactionId = trim((string)($data['transaction_id'] ?? ''));
            if (!$externalId || !$transactionId) return $this->reject(400, 'Identificadores do depósito ausentes.');

            $deposito = DepositosModel::getByLabel('token', $externalId);
            if (!$deposito instanceof DepositoVO || $deposito->getTipo() !== 'veopag') return $this->reject(404, 'Depósito VeoPag não localizado.');
            if ($deposito->getTransacaoId() && !hash_equals((string)$deposito->getTransacaoId(), $transactionId)) return $this->reject(409, 'Identificador de transação não corresponde ao depósito.');

            $status = strtoupper(trim((string)($data['status'] ?? '')));
            if ($status === 'COMPLETED') {
                $currency = strtoupper((string)($data['currency'] ?? 'BRL'));
                if ($currency !== 'BRL') return $this->reject(400, 'Moeda de pagamento incompatível.');
                $paidAmount = round((float)($data['amount'] ?? 0), 2);
                if ($paidAmount <= 0 || (int)round($paidAmount * 100) !== (int)round((float)$deposito->getValor() * 100)) return $this->reject(409, 'Valor pago não corresponde ao depósito.');

                if ($deposito->getStatus() != DepositosModel::STATUS_APROVADO) {
                    $pdo = Conn::getConn();
                    $pdo->beginTransaction();
                    $lock = $pdo->prepare('SELECT `status` FROM `sis_depositos` WHERE `id` = :id FOR UPDATE');
                    $lock->execute(['id' => $deposito->getId()]);
                    $lockedStatus = $lock->fetchColumn();
                    if ($lockedStatus === false) { $pdo->rollBack(); return $this->reject(404, 'Depósito não localizado.'); }
                    if ((int)$lockedStatus === DepositosModel::STATUS_APROVADO) {
                        $pdo->commit();
                        http_response_code(200);
                        return ['result' => 1, 'message' => 'Pagamento já processado.'];
                    }
                    $user = $deposito->voUser();
                    if (!$user) { $pdo->rollBack(); return $this->reject(404, 'Usuário do depósito não localizado.'); }
                    $deposito->setStatus(DepositosModel::STATUS_APROVADO);
                    $deposito->setTransacaoId($transactionId);
                    $deposito->setPayload($raw);
                    $deposito->save();
                    HistoricoBancarioModel::add($user, (float)$deposito->getValor(), "<b>DEPÓSITO PIX</b> - Pagamento aprovado automaticamente via VeoPag (#{$deposito->getId()})", $deposito, 'deposito');
                    HistoricoModel::add("Depósito PIX #{$deposito->getId()} no valor de R$ {$deposito->getValor(true)} aprovado automaticamente via VeoPag", $deposito, $user);
                    $pdo->commit();
                }
                http_response_code(200);
                return ['result' => 1, 'message' => 'Pagamento aprovado e creditado.'];
            }

            if ($status === 'FAILED' && $deposito->getStatus() == DepositosModel::STATUS_AGUARDANDO) {
                $deposito->setStatus(DepositosModel::STATUS_DESAPROVADO);
                $deposito->setTransacaoId($transactionId);
                $deposito->setPayload($raw);
                $deposito->save();
            }
            http_response_code(200);
            return ['result' => 1, 'message' => 'Status ' . $status . ' registrado.'];
        } catch (Exception $ex) {
            if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
            return $this->reject(500, $ex->getMessage());
        }
    }

    private function reject($httpCode, $message)
    {
        http_response_code($httpCode);
        return ['result' => 0, 'message' => $message];
    }
}
