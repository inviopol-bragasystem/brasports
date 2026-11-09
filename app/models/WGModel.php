<?php
/**
 * WG Model - Integração com API WG
 */

namespace app\models;

use app\core\crud\Conn;
use PDO;

class WGModel
{
    /**
     * Lança um jogo WG via API
     */
    public static function launchGame($user, array $game): array
    {
        $settings = self::getSettings();
        if (!$settings['active']) {
            return [
                'ok' => false,
                'message' => 'O cassino WG está temporariamente em manutenção.'
            ];
        }

        $apiUrl = rtrim($settings['api_url'] ?? '', '/');
        $agentToken = $settings['agent_token'] ?? '';
        $secretKey = $settings['secret_key'] ?? '';
        $userCode = $user ? (string)$user->getId() : ('demo_' . (session_id() ? substr(session_id(), 0, 8) : bin2hex(random_bytes(4))));
        $userBalance = $user ? (float)$user->getCredito() : 1000.00;

        $payload = [
            'gameId' => $game['wg_game_id'] ?? $game['game_id'],
            'name' => $game['game_code'],
            'balance' => round($userBalance, 2),
            'currency' => 'BRL',
        ];

        if ($user) {
            $payload['user_id'] = $userCode;
            $payload['agent_code'] = $agentToken;
            $payload['agent_token'] = $secretKey;
        }

        // Endpoints da API WG
        $targets = [
            'http://127.0.0.1:3000/api/launch',  // Local WG API
        ];
        
        if (!empty($apiUrl) && $apiUrl !== 'http://127.0.0.1:3000') {
            $targets[] = $apiUrl . '/api/launch';
        }

        if (function_exists('curl_init') && !empty($agentToken) && !empty($secretKey)) {
            foreach ($targets as $url) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                curl_setopt($ch, CURLOPT_TIMEOUT, 8);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

                $body = curl_exec($ch);
                $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($body && $httpStatus >= 200 && $httpStatus < 300) {
                    $res = json_decode($body, true);
                    if (is_array($res) && isset($res['launch_url']) && !empty($res['launch_url'])) {
                        return [
                            'ok' => true,
                            'url' => $res['launch_url'],
                            'mode' => 'wg_api'
                        ];
                    }
                }
            }
        }

        // Fallback: abre o jogo local se tiver arquivos
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = rtrim($protocol . $host, '/');
        
        $gamePath = $game['game_path'] ?? '/games/WG/public/' . $game['game_code'];
        $launchUrl = "{$baseUrl}{$gamePath}/index.html";

        return [
            'ok' => true,
            'url' => $launchUrl,
            'mode' => 'wg_local'
        ];
    }

    /**
     * Configurações WG
     */
    public static function getSettings(): array
    {
        $pdo = Conn::getConn();
        $stmt = $pdo->query("SELECT * FROM casino_settings_wg LIMIT 1");
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return [
                'id' => 1,
                'api_url' => 'http://127.0.0.1:3000',
                'agent_token' => '',
                'secret_key' => '',
                'rtp_default' => 96.50,
                'active' => 1
            ];
        }

        return $data;
    }

    /**
     * Salva configurações WG
     */
    public static function saveSettings(array $dados): bool
    {
        $pdo = Conn::getConn();
        $stmt = $pdo->prepare("UPDATE casino_settings_wg SET 
            api_url = :api_url,
            agent_token = :agent_token,
            secret_key = :secret_key,
            rtp_default = :rtp_default,
            active = :active
            WHERE id = :id");

        return $stmt->execute([
            'id' => (int)($dados['id'] ?? 1),
            'api_url' => trim($dados['api_url'] ?? 'http://127.0.0.1:3000'),
            'agent_token' => trim($dados['agent_token'] ?? ''),
            'secret_key' => trim($dados['secret_key'] ?? ''),
            'rtp_default' => (float)($dados['rtp_default'] ?? 96.50),
            'active' => isset($dados['active']) ? (int)$dados['active'] : 1
        ]);
    }
}