<?php

namespace App\Services;

use App\Cache\FileCache;
use App\Http\Client;

/**
 * Camada de acesso aos dados da Betano + normalização completa
 * (horários, odds, mercados, live, agenda).
 */
class BetanoService
{
    /** Tabs de mercados da Betano (bt=N) */
    public const MARKET_TABS = [
        'popular' => 0,
        'overunder' => 1,
        'lineups' => 2,
        'players' => 3,
        'cornerkicks' => 4,
        'cards' => 5,
        'statistics' => 6,
        'goals' => 7,
        'halftime' => 8,
        'specials' => 9,
        'handicap' => 10,
        'asianlines' => 11,
        'minutes' => 12,
        'myodds' => 13,
        'all' => 14,
    ];

    private Client $client;
    private FileCache $cache;
    private Normalizer $normalizer;
    private array $config;
    private array $ttl;

    public function __construct(array $config, Client $client, FileCache $cache)
    {
        $this->config = $config;
        $this->client = $client;
        $this->cache = $cache;
        $tz = $config['app']['timezone'] ?? 'America/Sao_Paulo';
        $this->normalizer = new Normalizer($tz);
        $this->ttl = $config['cache']['ttl'] ?? [];
    }

    private function ttl(string $key): int
    {
        return (int) ($this->ttl[$key] ?? $this->ttl['default'] ?? 60);
    }

    private function fetchJson(string $path, array $query = [], string $cacheKey = '', int $ttl = 60): array
    {
        $ck = $cacheKey !== '' ? $cacheKey : ('raw:' . $path . '?' . http_build_query($query));

        return $this->cache->remember($ck, $ttl, function () use ($path, $query) {
            $res = $this->client->get($path, $query);
            return $res['data'] ?? [];
        });
    }

    private function eventOpts(bool $includeMarkets = true): array
    {
        return ['include_markets' => $includeMarkets];
    }

    /* ------------------------------------------------------------------
     * Esportes
     * ----------------------------------------------------------------*/

    public function listSports(): array
    {
        $configured = $this->config['sports'] ?? [];
        $items = [];
        foreach ($configured as $slug => $meta) {
            $items[] = [
                'slug' => $slug,
                'id' => $meta['id'] ?? null,
                'name' => $meta['name'] ?? $slug,
                'icon' => $meta['icon'] ?? null,
                'endpoints' => [
                    'detail' => "/api/sports/{$slug}",
                    'today' => "/api/sports/{$slug}/today",
                    'schedule' => "/api/sports/{$slug}/schedule",
                    'leagues' => "/api/sports/{$slug}/leagues",
                ],
            ];
        }
        return $items;
    }

    public function getSport(string $slug): array
    {
        $slug = $this->normalizeSportSlug($slug);
        $raw = $this->fetchJson(
            "/api/sport/{$slug}",
            [],
            "sport:{$slug}",
            $this->ttl('sport')
        );

        $data = $raw['data'] ?? $raw;
        $topLeagues = [];
        foreach ($data['topLeagues'] ?? [] as $lg) {
            if (is_array($lg)) {
                $topLeagues[] = $this->normalizer->league($lg);
            }
        }

        $regionGroups = [];
        foreach ($data['regionGroups'] ?? [] as $g) {
            if (is_array($g)) {
                $regionGroups[] = $this->normalizer->regionGroup($g);
            }
        }

        $meta = ($this->config['sports'][$slug] ?? []);

        return [
            'slug' => $slug,
            'id' => $data['id'] ?? ($meta['id'] ?? null),
            'name' => $data['name'] ?? ($meta['name'] ?? $slug),
            'top_leagues' => $topLeagues,
            'region_groups' => $regionGroups,
            'segments' => $data['segments'] ?? [],
        ];
    }

