<?php

namespace app\services;

use app\core\crud\Conn;
use PDO;
use Exception;

class CassinoAgregadorService
{
    /**
     * Cache estático das configurações
     */
    private static ?array $settings = null;

    /**
     * Retorna as configurações ativas da API de Cassino (GamesVIP / Acelora)
     */
    public static function getSettings(): array
    {
        if (self::$settings !== null) {
            return self::$settings;
        }

        $pdo = Conn::getConn();
        $stmt = $pdo->query("SELECT * FROM casino_settings LIMIT 1");
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            $data = [
                'id' => 1,
                'provider_key' => 'GAMESVIP',
                'provider_name' => 'GamesVIP Agregador',
                'api_url' => 'https://acelora.0um.online',
                'agent_code' => 'banca-vip',
                'agent_token' => 'agt_285cb5406376d80239618d61537faefee07e36df',
                'secret_key' => 'sec_fe52000e7b7b38ca21dcac8d1d7ae897c5e6f96b606b22a7240817cdd7e88954',
                'allowed_providers' => 'WG,POPOK,G759,MINES,TADA,PRAGMATIC,CRASH,CPGAMES,PGSOFT,AVIATOR',
                'rtp_default' => 96.50,
                'rtp_influencer' => 99.00,
                'active' => 1
            ];
        }

