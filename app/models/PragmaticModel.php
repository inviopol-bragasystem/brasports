<?php
/**
 * Pragmatic Model - Integração com API Pragmatic Play
 */

namespace app\models;

use app\core\crud\Conn;
use PDO;

class PragmaticModel
{
    /**
     * Lança um jogo Pragmatic via API local/externa
     */
    public static function launchGame($user, array $game): array
    {
        $settings = self::getSettings();
        if (!$settings['active']) {
            return [
                'ok' => false,
                'message' => 'O cassino Pragmatic Play está temporariamente em manutenção.'
            ];
        }

        $apiUrl = rtrim($settings['api_url'] ?? 'http://127.0.0.1/games/pragmatic', '/');
        $agentCode = $settings['agent_code'] ?? 'admin';
        $agentToken = $settings['agent_token'] ?? '';
        $userCode = $user ? (string)$user->getId() : ('demo_' . (session_id() ? substr(session_id(), 0, 8) : bin2hex(random_bytes(4))));
        $userBalance = $user ? (float)$user->getCredito() : 1000.00;

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = rtrim($protocol . $host, '/');

        $gameCode = $game['game_code'];

        $payload = [
            'method' => 'game_launch',
            'agent_code' => $agentCode,
            'agent_token' => $agentToken,
            'user_code' => $userCode,
            'provider_code' => 'pragmaticplay',
            'game_code' => $gameCode,
            'lang' => 'pt',
            'currency' => 'BRL',
            'user_balance' => round($userBalance, 2),
        ];

        // Endpoints da API Pragmatic
        $targets = [
            'http://127.0.0.1/games/pragmatic/api',
        ];

        if (!empty($apiUrl) && $apiUrl !== 'http://127.0.0.1/games/pragmatic') {
            $targets[] = $apiUrl . '/api';
        }

        if (function_exists('curl_init') && !empty($agentCode) && !empty($agentToken)) {
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
                        $launchUrl = $res['launch_url'];
                        // Ajusta o host do launchUrl para o host atual do navegador
                        $parsedLaunch = parse_url($launchUrl);
                        if (!empty($parsedLaunch['path'])) {
                            $queryPart = !empty($parsedLaunch['query']) ? ('?' . $parsedLaunch['query']) : '';
                            $launchUrl = "{$baseUrl}{$parsedLaunch['path']}{$queryPart}";
                        }

                        return [
                            'ok' => true,
                            'url' => $launchUrl,
                            'mode' => 'pragmatic_api'
                        ];
                    }
                }
            }
        }

        // Fallback: abre o jogo diretamente pelo controller play da Pragmatic
        $launchUrl = "{$baseUrl}/games/pragmatic/play?game={$gameCode}&user={$userCode}&lang=pt&cur=BRL";

        return [
            'ok' => true,
            'url' => $launchUrl,
            'mode' => 'pragmatic_local'
        ];
    }

    /**
     * Configurações Pragmatic
     */
    public static function getSettings(): array
    {
        $pdo = Conn::getConn();
        
        try {
            $stmt = $pdo->query("SELECT * FROM casino_settings_pragmatic LIMIT 1");
            $data = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        } catch (\Throwable $e) {
            $data = false;
        }

        if (!$data) {
            return [
                'id' => 1,
                'api_url' => 'http://127.0.0.1/games/pragmatic',
                'agent_code' => 'admin',
                'agent_token' => '5f2dbdcb-a59d-42f8-9815-cb34a9723cd9',
                'secret_key' => 'e177c4ee-cf29-43b1-9e02-b7dc503051fd',
                'rtp_default' => 96.50,
                'active' => 1
            ];
        }

        return $data;
    }

    /**
     * Salva configurações Pragmatic
     */
    public static function saveSettings(array $dados): bool
    {
        $pdo = Conn::getConn();
        
        // Garante tabela criada
        $pdo->exec("CREATE TABLE IF NOT EXISTS `casino_settings_pragmatic` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `api_url` varchar(255) DEFAULT 'http://127.0.0.1/games/pragmatic',
            `agent_code` varchar(100) DEFAULT 'admin',
            `agent_token` varchar(255) DEFAULT '',
            `secret_key` varchar(255) DEFAULT '',
            `rtp_default` decimal(5,2) DEFAULT '96.50',
            `active` tinyint(1) DEFAULT '1',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Verifica se já existe registro
        $exists = (int)$pdo->query("SELECT COUNT(*) FROM casino_settings_pragmatic")->fetchColumn();
        if ($exists === 0) {
            $stmt = $pdo->prepare("INSERT INTO casino_settings_pragmatic 
                (id, api_url, agent_code, agent_token, secret_key, rtp_default, active) 
                VALUES (1, :api_url, :agent_code, :agent_token, :secret_key, :rtp_default, :active)");
        } else {
            $stmt = $pdo->prepare("UPDATE casino_settings_pragmatic SET 
                api_url = :api_url,
                agent_code = :agent_code,
                agent_token = :agent_token,
                secret_key = :secret_key,
                rtp_default = :rtp_default,
                active = :active
                WHERE id = :id");
        }

        return $stmt->execute([
            'id' => (int)($dados['id'] ?? 1),
            'api_url' => trim($dados['api_url'] ?? 'http://127.0.0.1/games/pragmatic'),
            'agent_code' => trim($dados['agent_code'] ?? 'admin'),
            'agent_token' => trim($dados['agent_token'] ?? ''),
            'secret_key' => trim($dados['secret_key'] ?? ''),
            'rtp_default' => (float)($dados['rtp_default'] ?? 96.50),
            'active' => isset($dados['active']) ? (int)$dados['active'] : 1
        ]);
    }
}
