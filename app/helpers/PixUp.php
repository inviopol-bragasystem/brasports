<?php

namespace app\helpers;

use app\models\DadosModel;
use Exception;

class PixUp
{

    const BASE_URL = 'https://api.pixupbr.com';

    /**
     * Obtém as configurações do PixUp no sistema
     * @return array
     */
    public static function getConfig()
    {
        $dados = DadosModel::get();
        return [
            'client_id' => trim($dados->getPixupClientId() ?? ''),
            'client_secret' => trim($dados->getPixupClientSecret() ?? ''),
            'status' => $dados->getPixupStatus(),
            'modo_deposito' => $dados->getModoDeposito(),
        ];
    }

    /**
     * Verifica se o PixUp está devidamente configurado e ativo
     * @return bool
     */
    public static function isConfigurado()
    {
        $cfg = self::getConfig();
        return !empty($cfg['client_id']) && !empty($cfg['client_secret']) && !empty($cfg['status']);
    }

    /**
     * Obtém o Token de Acesso OAuth da PixUp (com cache em sessão ou arquivo)
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @return string
     * @throws Exception
     */
    public static function getToken($clientId = null, $clientSecret = null)
    {
        if (!$clientId || !$clientSecret) {
            $cfg = self::getConfig();
            $clientId = $clientId ?: $cfg['client_id'];
            $clientSecret = $clientSecret ?: $cfg['client_secret'];
        }

        if (empty($clientId) || empty($clientSecret)) {
            throw new Exception("Credenciais do gateway PixUp (Client ID e Client Secret) não configuradas no painel.");
        }

        $credentials = base64_encode("{$clientId}:{$clientSecret}");

        $ch = curl_init(self::BASE_URL . '/v2/oauth/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Basic {$credentials}",
            "Content-Type: application/json",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("Falha de conexão com a PixUp: {$curlError}");
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200 || empty($data['access_token'])) {
            $msg = $data['message'] ?? $data['error'] ?? "Código HTTP {$httpCode}";
            throw new Exception("Erro de autenticação PixUp: {$msg}");
        }

        return $data['access_token'];
    }

    /**
     * Cria uma cobrança PIX com QRCode na PixUp
     * @param float $valor
     * @param string $externalId
     * @param string $postbackUrl
     * @param array $payer
     * @param string $descricao
     * @return array
     * @throws Exception
     */
    public static function createQrCode($valor, $externalId, $postbackUrl, array $payer = [], $descricao = "Depósito")
    {
        $token = self::getToken();

        // Limpa e formata documento (apenas dígitos)
        $documento = preg_replace('/\D/', '', $payer['document'] ?? '');
        if (strlen($documento) < 11) {
            $documento = '00000000000';
        }

        $body = [
            'amount' => (float)$valor,
            'external_id' => (string)$externalId,
            'postbackUrl' => (string)$postbackUrl,
            'payerQuestion' => (string)substr($descricao, 0, 50),
            'payer' => [
                'name' => substr($payer['name'] ?? 'Cliente', 0, 100),
                'document' => $documento,
                'email' => $payer['email'] ?? 'cliente@email.com',
            ]
        ];

        $ch = curl_init(self::BASE_URL . '/v2/pix/qrcode');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$token}",
            "Content-Type: application/json",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("Falha ao se comunicar com a PixUp: {$curlError}");
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200 && $httpCode !== 201) {
            $msg = $data['message'] ?? $data['error'] ?? "Código HTTP {$httpCode}";
            throw new Exception("Erro ao gerar QRCode PixUp: {$msg}");
        }

        return $data;
    }

}