    public function getSportLeagues(string $slug): array
    {
        $sport = $this->getSport($slug);
        $all = [];
        $seen = [];

        foreach ($sport['top_leagues'] as $lg) {
            $id = $lg['id'] ?? null;
            if ($id && !isset($seen[$id])) {
                $seen[$id] = true;
                $lg['featured'] = true;
                $all[] = $lg;
            }
        }

        foreach ($sport['region_groups'] as $group) {
            foreach ($group['regions'] ?? [] as $region) {
                foreach ($region['leagues'] ?? [] as $lg) {
                    $id = $lg['id'] ?? null;
                    if ($id && !isset($seen[$id])) {
                        $seen[$id] = true;
                        $lg['featured'] = false;
                        $lg['group'] = $group['name'] ?? null;
                        $all[] = $lg;
                    }
                }
            }
        }

        return [
            'sport' => [
                'slug' => $sport['slug'],
                'id' => $sport['id'],
                'name' => $sport['name'],
            ],
            'count' => count($all),
            'leagues' => $all,
        ];
    }

    /**
     * Jogos de hoje com odds + agenda por horário.
     * @param array $options expand=full|false, limit_expand=int
     */
    public function getToday(string $slug, array $options = []): array
    {
        $slug = $this->normalizeSportSlug($slug);
        $raw = $this->fetchJson(
            "/api/sport/{$slug}/jogos-de-hoje",
            [],
            "today:{$slug}",
            $this->ttl('events_today')
        );

        $data = $raw['data'] ?? $raw;
        $blocks = [];
        $allEvents = [];

        foreach ($data['blocks'] ?? [] as $block) {
            if (!is_array($block)) {
                continue;
            }
            $normalized = $this->normalizer->blockWithEvents($block, $this->eventOpts(true));
            $blocks[] = $normalized;
            foreach ($normalized['events'] as $ev) {
                $allEvents[] = $ev;
            }
        }

        usort($allEvents, static fn ($a, $b) => ($a['start_time_ms'] ?? 0) <=> ($b['start_time_ms'] ?? 0));

        if (($options['expand'] ?? '') === 'full') {
            $allEvents = $this->expandEventsFullOdds($allEvents, (int) ($options['limit_expand'] ?? 15));
            // re-sync blocks events by id
            $byId = [];
            foreach ($allEvents as $ev) {
                $byId[(string) $ev['id']] = $ev;
            }
            foreach ($blocks as &$block) {
                foreach ($block['events'] as &$bev) {
                    $id = (string) ($bev['id'] ?? '');
                    if (isset($byId[$id])) {
                        $bev = $byId[$id];
                    }
                }
                unset($bev);
            }
            unset($block);
        }

        $schedule = $this->buildScheduleViews($allEvents);

        return [
            'sport' => $slug,
            'date' => date('Y-m-d'),
            'timezone' => $this->config['app']['timezone'] ?? 'America/Sao_Paulo',
            'blocks_count' => count($blocks),
            'events_count' => count($allEvents),
            'schedule' => $schedule,
            'blocks' => $blocks,
            'events' => $allEvents,
            'hint' => 'Cada evento já traz odds principais (1X2, gols, BTTS...). Use /api/events/{id}?markets=all para 600+ mercados.',
        ];
    }

    /**
     * Agenda compacta do esporte (horário + odds principais).
     */
    public function getSchedule(string $slug, array $options = []): array
    {
        $today = $this->getToday($slug, $options);
        $rows = [];
        foreach ($today['events'] as $ev) {
            $rows[] = $this->normalizer->eventScheduleRow($ev);
        }

        $byDate = [];
        foreach ($rows as $row) {
            $d = $row['date_br'] ?? 'sem-data';
            $byDate[$d][] = $row;
        }

        $byHour = [];
        foreach ($rows as $row) {
            $h = $row['time_br'] ?? '??:??';
            $byHour[$h][] = $row;
        }
        ksort($byHour);

        return [
            'sport' => $slug,
            'timezone' => $this->config['app']['timezone'] ?? 'America/Sao_Paulo',
            'events_count' => count($rows),
            'by_date' => $byDate,
            'by_hour' => $byHour,
            'events' => $rows,
        ];
    }

    /* ------------------------------------------------------------------
     * Ligas
     * ----------------------------------------------------------------*/

