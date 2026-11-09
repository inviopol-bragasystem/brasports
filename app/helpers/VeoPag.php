<?php

namespace app\helpers;

use app\models\DadosModel;
use Exception;

class VeoPag
{
    const BASE_URL = 'https://api.veopag.co';
    const TOKEN_TTL = 3300;

    public static function getConfig()
    {
        $dados = DadosModel::get();
        return [
            'client_id' => trim((string)$dados->getVeopagClientId()),
            'client_secret' => trim((string)$dados->getVeopagClientSecret()),
            'webhook_secret' => trim((string)$dados->getVeopagWebhookSecret()),
            'webhook_token' => trim((string)$dados->getVeopagWebhookToken()),
            'status' => $dados->getVeopagStatus(),
        ];
    }

    public static function isConfigurado()
    {
        $cfg = self::getConfig();
        return $cfg['status'] && $cfg['client_id'] !== '' && $cfg['client_secret'] !== ''
            && ($cfg['webhook_secret'] !== '' || $cfg['webhook_token'] !== '');
    }

    private static function request($path, array $body = null, $token = null)
    {
        $url = self::BASE_URL . $path;
        $ch = curl_init($url);
        $headers = ['Accept: application/json', 'Content-Type: application/json'];
        if ($token) $headers[] = 'Authorization: Bearer ' . $token;
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $body === null ? 'GET' : 'POST',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) throw new Exception('Falha de conexão com a VeoPag: ' . $error);
        $data = json_decode((string)$response, true);
        if (!is_array($data)) $data = [];
        if ($httpCode < 200 || $httpCode >= 300) {
            $message = $data['message'] ?? $data['error'] ?? ('HTTP ' . $httpCode);
            throw new Exception('Erro VeoPag: ' . $message);
        }
        return [$httpCode, $data];
    }

    private static function tokenFile(array $cfg)
    {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'veopag_token_' . hash('sha256', $cfg['client_id']) . '.json';
    }

    public static function getToken($forceRefresh = false)
    {
        $cfg = self::getConfig();
        if (!$cfg['client_id'] || !$cfg['client_secret']) throw new Exception('Credenciais VeoPag não configuradas no painel.');
        $file = self::tokenFile($cfg);
        $lock = fopen($file . '.lock', 'c');
        if ($lock) flock($lock, LOCK_EX);
        try {
            if (!$forceRefresh && is_file($file)) {
                $cached = json_decode((string)file_get_contents($file), true);
                if (!empty($cached['token']) && !empty($cached['expires']) && $cached['expires'] > time()) return $cached['token'];
            }
            [, $data] = self::request('/api/auth/login', ['client_id' => $cfg['client_id'], 'client_secret' => $cfg['client_secret']]);
            if (empty($data['token'])) throw new Exception('A VeoPag não retornou o token de autenticação.');
            file_put_contents($file, json_encode(['token' => $data['token'], 'expires' => time() + self::TOKEN_TTL]), LOCK_EX);
            @chmod($file, 0600);
            return $data['token'];
        } finally {
            if ($lock) { flock($lock, LOCK_UN); fclose($lock); }
        }
    }

    public static function createDeposit($amount, $externalId, $callbackUrl, array $payer)
    {
        $body = [
            'amount' => round((float)$amount, 2),
            'external_id' => (string)$externalId,
            'clientCallbackUrl' => (string)$callbackUrl,
            'payer' => [
                'name' => substr((string)($payer['name'] ?? 'Cliente'), 0, 100),
                'email' => (string)($payer['email'] ?? 'cliente@email.com'),
                'document' => preg_replace('/\D/', '', (string)($payer['document'] ?? '')) ?: '00000000000',
            ],
        ];
        try {
            [, $data] = self::request('/api/payments/deposit', $body, self::getToken());
        } catch (Exception $ex) {
            $message = strtolower($ex->getMessage());
            if (strpos($message, '401') === false && strpos($message, 'unauthorized') === false && strpos($message, 'unauthenticated') === false && strpos($message, 'invalid token') === false) throw $ex;
            [, $data] = self::request('/api/payments/deposit', $body, self::getToken(true));
        }
        $payment = $data['qrCodeResponse'] ?? $data;
        $transactionId = $payment['transactionId'] ?? $payment['transaction_id'] ?? $data['transaction_id'] ?? '';
        $qrcode = $payment['qrcode'] ?? $data['qrcode'] ?? '';
        if (!$transactionId || !$qrcode) throw new Exception('Resposta da VeoPag incompleta: QR Code ou identificador ausente.');
        return ['transactionId' => $transactionId, 'qrcode' => $qrcode, 'response' => $data];
    }
}