        self::$settings = $data;
        return $data;
    }

    /**
     * Limpa o cache estático
     */
    public static function clearCache(): void
    {
        self::$settings = null;
    }

    /**
     * Atualiza as configurações de API
     */
    public static function saveSettings(array $dados): bool
    {
        $pdo = Conn::getConn();
        $stmt = $pdo->prepare("UPDATE casino_settings SET 
            api_url = :api_url,
            agent_code = :agent_code,
            agent_token = :agent_token,
            secret_key = :secret_key,
            allowed_providers = :allowed_providers,
            rtp_default = :rtp_default,
            rtp_influencer = :rtp_influencer,
            active = :active
            WHERE id = :id");

        $res = $stmt->execute([
            'id' => (int)($dados['id'] ?? 1),
            'api_url' => rtrim(trim($dados['api_url'] ?? 'https://acelora.0um.online'), '/'),
            'agent_code' => trim($dados['agent_code'] ?? 'banca-vip'),
            'agent_token' => trim($dados['agent_token'] ?? ''),
            'secret_key' => trim($dados['secret_key'] ?? ''),
            'allowed_providers' => trim($dados['allowed_providers'] ?? 'WG,POPOK,G759,MINES,TADA,PRAGMATIC,CPGAMES,PGSOFT,AVIATOR'),
            'rtp_default' => (float)($dados['rtp_default'] ?? 96.50),
            'rtp_influencer' => (float)($dados['rtp_influencer'] ?? 99.00),
            'active' => isset($dados['active']) ? (int)$dados['active'] : 1
        ]);

        self::clearCache();
        return $res;
    }

    /**
     * Executa uma requisição autenticada à API v2 do Agregador
     */
    public static function request(string $path, array $data = [], string $method = 'GET'): array
    {
        $settings = self::getSettings();
        $apiUrl = rtrim($settings['api_url'] ?? '', '/');
        $agentToken = trim($settings['agent_token'] ?? '');
        $secretKey = trim($settings['secret_key'] ?? '');

        if (empty($apiUrl) || empty($agentToken) || empty($secretKey)) {
            return [
                'status' => false,
                'msg' => 'Credenciais do agregador de cassino não configuradas.'
            ];
        }

        $url = $apiUrl . '/api/v2/' . ltrim($path, '/');
        $ch = curl_init();

        $headers = [
            'X-Acelora-Agent: ' . $agentToken,
            'X-Acelora-Secret: ' . $secretKey,
            'Accept: application/json',
            'User-Agent: Banca-System/2.0'
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
        } elseif (strtoupper($method) === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $body = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'status' => false,
                'msg' => 'Falha de comunicação com o agregador: ' . $curlError
            ];
        }

        // Remove possível BOM UTF-8
        $cleanBody = preg_replace('/^\xEF\xBB\xBF/', '', trim((string)$body));
        $res = json_decode($cleanBody, true);

        if (!is_array($res)) {
            return [
                'status' => false,
                'msg' => "Resposta inválida do agregador (HTTP {$httpCode}).",
                'raw' => substr($cleanBody, 0, 300)
            ];
        }

        return $res;
    }

    /**
     * Sincroniza a URL do callback da carteira no Agregador
     */
    public static function syncWalletCallback(?string $walletUrl = null): array
    {
        if (empty($walletUrl)) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "https://";
            $host = $_SERVER['HTTP_HOST'] ?? 'banca.0um.online';
            $walletUrl = "{$protocol}{$host}/api/cassino/wallet";
        }

        $res = self::request('agent', ['wallet_url' => $walletUrl], 'PUT');
        return $res;
    }

    /**
     * Consulta os provedores ativos no Agregador
     */
    public static function getProviders(): array
    {
        $res = self::request('providers');
        if (!empty($res['status']) && isset($res['providers']) && is_array($res['providers'])) {
            return $res['providers'];
        }
        return [];
    }

    /**
     * Consulta os jogos disponíveis no Agregador
     */
    public static function getRemoteGames(): array
    {
        $res = self::request('games');
        if (!empty($res['status']) && isset($res['games']) && is_array($res['games'])) {
            return $res['games'];
        }
        return [];
    }

    /**
     * Sincroniza o catálogo de jogos do agregador com o banco de dados da Banca
     */
    public static function syncCatalog(): array
    {
        $settings = self::getSettings();
        $allowedProviders = array_filter(array_map('trim', explode(',', strtoupper($settings['allowed_providers'] ?? ''))));

        $providers = self::getProviders();
        $games = self::getRemoteGames();

        if (empty($games)) {
            return [
                'status' => false,
                'msg' => 'Nenhum jogo retornado pelo agregador. Verifique as credenciais da API.'
            ];
        }

        $pdo = Conn::getConn();
        $synced = 0;
        $updated = 0;

        // Mapeamento de categorias e prioridade de vitrine
        $featuredGames = [
            'fortune-tiger', 'fortune-ox', 'fortune-rabbit', 'fortune-mouse', 'fortune-dragon',
            'vs20olympgate', 'vs20sweetbonz', 'vs20sugarush', 'vs20starlight',
            'aviator', 'mines', 'yo-dragon', 'slofortunetiger', 'slorabbitnew'
        ];

        foreach ($games as $g) {
            $provider = strtoupper(trim((string)($g['provider'] ?? '')));
            if (!empty($allowedProviders) && !in_array($provider, $allowedProviders, true)) {
                continue;
            }

            $launchId = trim((string)($g['launch_id'] ?? ''));
            $rawCode = trim((string)($g['game_code'] ?? ($g['game_id'] ?? '')));
            $slug = trim((string)($g['slug'] ?? ''));
            $gameCode = !empty($slug) ? $slug : $rawCode;
            $name = trim((string)($g['name'] ?? $gameCode));
            $coverUrl = trim((string)($g['cover_url'] ?? ''));
            $available = !empty($g['available']) ? 1 : 0;
            $type = strtolower(trim((string)($g['type'] ?? 'slot')));

            if (empty($launchId) || empty($gameCode)) {
                continue;
            }

            // Normalização de categoria
            $category = 'Slots';
            if ($type === 'crash' || in_array($provider, ['CRASH', 'AVIATOR', 'MINES'], true)) {
                $category = 'Crash';
            } elseif ($type === 'live' || in_array($provider, ['LIVE'], true)) {
                $category = 'Ao Vivo';
            } elseif (stripos($name, 'fortune') !== false || stripos($gameCode, 'fortune') !== false) {
                $category = 'Fortune';
            } elseif ($provider === 'PRAGMATIC') {
                $category = 'Pragmatic';
            } elseif ($provider === 'TADA') {
                $category = 'TaDa';
            } elseif ($provider === 'POPOK') {
                $category = 'PopOK';
            } elseif ($provider === 'WG') {
                $category = 'WG';
            } elseif ($provider === 'PGSOFT') {
                $category = 'PGSOFT';
            }

            $isFeatured = in_array(strtolower($gameCode), $featuredGames, true) ? 1 : 0;
            $ordem = $isFeatured ? 1 : 50;

            // Insere ou atualiza o jogo
            $stmt = $pdo->prepare("SELECT id FROM casino_games WHERE game_id = :launchId OR (provider = :provider AND (game_code = :gameCode OR game_code = :rawCode)) LIMIT 1");
            $stmt->execute([
                'launchId' => $launchId,
                'provider' => $provider,
                'gameCode' => $gameCode,
                'rawCode' => $rawCode
            ]);
            $existingId = $stmt->fetchColumn();

            if ($existingId) {
                $upd = $pdo->prepare("UPDATE casino_games SET 
                    game_id = :launchId,
                    name = :name,
                    category = :category,
                    provider = :provider,
                    image = :image,
                    active = :active,
                    distribution = 'gamesvip'
                    WHERE id = :id");
                $upd->execute([
                    'launchId' => $launchId,
                    'name' => $name,
                    'category' => $category,
                    'provider' => $provider,
                    'image' => $coverUrl,
                    'active' => $available,
                    'id' => $existingId
                ]);
                $updated++;
            } else {
                $ins = $pdo->prepare("INSERT INTO casino_games 
                    (game_id, game_code, name, category, provider, image, rtp, active, featured, ordem, views_count, distribution)
                    VALUES 
                    (:launchId, :gameCode, :name, :category, :provider, :image, 96.50, :active, :featured, :ordem, 0, 'gamesvip')");
                $ins->execute([
                    'launchId' => $launchId,
                    'gameCode' => $gameCode,
                    'name' => $name,
                    'category' => $category,
                    'provider' => $provider,
                    'image' => $coverUrl,
                    'active' => $available,
                    'featured' => $isFeatured,
                    'ordem' => $ordem
                ]);
                $synced++;
            }
        }

        return [
            'status' => true,
            'msg' => "Catálogo sincronizado com sucesso! Novos: {$synced}, Atualizados: {$updated}.",
            'total' => $synced + $updated
        ];
    }

    /**
     * Lança uma sessão de jogo pelo Agregador GamesVIP
     */
    public static function launchGame($user, array $game): array
    {
        $settings = self::getSettings();
        if (empty($settings['active'])) {
            return [
                'ok' => false,
                'message' => 'O cassino está temporariamente em manutenção.'
            ];
        }

        $launchId = trim((string)($game['game_id'] ?? ''));
        // Se game_id não contiver os dois pontos (:), tenta compor com o provedor
        if (strpos($launchId, ':') === false) {
            $prov = strtoupper(trim((string)($game['provider'] ?? 'PGSOFT')));
            $launchId = $prov . ':' . $launchId;
        }

        $userCode = $user ? (string)$user->getId() : ('guest_' . (session_id() ? substr(session_id(), 0, 8) : bin2hex(random_bytes(4))));
        $userBalance = $user ? (float)$user->getCredito() : 1000.00;

        $payload = [
            'launch_id' => $launchId,
            'user_code' => $userCode,
            'user_balance' => round($userBalance, 2),
            'lang' => 'pt',
            'currency' => 'BRL',
        ];

        $res = self::request('game_launch', $payload, 'POST');

        if (!empty($res['status']) && !empty($res['launch_url'])) {
            // Incrementa contador de visualizações
            if (!empty($game['id'])) {
                try {
                    $pdo = Conn::getConn();
                    $pdo->prepare("UPDATE casino_games SET views_count = views_count + 1 WHERE id = :id")->execute(['id' => (int)$game['id']]);
                } catch (Exception $e) {}
            }

            return [
                'ok' => true,
                'url' => $res['launch_url'],
                'mode' => 'api'
            ];
        }

        return [
            'ok' => false,
            'message' => $res['msg'] ?? 'Falha ao iniciar o jogo no agregador.'
        ];
    }
}