    public function getLeagueByPath(string $path, array $options = []): array
    {
        $path = trim($path, '/');
        if (!str_starts_with($path, 'sport/')) {
            if (preg_match('#^\d+[a-z]?$#i', $path)) {
                return $this->getLeagueEvents($path, 'upcoming', $options);
            }
            $path = 'sport/' . $path;
        }

        $raw = $this->fetchJson(
            '/api/' . $path,
            [],
            'league_path:' . $path,
            $this->ttl('league')
        );

        $result = $this->normalizeLeaguePage($raw, $path);

        if (($options['expand'] ?? '') === 'full') {
            $result['events'] = $this->expandEventsFullOdds(
                $result['events'],
                (int) ($options['limit_expand'] ?? 10)
            );
            $result['events_count'] = count($result['events']);
        }

        $result['schedule'] = $this->buildScheduleViews($result['events']);
        return $result;
    }

    public function getLeagueEvents(string $leagueId, string $mode = 'upcoming', array $options = []): array
    {
        $leagueId = trim($leagueId);
        $mode = in_array($mode, ['upcoming', 'specialbets'], true) ? $mode : 'upcoming';

        $raw = $this->fetchJson(
            "/api/league/hot/{$mode}",
            ['leagueId' => $leagueId],
            "league_hot:{$mode}:{$leagueId}",
            $this->ttl('league')
        );

        $data = $raw['data'] ?? $raw;
        $events = [];
        foreach ($data['events'] ?? [] as $ev) {
            if (is_array($ev)) {
                $events[] = $this->normalizer->event($ev, $this->eventOpts(true));
            }
        }

        usort($events, static fn ($a, $b) => ($a['start_time_ms'] ?? 0) <=> ($b['start_time_ms'] ?? 0));

        if (($options['expand'] ?? '') === 'full') {
            $events = $this->expandEventsFullOdds($events, (int) ($options['limit_expand'] ?? 10));
        }

        return [
            'league_id' => $leagueId,
            'mode' => $mode,
            'layout' => $data['layout'] ?? null,
            'headers' => $data['headers'] ?? [],
            'events_count' => count($events),
            'schedule' => $this->buildScheduleViews($events),
            'events' => $events,
            'hint' => 'Lista da liga traz mercados principais. expand=full carrega todos os mercados (mais lento).',
        ];
    }

    private function normalizeLeaguePage(array $raw, string $path): array
    {
        $data = $raw['data'] ?? $raw;
        $sport = $data['sport'] ?? [];

        $componentEvents = [];
        $hotUrl = null;
        foreach ($data['parentMarkets'] ?? [] as $pm) {
            foreach ($pm['components'] ?? [] as $comp) {
                $url = $comp['url'] ?? '';
                if (is_string($url) && str_contains($url, '/api/league/hot/upcoming')) {
                    $hotUrl = $url;
                    break 2;
                }
            }
        }

        if ($hotUrl) {
            try {
                $hotRaw = $this->fetchJson($hotUrl, [], 'league_comp:' . md5($hotUrl), $this->ttl('league'));
                $hotData = $hotRaw['data'] ?? $hotRaw;
                foreach ($hotData['events'] ?? [] as $ev) {
                    if (is_array($ev)) {
                        $componentEvents[] = $this->normalizer->event($ev, $this->eventOpts(true));
                    }
                }
            } catch (\Throwable) {
            }
        }

        $embedded = [];
        foreach ($data['events'] ?? [] as $ev) {
            if (is_array($ev)) {
                $embedded[] = $this->normalizer->event($ev, $this->eventOpts(true));
            }
        }

        $events = $componentEvents ?: $embedded;
        usort($events, static fn ($a, $b) => ($a['start_time_ms'] ?? 0) <=> ($b['start_time_ms'] ?? 0));

        return [
            'path' => $path,
            'sport' => [
                'id' => $sport['id'] ?? null,
                'name' => $sport['name'] ?? null,
                'region' => $sport['region'] ?? null,
                'url' => $sport['url'] ?? null,
            ],
            'parent_markets' => array_map(static function ($pm) {
                return [
                    'id' => $pm['id'] ?? null,
                    'name' => $pm['name'] ?? null,
                    'type' => $pm['type'] ?? null,
                    'selected' => (bool) ($pm['selected'] ?? false),
                ];
            }, $data['parentMarkets'] ?? []),
            'events_count' => count($events),
            'events' => $events,
            'standings' => $data['leagueStandings'] ?? [],
        ];
    }

