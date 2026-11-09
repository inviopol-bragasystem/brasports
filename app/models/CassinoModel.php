<?php

namespace app\models;

use app\core\crud\Conn;
use PDO;

class CassinoModel
{
    /**
     * Retorna as configurações da API de Cassino
     */
    public static function getSettings(): array
    {
        return \app\services\CassinoAgregadorService::getSettings();
    }

    /**
     * Atualiza as configurações de API do Cassino
     */
    public static function saveSettings(array $dados): bool
    {
        return \app\services\CassinoAgregadorService::saveSettings($dados);
    }

    /**
     * Retorna a lista de jogos do catálogo
     */
    public static function getGames(bool $onlyActive = true, ?string $category = null, ?string $search = null, ?string $provider = null): array
    {
        $pdo = Conn::getConn();
        $sql = "SELECT * FROM casino_games WHERE 1=1";
        $params = [];

        if ($onlyActive) {
            $sql .= " AND active = 1";
        }

        if ($provider && $provider !== 'all' && $provider !== 'Todos') {
            if ($provider === 'PRAGMATIC' || $provider === 'PRAGMATICPLAY') {
                $sql .= " AND (provider = 'PRAGMATIC' OR provider = 'PRAGMATICPLAY')";
            } else if ($provider === 'CRASH' || $provider === 'Crash') {
                $sql .= " AND (provider = 'CRASH' OR provider = 'AVIATOR' OR provider = 'MINES')";
            } else {
                $sql .= " AND provider = :provider";
                $params['provider'] = $provider;
            }
        }

        if ($category && $category !== 'all' && $category !== 'Todos') {
            if ($category === 'Destaque' || $category === 'featured' || $category === 'Destaques') {
                $sql .= " AND (featured = 1 OR category = 'Fortune')";
            } else if ($category === 'PGSOFT' || $category === 'PG Soft') {
                $sql .= " AND provider = 'PGSOFT'";
            } else if ($category === 'POPOK' || $category === 'PopOK') {
                $sql .= " AND provider = 'POPOK'";
            } else if ($category === 'WG' || $category === 'World Games') {
                $sql .= " AND provider = 'WG'";
            } else if ($category === 'PRAGMATIC' || $category === 'PRAGMATICPLAY' || $category === 'Pragmatic') {
                $sql .= " AND (provider = 'PRAGMATIC' OR provider = 'PRAGMATICPLAY')";
            } else if ($category === 'TADA' || $category === 'TaDa') {
                $sql .= " AND provider = 'TADA'";
            } else if ($category === 'CPGAMES' || $category === 'CP Games') {
                $sql .= " AND provider = 'CPGAMES'";
            } else if ($category === 'G759') {
                $sql .= " AND provider = 'G759'";
            } else if ($category === 'Crash' || $category === 'crash') {
                $sql .= " AND (category = 'Crash' OR provider IN ('CRASH', 'AVIATOR', 'MINES'))";
            } else if ($category === 'Fortune') {
                $sql .= " AND (category = 'Fortune' OR game_code LIKE 'fortune-%' OR name LIKE 'Fortune%')";
            } else {
                $sql .= " AND category = :category";
                $params['category'] = $category;
            }
        }

        if ($search) {
            $sql .= " AND (name LIKE :search OR game_code LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        $sql .= " ORDER BY featured DESC, ordem ASC, name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna os provedores distintos disponíveis no catálogo com contagem
     */
    public static function getDistinctProviders(): array
    {
        $pdo = Conn::getConn();
        $stmt = $pdo->query("SELECT provider, COUNT(*) as count FROM casino_games GROUP BY provider ORDER BY count DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca um jogo pelo seu ID ou Código
     */
    public static function getGameByCode(string $code): ?array
    {
        $pdo = Conn::getConn();
        $code = trim($code);
        if (is_numeric($code)) {
            $stmt = $pdo->prepare("SELECT * FROM casino_games WHERE (id = :code OR game_code = :code OR game_id = :code) ORDER BY active DESC, id DESC LIMIT 1");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM casino_games WHERE (game_code = :code OR game_id = :code) ORDER BY active DESC, id DESC LIMIT 1");
        }
        $stmt->execute(['code' => $code]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Alterna o status ativo/inativo de um jogo
     */
    public static function toggleGame(int $id, int $status): bool
    {
        $pdo = Conn::getConn();
        $stmt = $pdo->prepare("UPDATE casino_games SET active = :status WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Alterna o destaque de um jogo
     */
    public static function toggleFeatured(int $id, int $featured): bool
    {
        $pdo = Conn::getConn();
        $stmt = $pdo->prepare("UPDATE casino_games SET featured = :featured WHERE id = :id");
        return $stmt->execute(['featured' => $featured, 'id' => $id]);
    }

    /**
     * Atualiza o RTP específico de um jogo
     */
    public static function updateGameRtp(int $id, float $rtp): bool
    {
        $pdo = Conn::getConn();
        $stmt = $pdo->prepare("UPDATE casino_games SET rtp = :rtp WHERE id = :id");
        return $stmt->execute(['rtp' => $rtp, 'id' => $id]);
    }

    /**
     * Incrementa visualizações do jogo
     */
    public static function incrementViews(int $id): void
    {
        $pdo = Conn::getConn();
        $stmt = $pdo->prepare("UPDATE casino_games SET views_count = views_count + 1 WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    /**
     * Estatísticas gerais do Cassino para o painel de RTP e Lucro
     */
    public static function getStats(): array
    {
        $pdo = Conn::getConn();

        $stats = [
            'total_apostado' => 0.00,
            'total_pago' => 0.00,
            'ggr_lucro' => 0.00,
            'rtp_real' => 0.00,
            'total_rodadas' => 0,
            'total_jogadores' => 0,
            'total_jogos' => 0,
            'jogos_ativos' => 0,
        ];

        // Total de jogos
        $stats['total_jogos'] = (int)$pdo->query("SELECT count(*) FROM casino_games")->fetchColumn();
        $stats['jogos_ativos'] = (int)$pdo->query("SELECT count(*) FROM casino_games WHERE active = 1")->fetchColumn();

        // Movimentação financeira
        $stmtFin = $pdo->query("SELECT 
            COALESCE(SUM(CASE WHEN action = 'bet' THEN amount ELSE 0 END), 0) as total_apostado,
            COALESCE(SUM(CASE WHEN action = 'win' THEN amount ELSE 0 END), 0) as total_pago,
            COUNT(*) as total_rodadas,
            COUNT(DISTINCT user_id) as total_jogadores
            FROM casino_history");

        if ($fin = $stmtFin->fetch(PDO::FETCH_ASSOC)) {
            $stats['total_apostado'] = (float)$fin['total_apostado'];
            $stats['total_pago'] = (float)$fin['total_pago'];
            $stats['ggr_lucro'] = $stats['total_apostado'] - $stats['total_pago'];
            $stats['total_rodadas'] = (int)$fin['total_rodadas'];
            $stats['total_jogadores'] = (int)$fin['total_jogadores'];
            if ($stats['total_apostado'] > 0) {
                $stats['rtp_real'] = round(($stats['total_pago'] / $stats['total_apostado']) * 100, 2);
            }
        }

        return $stats;
    }

    /**
     * Histórico de rodadas/apostas com dados do usuário
     */
    public static function getHistory(?int $userId = null, int $page = 1, int $limit = 50): array
    {
        $pdo = Conn::getConn();
        $offset = ($page - 1) * $limit;

        $sql = "SELECT h.*, u.nome as user_nome, u.login as user_login, g.name as game_titulo, g.image as game_image
            FROM casino_history h
            LEFT JOIN sis_users u ON u.id = h.user_id
            LEFT JOIN casino_games g ON g.game_code = h.game_code
            WHERE 1=1";

        $params = [];
        if ($userId) {
            $sql .= " AND h.user_id = :uid";
            $params['uid'] = $userId;
        }

        $sql .= " ORDER BY h.id DESC LIMIT {$limit} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lança uma sessão de jogo para um usuário autenticado ou em modo demonstração
     */
    public static function launchGame($user, array $game): array
    {
        return \app\services\CassinoAgregadorService::launchGame($user, $game);
    }
}
