<?php

declare(strict_types=1);

/**
 * Front controller — API Betano Sports (completa: odds + horários + mercados)
 *
 *   php -d open_basedir= -S 127.0.0.1:8080 router.php
 *   start.bat
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');

$config = require dirname(__DIR__) . '/config.php';

date_default_timezone_set($config['app']['timezone'] ?? 'America/Sao_Paulo');

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = dirname(__DIR__) . '/src/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Cache\FileCache;
use App\Http\Client;
use App\Http\Response;
use App\Router;
use App\Services\BetanoService;

Response::applyCors($config['cors'] ?? []);

/** Helpers de query */
$qBool = static function (string $key, bool $default = false): bool {
    if (!isset($_GET[$key])) {
        return $default;
    }
    $v = strtolower((string) $_GET[$key]);
    return in_array($v, ['1', 'true', 'yes', 'on', 'full'], true);
};

$expandOpts = static function () use ($qBool): array {
    $expand = $_GET['expand'] ?? '';
    if ($qBool('full') || $expand === '1' || $expand === 'true') {
        $expand = 'full';
    }
    return [
        'expand' => (string) $expand,
        'limit_expand' => max(1, min(30, (int) ($_GET['limit_expand'] ?? 15))),
    ];
};

try {
    $client = new Client($config['betano']);
    $cache = new FileCache($config['cache']);
    $service = new BetanoService($config, $client, $cache);
    $router = new Router();

    $router->get('/api', static function () use ($config, $service) {
        Response::json([
            'name' => $config['app']['name'],
            'version' => $config['app']['version'],
            'source' => 'https://www.betano.bet.br/',
            'timezone' => $config['app']['timezone'],
            'completeness' => [
                'horarios' => 'UTC, BR (America/Sao_Paulo), unix, ms, date/time, countdown',
                'odds_listagens' => '1X2, mais/menos, BTTS, chance dupla, handicap quando disponíveis',
                'odds_evento' => 'por padrão markets=all (~600+ mercados)',
                'agenda' => 'schedule.by_date, by_hour, timeline em listagens',
                'live' => 'placar, período, odds live',
            ],
            'market_tabs' => $service->marketTabsInfo(),
            'endpoints' => [
                'GET /api' => 'Documentação',
                'GET /api/health' => 'Healthcheck',
                'GET /api/sports' => 'Lista de esportes',
                'GET /api/sports/{slug}' => 'Esporte + ligas + regiões',
                'GET /api/sports/{slug}/leagues' => 'Todas as ligas',
                'GET /api/sports/{slug}/today' => 'Jogos de hoje + odds + agenda (?expand=full)',
                'GET /api/sports/{slug}/schedule' => 'Agenda compacta (horário + odds principais)',
                'GET /api/leagues/{leagueId}/events' => 'Eventos da liga (?mode=&expand=full)',
                'GET /api/leagues/path/{path}' => 'Liga via path Betano',
                'GET /api/events/top' => 'Destaques home + odds + agenda',
                'GET /api/events/{id}' => 'Evento completo (?slug=&markets=all|popular|...)',
                'GET /api/events/{id}/full' => 'Mescla tabs extras de mercados',
                'GET /api/markets/tabs' => 'Lista de tabs de mercados',
                'GET /api/live' => 'Ao vivo',
                'GET /api/live/{sportId}' => 'Ao vivo por esporte (FOOT, BASK...)',
                'GET /api/live/layout' => 'Layout mercados live',
                'GET /api/coupons' => 'Cupons',
                'GET /api/search?q=' => 'Busca',
                'GET /api/cache/clear?confirm=1' => 'Limpa cache',
            ],
            'query_params' => [
                'markets' => 'all (padrão, ~600+), popular (~24), overunder, players, goals, cards, cornerkicks, handicap, asianlines, halftime, specials, lineups, statistics, minutes, ou 0-14',
                'slug' => 'slug da URL Betano (ex: argentina-suica)',
                'expand' => 'full — hidrata listagens com todos os mercados (mais lento)',
                'limit_expand' => 'máx eventos a expandir (1-30, padrão 15)',
                'mode' => 'upcoming|specialbets nas ligas',
            ],
            'sports_slugs' => array_keys($config['sports']),
            'examples' => [
                '/api/sports/futebol/today',
                '/api/sports/futebol/schedule',
                '/api/events/top',
                '/api/events/88703633?slug=argentina-suica&markets=all',
                '/api/events/88703633?slug=argentina-suica&markets=popular',
                '/api/leagues/10017/events',
                '/api/leagues/10017/events?expand=full&limit_expand=5',
                '/api/live/FOOT',
                '/api/search?q=argentina',
            ],
        ], 200, ['docs' => true]);
    });

    $router->get('/api/health', static function () use ($config) {
        Response::json([
            'status' => 'ok',
            'time' => date('c'),
            'timezone' => $config['app']['timezone'] ?? null,
            'version' => $config['app']['version'],
            'php' => PHP_VERSION,
        ]);
    });

    $router->get('/api/markets/tabs', static function () use ($service) {
        Response::json($service->marketTabsInfo());
    });

    $router->get('/api/sports', static function () use ($service) {
        Response::json($service->listSports());
    });

    $router->get('/api/sports/{slug}', static function (array $p) use ($service) {
        Response::json($service->getSport($p['slug']));
    });

    $router->get('/api/sports/{slug}/leagues', static function (array $p) use ($service) {
        Response::json($service->getSportLeagues($p['slug']));
    });

    $router->get('/api/sports/{slug}/today', static function (array $p) use ($service, $expandOpts) {
        Response::json($service->getToday($p['slug'], $expandOpts()));
    });

    $router->get('/api/sports/{slug}/schedule', static function (array $p) use ($service, $expandOpts) {
        Response::json($service->getSchedule($p['slug'], $expandOpts()));
    });

    $router->get('/api/leagues/{leagueId}/events', static function (array $p) use ($service, $expandOpts) {
        $mode = (string) ($_GET['mode'] ?? 'upcoming');
        Response::json($service->getLeagueEvents($p['leagueId'], $mode, $expandOpts()));
    });

    $router->get('/api/leagues/path/{path*}', static function (array $p) use ($service, $expandOpts) {
        Response::json($service->getLeagueByPath($p['path'], $expandOpts()));
    });

    $router->get('/api/events/top', static function () use ($service, $expandOpts) {
        Response::json($service->getTopEvents($expandOpts()));
    });

    // full merge tabs — registrar ANTES de /api/events/{id}
    $router->get('/api/events/{id}/full', static function (array $p) use ($service) {
        $slug = isset($_GET['slug']) ? (string) $_GET['slug'] : null;
        $tabsParam = (string) ($_GET['tabs'] ?? 'all');
        $tabs = array_values(array_filter(array_map('trim', explode(',', $tabsParam))));
        Response::json($service->getEventAllTabs($p['id'], $slug, $tabs ?: ['all']));
    });

    $router->get('/api/events/{id}', static function (array $p) use ($service) {
        $slug = isset($_GET['slug']) ? (string) $_GET['slug'] : null;
        $markets = (string) ($_GET['markets'] ?? $_GET['tab'] ?? 'all');
        Response::json($service->getEvent($p['id'], $slug, $markets));
    });

    $router->get('/api/live', static function () use ($service) {
        Response::json($service->getLive(null));
    });

    $router->get('/api/live/layout', static function () use ($service) {
        Response::json($service->getLiveLayout());
    });

    $router->get('/api/live/{sportId}', static function (array $p) use ($service) {
        Response::json($service->getLive($p['sportId']));
    });

    $router->get('/api/coupons', static function () use ($service) {
        Response::json($service->getUpcomingCoupons());
    });

    $router->get('/api/search', static function () use ($service) {
        $q = (string) ($_GET['q'] ?? $_GET['query'] ?? '');
        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 30)));
        if ($q === '') {
            Response::error('Parâmetro q é obrigatório', 422);
        }
        Response::json($service->search($q, $limit));
    });

    $router->get('/api/cache/clear', static function () use ($service) {
        if (($_GET['confirm'] ?? '') !== '1') {
            Response::error('Confirme com ?confirm=1', 400);
        }
        $n = $service->clearCache();
        Response::json(['cleared' => $n]);
    });

    $router->get('/', static function () {
        header('Location: /dashboard.html');
        exit;
    });

    $router->get('/dashboard', static function () {
        header('Location: /dashboard.html');
        exit;
    });

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $router->dispatch($method, $uri);
} catch (\Throwable $e) {
    $status = (int) $e->getCode();
    if ($status < 400 || $status > 599) {
        $status = 500;
    }

    $payload = ['message' => $e->getMessage()];
    if (!empty($config['app']['debug'])) {
        $payload['trace'] = explode("\n", $e->getTraceAsString());
        $payload['file'] = $e->getFile() . ':' . $e->getLine();
    }

    Response::error($e->getMessage(), $status, $payload);
}