    /* ------------------------------------------------------------------
     * Eventos / Odds
     * ----------------------------------------------------------------*/

    public function getTopEvents(array $options = []): array
    {
        $raw = $this->fetchJson(
            '/api/home/top-events-v2/',
            [],
            'top_events',
            $this->ttl('top_events')
        );

        $payload = $raw['data']['topEventsV2'] ?? $raw['data'] ?? $raw;
        $idList = $payload['eventIdList'] ?? [];
        $eventsMap = $payload['events'] ?? [];
        $marketsMap = $payload['markets'] ?? [];
        $selectionsMap = $payload['selections'] ?? [];
        $leaguesMap = $payload['leagues'] ?? [];
        $zonesMap = $payload['zones'] ?? [];
        $sportsMap = $payload['sports'] ?? [];

        $events = [];
        foreach ($idList as $id) {
            $key = (string) $id;
            $ev = $eventsMap[$key] ?? $eventsMap[$id] ?? null;
            if (!is_array($ev)) {
                continue;
            }

            // enriquece liga/zona/esporte a partir dos maps
            $lid = (string) ($ev['leagueId'] ?? '');
            $zid = (string) ($ev['zoneId'] ?? '');
            $sid = (string) ($ev['sportId'] ?? '');
            if ($lid && isset($leaguesMap[$lid]) && is_array($leaguesMap[$lid])) {
                $ev['leagueName'] = $leaguesMap[$lid]['name'] ?? $ev['leagueName'] ?? null;
            }
            if ($zid && isset($zonesMap[$zid]) && is_array($zonesMap[$zid])) {
                $ev['regionName'] = $zonesMap[$zid]['name'] ?? $ev['regionName'] ?? null;
            }
            if ($sid && isset($sportsMap[$sid]) && is_array($sportsMap[$sid])) {
                $ev['sportName'] = $sportsMap[$sid]['name'] ?? null;
            }

            if (empty($ev['markets']) && !empty($ev['marketIdList'])) {
                $ev['markets'] = $this->composeMarketsFromMaps(
                    $ev['marketIdList'],
                    $marketsMap,
                    $selectionsMap
                );
            }

            $events[] = $this->normalizer->event($ev, $this->eventOpts(true));
        }

        if (($options['expand'] ?? '') === 'full') {
            $events = $this->expandEventsFullOdds($events, (int) ($options['limit_expand'] ?? 10));
        }

        return [
            'count' => count($events),
            'schedule' => $this->buildScheduleViews($events),
            'events' => $events,
            'sports_layout' => $payload['sportsLayout'] ?? null,
        ];
    }

    private function composeMarketsFromMaps(array $marketIds, array $marketsMap, array $selectionsMap): array
    {
        $markets = [];
        foreach ($marketIds as $mid) {
            $m = $marketsMap[(string) $mid] ?? $marketsMap[$mid] ?? null;
            if (!is_array($m)) {
                continue;
            }
            if (empty($m['selections']) && !empty($m['selectionIdList'])) {
                $sels = [];
                foreach ($m['selectionIdList'] as $sid) {
                    $s = $selectionsMap[(string) $sid] ?? $selectionsMap[$sid] ?? null;
                    if (is_array($s)) {
                        $sels[] = $s;
                    }
                }
                $m['selections'] = $sels;
            }
            // live maps às vezes usam price em selection com outro formato
            $markets[] = $m;
        }
        return $markets;
    }

    /**
     * Detalhe completo do evento.
     *
     * @param string $marketsMode popular|all|overunder|players|...|número do tab
     */
    public function getEvent(string $eventId, ?string $slug = null, string $marketsMode = 'all'): array
    {
        $eventId = preg_replace('/\D/', '', $eventId) ?: $eventId;

        if ($slug === null || $slug === '') {
            $slug = $this->resolveEventSlug($eventId);
        }

        if ($slug === null) {
            throw new \RuntimeException(
                "Não foi possível resolver o slug do evento {$eventId}. Passe ?slug=nome-do-jogo",
                404
            );
        }

        $bt = $this->resolveMarketTab($marketsMode);
        $path = "/api/odds/{$slug}/{$eventId}/";
        $query = $bt !== null ? ['bt' => $bt] : [];

        $raw = $this->fetchJson(
            $path,
            $query,
            "event:{$eventId}:{$slug}:bt" . ($bt ?? 'def'),
            $this->ttl('event')
        );
        $data = $raw['data'] ?? $raw;
        $event = $data['event'] ?? null;

        if (!is_array($event)) {
            throw new \RuntimeException("Evento {$eventId} não encontrado", 404);
        }

        $normalized = $this->normalizer->event($event, $this->eventOpts(true));
        $tabs = [];
        foreach ($data['markets'] ?? [] as $tab) {
            if (is_array($tab)) {
                $tabs[] = [
                    'id' => $tab['id'] ?? null,
                    'name' => $tab['name'] ?? null,
                    'type' => $tab['type'] ?? null,
                    'selected' => (bool) ($tab['selected'] ?? false),
                    'endpoint' => "/api/events/{$eventId}?slug={$slug}&markets=" . ($tab['type'] ?? $tab['id']),
                ];
            }
        }

        return [
            'event' => $normalized,
            'markets_mode' => $marketsMode,
            'markets_tab_bt' => $bt,
            'markets_count' => count($normalized['markets'] ?? []),
            'total_markets_available' => $normalized['total_markets_available'] ?? null,
            'market_tabs' => $tabs,
            'available_market_modes' => array_keys(self::MARKET_TABS),
            'breadcrumb' => $data['breadcrumb'] ?? null,
            'bet_builder_tabs' => $data['betBuilderTabs'] ?? null,
            'source_url' => "https://www.betano.bet.br/odds/{$slug}/{$eventId}/",
            'fetched_at' => date('c'),
        ];
    }

    /**
     * Carrega vários tabs e mescla (máxima completude, mais lento).
     */
    public function getEventAllTabs(string $eventId, ?string $slug = null, array $tabs = []): array
    {
        if ($slug === null || $slug === '') {
            $slug = $this->resolveEventSlug($eventId);
        }
        if ($slug === null) {
            throw new \RuntimeException("Slug do evento {$eventId} não resolvido", 404);
        }

        if ($tabs === []) {
            // all sozinho já traz ~667; popular + tabs extras se quiser
            $tabs = ['all'];
        }

        $merged = [];
        $seen = [];
        $base = null;
        $tabMeta = [];

        foreach ($tabs as $tab) {
            try {
                $res = $this->getEvent($eventId, $slug, (string) $tab);
                if ($base === null) {
                    $base = $res;
                }
                $tabMeta[] = [
                    'mode' => $tab,
                    'markets' => $res['markets_count'] ?? 0,
                ];
                foreach ($res['event']['markets'] ?? [] as $m) {
                    $mid = (string) ($m['id'] ?? '');
                    if ($mid === '' || isset($seen[$mid])) {
                        continue;
                    }
                    $seen[$mid] = true;
                    $merged[] = $m;
                }
            } catch (\Throwable) {
            }
        }

        if ($base === null) {
            throw new \RuntimeException("Falha ao carregar evento {$eventId}", 502);
        }

        $base['event']['markets'] = $merged;
        $base['event']['markets_returned'] = count($merged);
        $base['event']['markets_by_type'] = $this->normalizer->groupMarketsByType($merged);
        $base['event']['markets_by_category'] = $this->normalizer->groupMarketsByCategory($merged);
        $base['event']['odds'] = $this->normalizer->buildOddsSummary($merged);
        $base['event']['main_odds'] = $base['event']['odds']['match_result'] ?? null;
        $base['markets_count'] = count($merged);
        $base['tabs_loaded'] = $tabMeta;
        $base['markets_mode'] = 'merged:' . implode(',', $tabs);

        return $base;
    }

    private function resolveMarketTab(string $mode): ?int
    {
        $mode = strtolower(trim($mode));
        if ($mode === '' || $mode === 'default') {
            return self::MARKET_TABS['all']; // completo por padrão
        }
        if (ctype_digit($mode)) {
            return (int) $mode;
        }
        if (isset(self::MARKET_TABS[$mode])) {
            return self::MARKET_TABS[$mode];
        }
        // aliases
        $aliases = [
            '1x2' => 'popular',
            'ou' => 'overunder',
            'gols' => 'goals',
            'escanteios' => 'cornerkicks',
            'cartoes' => 'cards',
            'todos' => 'all',
            'completo' => 'all',
            'full' => 'all',
        ];
        if (isset($aliases[$mode], self::MARKET_TABS[$aliases[$mode]])) {
            return self::MARKET_TABS[$aliases[$mode]];
        }
        return self::MARKET_TABS['all'];
    }

    public function resolveEventSlug(string $eventId): ?string
    {
        try {
            $top = $this->getTopEvents();
            foreach ($top['events'] as $ev) {
                if ((string) $ev['id'] === (string) $eventId && !empty($ev['slug'])) {
                    return $ev['slug'];
                }
            }
        } catch (\Throwable) {
        }

        try {
            $live = $this->getLive();
            foreach ($live['events'] as $ev) {
                if ((string) $ev['id'] === (string) $eventId && !empty($ev['slug'])) {
                    return $ev['slug'];
                }
            }
        } catch (\Throwable) {
        }

        try {
            $today = $this->getToday('futebol');
            foreach ($today['events'] as $ev) {
                if ((string) $ev['id'] === (string) $eventId && !empty($ev['slug'])) {
                    return $ev['slug'];
                }
            }
        } catch (\Throwable) {
        }

        return null;
    }

    /**
     * Expande eventos com carga completa de mercados (bt=all).
     * Limitado para não estourar tempo/rate limit.
     */
    public function expandEventsFullOdds(array $events, int $limit = 10): array
    {
        $limit = max(0, min(30, $limit));
        $n = 0;
        foreach ($events as $i => $ev) {
            if ($n >= $limit) {
                break;
            }
            $id = (string) ($ev['id'] ?? '');
            $slug = $ev['slug'] ?? null;
            if ($id === '' || !$slug) {
                continue;
            }
            // já bem completo?
            if (($ev['markets_returned'] ?? 0) > 50) {
                continue;
            }
            try {
                $full = $this->getEvent($id, $slug, 'all');
                $events[$i] = $full['event'];
                $events[$i]['_expanded'] = true;
                $n++;
            } catch (\Throwable) {
                $events[$i]['_expanded'] = false;
            }
        }
        return $events;
    }

    /* ------------------------------------------------------------------
     * Live
     * ----------------------------------------------------------------*/

    public function getLive(?string $sportId = null): array
    {
        $cfg = $this->config['betano'];
        $query = [
            'includeVirtuals' => 'true',
            'queryLanguageId' => $cfg['language_id'],
            'queryOperatorId' => $cfg['operator_id'],
        ];
        if ($sportId) {
            $query['sportIds'] = strtoupper($sportId);
        }

        $raw = $this->fetchJson(
            '/danae-webapi/api/live/overview/latest',
            $query,
            'live:' . ($sportId ?? 'all'),
            $this->ttl('live')
        );

        $sports = [];
        foreach ($raw['sports']['byId'] ?? $raw['sports'] ?? [] as $sid => $sp) {
            if (!is_array($sp)) {
                continue;
            }
            if (isset($sp['id']) || isset($sp['name'])) {
                $sports[] = [
                    'id' => $sp['id'] ?? $sid,
                    'sport_id' => $sp['sportId'] ?? null,
                    'name' => $sp['name'] ?? $sid,
                    'display_order' => $sp['displayOrder'] ?? null,
                    'zones' => $sp['zoneIdList'] ?? [],
                ];
            }
        }

        $leagues = [];
        $leaguesSrc = $raw['leagues']['byId'] ?? $raw['leagues'] ?? [];
        if (is_array($leaguesSrc)) {
            foreach ($leaguesSrc as $lid => $lg) {
                if (is_array($lg)) {
                    $leagues[(string) ($lg['id'] ?? $lid)] = [
                        'id' => (string) ($lg['id'] ?? $lid),
                        'name' => $lg['name'] ?? null,
                        'sport_id' => $lg['sportId'] ?? null,
                        'zone_id' => $lg['zoneId'] ?? null,
                    ];
                }
            }
        }

        $zones = [];
        $zonesSrc = $raw['zones']['byId'] ?? $raw['zones'] ?? [];
        if (is_array($zonesSrc)) {
            foreach ($zonesSrc as $zid => $z) {
                if (is_array($z)) {
                    $zones[(string) ($z['id'] ?? $zid)] = [
                        'id' => (string) ($z['id'] ?? $zid),
                        'name' => $z['name'] ?? null,
                        'sport_id' => $z['sportId'] ?? null,
                    ];
                }
            }
        }

        $marketsMap = $raw['markets']['byId'] ?? $raw['markets'] ?? [];
        $selectionsMap = $raw['selections']['byId'] ?? $raw['selections'] ?? [];
        $eventsSrc = $raw['events']['byId'] ?? $raw['events'] ?? [];

        // Normaliza maps flat (sem byId)
        if (is_array($marketsMap) && !isset($marketsMap['byId'])) {
            // ok flat id=>obj
        }

        $events = [];
        if (is_array($eventsSrc)) {
            foreach ($eventsSrc as $eid => $ev) {
                if (!is_array($ev)) {
                    continue;
                }
                // se a chave for o id e o obj não tiver id
                if (!isset($ev['id'])) {
                    $ev['id'] = $eid;
                }
                if ($sportId && strtoupper((string) ($ev['sportId'] ?? '')) !== strtoupper($sportId)) {
                    continue;
                }

                if (empty($ev['markets']) && !empty($ev['marketIdList'])) {
                    $ev['markets'] = $this->composeMarketsFromMaps(
                        $ev['marketIdList'],
                        is_array($marketsMap) ? $marketsMap : [],
                        is_array($selectionsMap) ? $selectionsMap : []
                    );
                }

                $lid = (string) ($ev['leagueId'] ?? '');
                $zid = (string) ($ev['zoneId'] ?? '');
                if ($lid && isset($leagues[$lid])) {
                    $ev['leagueName'] = $leagues[$lid]['name'] ?? null;
                }
                if ($zid && isset($zones[$zid])) {
                    $ev['regionName'] = $zones[$zid]['name'] ?? null;
                }

                $events[] = $this->normalizer->event($ev, $this->eventOpts(true));
            }
        }

        usort($events, static fn ($a, $b) => ($a['start_time_ms'] ?? 0) <=> ($b['start_time_ms'] ?? 0));

        return [
            'sports' => $sports,
            'events_count' => count($events),
            'schedule' => $this->buildScheduleViews($events),
            'events' => $events,
            'version' => $raw['version'] ?? null,
            'content_version' => $raw['contentVersion'] ?? null,
            'fetched_at' => date('c'),
        ];
    }

    public function getLiveLayout(): array
    {
        $cfg = $this->config['betano'];
        $raw = $this->fetchJson(
            '/danae-webapi/api/layout/live',
            [
                'queryLanguageId' => $cfg['language_id'],
                'queryOperatorId' => $cfg['operator_id'],
                'queryPlatformType' => $cfg['platform_type'],
            ],
            'live_layout',
            $this->ttl('sports')
        );

        return $raw;
    }

    /* ------------------------------------------------------------------
     * Cupons / Home
     * ----------------------------------------------------------------*/

    public function getUpcomingCoupons(): array
    {
        $raw = $this->fetchJson(
            '/api/home/upcoming-coupons',
            [],
            'coupons',
            $this->ttl('coupons')
        );

        $data = $raw['data'] ?? $raw;
        $coupons = [];
        foreach ($data['coupons'] ?? [] as $c) {
            if (!is_array($c)) {
                continue;
            }
            $events = [];
            foreach ($c['events'] ?? [] as $ev) {
                if (is_array($ev)) {
                    $events[] = $this->normalizer->event($ev, $this->eventOpts(true));
                }
            }
            $coupons[] = [
                'id' => $c['id'] ?? null,
                'name' => $c['name'] ?? null,
                'type' => $c['type'] ?? null,
                'url' => $c['url'] ?? null,
                'layout' => $c['layout'] ?? null,
                'headers' => $c['headers'] ?? [],
                'sub_nav' => $c['subNavItems'] ?? [],
                'events_count' => count($events),
                'schedule' => $this->buildScheduleViews($events),
                'events' => $events,
            ];
        }

        return [
            'count' => count($coupons),
            'coupons' => $coupons,
        ];
    }

    /* ------------------------------------------------------------------
     * Busca / util
     * ----------------------------------------------------------------*/

    public function search(string $query, int $limit = 30): array
    {
        $q = mb_strtolower(trim($query));
        if ($q === '') {
            return ['query' => $query, 'count' => 0, 'events' => []];
        }

        $pool = [];
        foreach (['getTopEvents' => 'top', 'getLive' => 'live'] as $method => $source) {
            try {
                $res = $this->{$method}();
                foreach ($res['events'] ?? [] as $ev) {
                    $ev['_source'] = $source;
                    $pool[(string) $ev['id']] = $ev;
                }
            } catch (\Throwable) {
            }
        }

        try {
            $today = $this->getToday('futebol');
            foreach ($today['events'] as $ev) {
                $ev['_source'] = 'today';
                $pool[(string) $ev['id']] = $ev;
            }
        } catch (\Throwable) {
        }

        $matches = [];
        foreach ($pool as $ev) {
            $hay = mb_strtolower(implode(' ', array_filter([
                $ev['name'] ?? '',
                $ev['short_name'] ?? '',
                $ev['home'] ?? '',
                $ev['away'] ?? '',
                $ev['league_name'] ?? '',
                $ev['region_name'] ?? '',
                ...array_map(static fn ($p) => $p['name'] ?? '', $ev['participants'] ?? []),
            ])));
            if (str_contains($hay, $q)) {
                $matches[] = $ev;
            }
            if (count($matches) >= $limit) {
                break;
            }
        }

        return [
            'query' => $query,
            'count' => count($matches),
            'schedule' => $this->buildScheduleViews($matches),
            'events' => $matches,
        ];
    }

    /**
     * Visões de agenda: por data, por hora, lista ordenada.
     */
    public function buildScheduleViews(array $events): array
    {
        $byDate = [];
        $byHour = [];
        $timeline = [];

        foreach ($events as $ev) {
            $row = $this->normalizer->eventScheduleRow($ev);
            $timeline[] = $row;
            $d = $row['date_br'] ?? 'sem-data';
            $h = ($row['date_br'] ?? '') . ' ' . ($row['time_br'] ?? '??:??');
            $byDate[$d][] = $row;
            $byHour[$h][] = $row;
        }

        ksort($byDate);
        ksort($byHour);

        return [
            'by_date' => $byDate,
            'by_hour' => $byHour,
            'timeline' => $timeline,
        ];
    }

    public function marketTabsInfo(): array
    {
        return [
            'default' => 'all',
            'tabs' => self::MARKET_TABS,
            'usage' => '/api/events/{id}?slug=...&markets=all|popular|overunder|players|goals|...|0-14',
            'note' => 'markets=all (bt=14) retorna ~600+ mercados com odds. popular retorna ~24.',
        ];
    }

    public function clearCache(): int
    {
        return $this->cache->clear();
    }

    private function normalizeSportSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $aliases = [
            'football' => 'futebol',
            'soccer' => 'futebol',
            'basketball' => 'basquete',
            'tennis' => 'tenis',
            'volleyball' => 'voleibol',
            'volei' => 'voleibol',
            'table-tennis' => 'tenis-de-mesa',
            'ping-pong' => 'tenis-de-mesa',
            'american-football' => 'futebol-americano',
            'nfl' => 'futebol-americano',
            'f1' => 'formula-1',
            'ufc' => 'mma',
            'e-sports' => 'esports',
            'esportes-eletronicos' => 'esports',
        ];
        return $aliases[$slug] ?? $slug;
    }
}
