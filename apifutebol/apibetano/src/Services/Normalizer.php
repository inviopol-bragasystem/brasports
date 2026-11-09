<?php

namespace App\Services;

/**
 * Normaliza respostas da Betano para um schema limpo e estável da API.
 * Prioriza completude: horários, odds, mercados e metadados.
 */
class Normalizer
{
    private string $timezone;

    public function __construct(string $timezone = 'America/Sao_Paulo')
    {
        $this->timezone = $timezone;
    }

    public function schedule(?int $ms): array
    {
        if ($ms === null || $ms <= 0) {
            return [
                'start_time_ms' => null,
                'start_time_unix' => null,
                'start_time_utc' => null,
                'start_time_br' => null,
                'date_br' => null,
                'time_br' => null,
                'day_name_br' => null,
                'is_live_now' => false,
                'is_past' => false,
                'starts_in_seconds' => null,
                'starts_in_human' => null,
            ];
        }

        $unix = (int) floor($ms / 1000);
        $utc = gmdate('c', $unix);

        try {
            $dt = (new \DateTimeImmutable('@' . $unix))->setTimezone(new \DateTimeZone($this->timezone));
            $localIso = $dt->format('c');
            $dateBr = $dt->format('Y-m-d');
            $timeBr = $dt->format('H:i');
            $dayName = $this->dayNameBr((int) $dt->format('w'));
        } catch (\Throwable) {
            $localIso = $utc;
            $dateBr = gmdate('Y-m-d', $unix);
            $timeBr = gmdate('H:i', $unix);
            $dayName = null;
        }

        $now = time();
        $diff = $unix - $now;

        return [
            'start_time_ms' => $ms,
            'start_time_unix' => $unix,
            'start_time_utc' => $utc,
            'start_time_br' => $localIso,
            'date_br' => $dateBr,
            'time_br' => $timeBr,
            'day_name_br' => $dayName,
            'is_live_now' => false, // preenchido pelo caller se tiver liveData
            'is_past' => $diff < -3 * 3600,
            'starts_in_seconds' => $diff,
            'starts_in_human' => $this->humanDiff($diff),
        ];
    }

    public function msToIso(?int $ms): ?string
    {
        if ($ms === null || $ms <= 0) {
            return null;
        }
        return gmdate('c', (int) floor($ms / 1000));
    }

    public function msToLocal(?int $ms): ?string
    {
        if ($ms === null || $ms <= 0) {
            return null;
        }
        try {
            $dt = (new \DateTimeImmutable('@' . (int) floor($ms / 1000)))
                ->setTimezone(new \DateTimeZone($this->timezone));
            return $dt->format('c');
        } catch (\Throwable) {
            return $this->msToIso($ms);
        }
    }

    public function extractSlugFromOddsUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        if (preg_match('#/(?:odds|live)/([^/]+)/(\d+)#', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    public function extractEventIdFromUrl(?string $url): ?int
    {
        if (!$url) {
            return null;
        }
        if (preg_match('#/(\d+)/?$#', $url, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    public function selection(array $sel): array
    {
        $price = isset($sel['price']) ? (float) $sel['price'] : null;

        return [
            'id' => (string) ($sel['id'] ?? $sel['betRef'] ?? ''),
            'name' => $sel['name'] ?? null,
            'full_name' => $sel['fullName'] ?? $sel['name'] ?? null,
            'price' => $price,
            'american_odds' => $price !== null ? $this->decimalToAmerican($price) : null,
            'implied_prob' => $price !== null && $price > 0 ? round(1 / $price, 4) : null,
            'handicap' => isset($sel['handicap']) ? (float) $sel['handicap'] : null,
            'column_index' => $sel['columnIndex'] ?? null,
            'bet_ref' => isset($sel['betRef']) ? (string) $sel['betRef'] : null,
            'status' => $sel['status'] ?? $sel['selectionStatus'] ?? null,
            'is_suspended' => (bool) ($sel['isSuspended'] ?? $sel['suspended'] ?? false),
            'is_disabled' => (bool) ($sel['isDisabled'] ?? false),
        ];
    }

    public function market(array $market): array
    {
        $selections = [];
        foreach ($market['selections'] ?? [] as $sel) {
            if (is_array($sel)) {
                $selections[] = $this->selection($sel);
            }
        }

        // Scorers / placar exato (quando a Betano envia separados)
        $scorers = [];
        foreach ($market['scorerSelections'] ?? [] as $sel) {
            if (is_array($sel)) {
                $scorers[] = $this->selection($sel);
            }
        }
        $exactScores = [];
        foreach ($market['exactScoreSelections'] ?? [] as $sel) {
            if (is_array($sel)) {
                $exactScores[] = $this->selection($sel);
            }
        }

        $closeMs = isset($market['marketCloseTimeMillis']) ? (int) $market['marketCloseTimeMillis'] : null;

        $out = [
            'id' => (string) ($market['id'] ?? $market['uniqueId'] ?? ''),
            'name' => $market['name'] ?? null,
            'type' => $market['type'] ?? null,
            'type_id' => $market['typeId'] ?? null,
            'handicap' => isset($market['handicap']) ? (float) $market['handicap'] : null,
            'display_order' => $market['displayOrder'] ?? null,
            'close_time_ms' => $closeMs,
            'close_time_utc' => $this->msToIso($closeMs),
            'close_time_br' => $this->msToLocal($closeMs),
            'notes' => $market['marketNotes'] ?? null,
            'pin_key' => $market['pinKey'] ?? null,
            'rendering_layout' => $market['renderingLayout'] ?? null,
            'is_suspended' => (bool) ($market['isSuspended'] ?? $market['suspended'] ?? false),
            'selections_count' => count($selections),
            'selections' => $selections,
            'odds_map' => $this->selectionsToOddsMap($selections),
        ];

        if ($scorers) {
            $out['scorer_selections'] = $scorers;
        }
        if ($exactScores) {
            $out['exact_score_selections'] = $exactScores;
        }

        return $out;
    }

    public function selectionsToOddsMap(array $selections): array
    {
        $map = [];
        foreach ($selections as $s) {
            $key = (string) ($s['name'] ?? $s['full_name'] ?? $s['id'] ?? '');
            if ($key === '') {
                continue;
            }
            // evita sobrescrever chaves duplicadas
            if (!array_key_exists($key, $map)) {
                $map[$key] = $s['price'] ?? null;
            } else {
                $map[$key . '_' . ($s['id'] ?? uniqid())] = $s['price'] ?? null;
            }
        }
        return $map;
    }

    public function participant(array $p, bool $isHomeFallback = false): array
    {
        return [
            'id' => isset($p['id']) ? (string) $p['id'] : (isset($p['teamId']) ? (string) $p['teamId'] : null),
            'name' => $p['name'] ?? $p['shortName'] ?? null,
            'short_name' => $p['shortName'] ?? $p['name'] ?? null,
            'color' => $p['color'] ?? null,
            'is_home' => array_key_exists('isHome', $p) ? (bool) $p['isHome'] : $isHomeFallback,
            'url' => $p['url'] ?? null,
        ];
    }

    /**
     * @param array $options {
     *   include_markets?: bool,
     *   compact_markets?: bool, // só summary de odds se true e poucos mercados
     * }
     */
    public function event(array $event, array $options = []): array
    {
        $includeMarkets = $options['include_markets'] ?? true;
        $startMs = isset($event['startTime']) ? (int) $event['startTime'] : null;

        $participants = [];
        foreach (array_values($event['participants'] ?? []) as $i => $p) {
            if (is_array($p)) {
                $participants[] = $this->participant($p, $i === 0);
            }
        }

        $markets = [];
        if ($includeMarkets && !empty($event['markets']) && is_array($event['markets'])) {
            foreach ($event['markets'] as $m) {
                if (!is_array($m)) {
                    continue;
                }
                // mantém mercado mesmo sem selections (suspenso)
                $markets[] = $this->market($m);
            }
        }

        $url = $event['url'] ?? null;
        $slug = $this->extractSlugFromOddsUrl($url);
        $schedule = $this->schedule($startMs);

        $hasLive = !empty($event['liveData']);
        if ($hasLive) {
            $schedule['is_live_now'] = true;
        }

        $home = $participants[0]['name'] ?? null;
        $away = $participants[1]['name'] ?? null;

        $out = [
            'id' => (string) ($event['id'] ?? ''),
            'name' => $event['name'] ?? $event['shortName'] ?? null,
            'short_name' => $event['shortName'] ?? $event['name'] ?? null,
            'home' => $home,
            'away' => $away,
            'sport_id' => $event['sportId'] ?? null,
            'league_id' => isset($event['leagueId']) ? (string) $event['leagueId'] : null,
            'league_name' => $event['leagueName'] ?? $event['leagueDescription'] ?? null,
            'league_url' => $event['leagueUrl'] ?? null,
            'region_id' => isset($event['regionId'])
                ? (string) $event['regionId']
                : (isset($event['zoneId']) ? (string) $event['zoneId'] : null),
            'region_name' => $event['regionName'] ?? null,
            // horários (várias formas)
            'schedule' => $schedule,
            'start_time' => $schedule['start_time_utc'],
            'start_time_br' => $schedule['start_time_br'],
            'start_time_ms' => $schedule['start_time_ms'],
            'start_time_unix' => $schedule['start_time_unix'],
            'date_br' => $schedule['date_br'],
            'time_br' => $schedule['time_br'],
            // links / meta
            'url' => $url,
            'slug' => $slug,
            'betano_url' => $url ? 'https://www.betano.bet.br' . $url : null,
            'bet_radar_id' => $event['betRadarId'] ?? $event['betradarMatchId'] ?? $event['betRadarMatchId'] ?? null,
            'total_markets_available' => $event['totalMarketsAvailable'] ?? null,
            'markets_returned' => count($markets),
            'streaming' => (bool) ($event['streamingAvailable'] ?? $event['isStreamingAvailable'] ?? false),
            'will_go_live' => (bool) ($event['willGoLive'] ?? false),
            'has_lineups' => (bool) ($event['hasLineupsAvailability'] ?? false),
            'has_zero_rake' => (bool) ($event['hasZeroRake'] ?? false),
            'has_match_combo' => (bool) ($event['hasMatchCombo'] ?? false),
            'match_combo_url' => $event['matchComboUrl'] ?? null,
            'supports_overtime' => (bool) ($event['supportsOvertime'] ?? false),
            'tv_channel' => $event['tvChannel'] ?? null,
            'notes' => $event['notes'] ?? null,
            'exclusive_stream_note' => $event['exclusiveStreamNote'] ?? null,
            'participants' => $participants,
            'stats' => $this->normalizeStats($event['stats'] ?? []),
        ];

        if ($hasLive) {
            $out['live'] = $this->liveData($event['liveData']);
            $out['is_live'] = true;
        } else {
            $out['is_live'] = false;
        }

        if ($includeMarkets) {
            $out['markets'] = $markets;
            $out['markets_by_type'] = $this->groupMarketsByType($markets);
            $out['markets_by_category'] = $this->groupMarketsByCategory($markets);
            $out['odds'] = $this->buildOddsSummary($markets);
            $out['main_odds'] = $out['odds']['match_result'] ?? $this->extractMainOdds($markets);
        }

        if (!empty($event['marketIdList'])) {
            $out['market_ids'] = array_map('strval', $event['marketIdList']);
        }

        return $out;
    }

    public function normalizeStats(array $stats): array
    {
        $out = [];
        foreach ($stats as $s) {
            if (!is_array($s)) {
                continue;
            }
            $out[] = [
                'provider_id' => $s['providerId'] ?? null,
                'url' => $s['url'] ?? null,
            ];
        }
        return $out;
    }

    public function groupMarketsByType(array $markets): array
    {
        $groups = [];
        foreach ($markets as $m) {
            $type = (string) ($m['type'] ?? 'UNKNOWN');
            $groups[$type][] = $m;
        }
        return $groups;
    }

    /**
     * Agrupa mercados em categorias amigáveis (1x2, gols, handicap, etc.)
     */
    public function groupMarketsByCategory(array $markets): array
    {
        $map = [
            'match_result' => ['MRES', 'MR12', 'ML12', 'HHLA', 'H1RS', 'FHMR', 'HTFT'],
            'double_chance' => ['DBLC', 'DNOB'],
            'total_goals' => ['HCTG', 'OUH1', 'OUHG', 'OUAG', '1COU', 'COU1', 'TCOU'],
            'both_teams_score' => ['BTSC'],
            'handicap' => ['AHCP', 'EHCP', 'HCP', 'AH12'],
            'corners' => ['CNOU', 'CNRS'],
            'cards' => ['TCOU', 'YCOU', 'RCOU'],
            'players' => ['PLST', 'PSCR', 'AGOL', 'FGOL', 'LGOL'],
            'exact_score' => ['CSFT', 'CS'],
            'specials' => ['QUAL', 'MOFQ', 'X008', 'MGTP', 'INTS'],
        ];

        $categories = [];
        foreach ($map as $cat => $types) {
            $categories[$cat] = [];
        }
        $categories['other'] = [];

        foreach ($markets as $m) {
            $type = (string) ($m['type'] ?? '');
            $placed = false;
            foreach ($map as $cat => $types) {
                if (in_array($type, $types, true)) {
                    $categories[$cat][] = $m;
                    $placed = true;
                    break;
                }
            }
            if (!$placed) {
                $categories['other'][] = $m;
            }
        }

        // remove categorias vazias
        return array_filter($categories, static fn ($list) => count($list) > 0);
    }

    /**
     * Resumo prático de odds principais para consumo rápido
     */
    public function buildOddsSummary(array $markets): array
    {
        $summary = [
            'match_result' => null,      // 1 X 2
            'double_chance' => null,     // 1X 12 X2
            'draw_no_bet' => null,
            'total_goals' => null,       // over/under principal
            'both_teams_score' => null,  // sim/não
            'handicap' => null,
            'corners' => null,
            'halftime_result' => null,
            'all_main' => [],
        ];

        $pickFirst = static function (array $markets, array $types) {
            foreach ($types as $type) {
                foreach ($markets as $m) {
                    if (($m['type'] ?? '') === $type && !empty($m['selections'])) {
                        return $m;
                    }
                }
            }
            return null;
        };

        $toSimple = static function (?array $m): ?array {
            if (!$m) {
                return null;
            }
            return [
                'market_id' => $m['id'],
                'market_name' => $m['name'],
                'type' => $m['type'],
                'handicap' => $m['handicap'] ?? null,
                'odds' => $m['odds_map'] ?? [],
                'selections' => $m['selections'],
            ];
        };

        $summary['match_result'] = $toSimple($pickFirst($markets, ['MRES', 'MR12', 'ML12']));
        $summary['double_chance'] = $toSimple($pickFirst($markets, ['DBLC']));
        $summary['draw_no_bet'] = $toSimple($pickFirst($markets, ['DNOB']));
        $summary['both_teams_score'] = $toSimple($pickFirst($markets, ['BTSC']));
        $summary['halftime_result'] = $toSimple($pickFirst($markets, ['H1RS', 'FHMR']));
        $summary['corners'] = $toSimple($pickFirst($markets, ['CNOU']));
        $summary['handicap'] = $toSimple($pickFirst($markets, ['AHCP', 'EHCP', 'HCP']));

        // total goals: prefere linha 2.5
        $totals = [];
        foreach ($markets as $m) {
            if (in_array($m['type'] ?? '', ['HCTG', 'OUH1'], true) && !empty($m['selections'])) {
                $totals[] = $m;
            }
        }
        if ($totals) {
            $preferred = null;
            foreach ($totals as $m) {
                $h = (float) ($m['handicap'] ?? 0);
                if (abs($h - 2.5) < 0.01 || abs($h) < 0.01) {
                    // se handicap 0, tenta extrair da selection
                    $preferred = $m;
                    if (abs($h - 2.5) < 0.01) {
                        break;
                    }
                    // check selection names for 2.5
                    foreach ($m['selections'] as $s) {
                        if (str_contains((string) ($s['full_name'] ?? $s['name'] ?? ''), '2.5')) {
                            $preferred = $m;
                            break 2;
                        }
                    }
                }
            }
            $summary['total_goals'] = $toSimple($preferred ?: $totals[0]);
        }

        foreach (['match_result', 'double_chance', 'total_goals', 'both_teams_score', 'draw_no_bet', 'halftime_result'] as $k) {
            if ($summary[$k]) {
                $summary['all_main'][$k] = $summary[$k]['odds'];
            }
        }

        return $summary;
    }

    public function extractMainOdds(array $markets): ?array
    {
        $summary = $this->buildOddsSummary($markets);
        return $summary['match_result'] ?? null;
    }

    public function liveData(array $live): array
    {
        $score = $live['score'] ?? null;
        $results = $live['results'] ?? [];

        return [
            'period' => $live['periodDescription'] ?? $live['period'] ?? null,
            'score' => $score ? [
                'home' => $score['home'] ?? null,
                'away' => $score['away'] ?? null,
                'display' => ($score['home'] ?? '?') . ' - ' . ($score['away'] ?? '?'),
            ] : null,
            'clock' => $live['clock'] ?? $results['matchTime'] ?? $results['time'] ?? null,
            'status' => $live['status'] ?? null,
            'server_home' => $results['serverHome'] ?? null,
            'current_set' => $results['currentSetNumber'] ?? null,
            'raw' => $results ?: null,
        ];
    }

    public function league(array $league): array
    {
        return [
            'id' => (string) ($league['id'] ?? ''),
            'name' => $league['name'] ?? $league['displayName'] ?? null,
            'display_name' => $league['displayName'] ?? $league['name'] ?? null,
            'region_id' => isset($league['regionId']) ? (string) $league['regionId'] : null,
            'region_name' => $league['regionName'] ?? null,
            'region_code' => $league['regionCode'] ?? null,
            'url' => $league['url'] ?? null,
            'flag_url' => $league['flagUrl'] ?? null,
            'betano_url' => !empty($league['url']) ? 'https://www.betano.bet.br' . $league['url'] : null,
        ];
    }

    public function regionGroup(array $group): array
    {
        $regions = [];
        foreach ($group['regions'] ?? [] as $region) {
            if (!is_array($region)) {
                continue;
            }
            $leagues = [];
            foreach ($region['leagues'] ?? [] as $lg) {
                if (is_array($lg)) {
                    $leagues[] = $this->league($lg);
                }
            }
            $regions[] = [
                'id' => isset($region['id']) ? (string) $region['id'] : null,
                'name' => $region['name'] ?? null,
                'region_code' => $region['regionCode'] ?? null,
                'url' => $region['url'] ?? null,
                'flag_url' => $region['flagUrl'] ?? null,
                'leagues' => $leagues,
                'leagues_count' => count($leagues),
            ];
        }

        return [
            'name' => $group['name'] ?? null,
            'expanded' => (bool) ($group['expanded'] ?? false),
            'regions' => $regions,
        ];
    }

    public function blockWithEvents(array $block, array $eventOptions = []): array
    {
        $events = [];
        foreach ($block['events'] ?? [] as $ev) {
            if (is_array($ev)) {
                $events[] = $this->event($ev, $eventOptions);
            }
        }

        // ordena por horário
        usort($events, static fn ($a, $b) => ($a['start_time_ms'] ?? 0) <=> ($b['start_time_ms'] ?? 0));

        return [
            'id' => isset($block['id']) ? (string) $block['id'] : null,
            'name' => $block['name'] ?? null,
            'short_name' => $block['shortName'] ?? null,
            'region_id' => isset($block['regionId']) ? (string) $block['regionId'] : null,
            'region_code' => $block['regionCode'] ?? null,
            'url' => $block['url'] ?? null,
            'events_count' => count($events),
            'events' => $events,
        ];
    }

    /**
     * Agenda compacta: só horário + times + odds principais (leve para listagens)
     */
    public function eventScheduleRow(array $normalizedEvent): array
    {
        return [
            'id' => $normalizedEvent['id'] ?? null,
            'name' => $normalizedEvent['name'] ?? null,
            'home' => $normalizedEvent['home'] ?? null,
            'away' => $normalizedEvent['away'] ?? null,
            'sport_id' => $normalizedEvent['sport_id'] ?? null,
            'league_id' => $normalizedEvent['league_id'] ?? null,
            'league_name' => $normalizedEvent['league_name'] ?? null,
            'region_name' => $normalizedEvent['region_name'] ?? null,
            'date_br' => $normalizedEvent['date_br'] ?? null,
            'time_br' => $normalizedEvent['time_br'] ?? null,
            'start_time_br' => $normalizedEvent['start_time_br'] ?? null,
            'start_time_utc' => $normalizedEvent['start_time'] ?? null,
            'start_time_unix' => $normalizedEvent['start_time_unix'] ?? null,
            'starts_in_human' => $normalizedEvent['schedule']['starts_in_human'] ?? null,
            'is_live' => $normalizedEvent['is_live'] ?? false,
            'slug' => $normalizedEvent['slug'] ?? null,
            'betano_url' => $normalizedEvent['betano_url'] ?? null,
            'total_markets_available' => $normalizedEvent['total_markets_available'] ?? null,
            'odds' => $normalizedEvent['odds']['all_main']
                ?? ($normalizedEvent['main_odds']['odds'] ?? null),
            'main_odds' => $normalizedEvent['main_odds'] ?? null,
            'detail_endpoint' => !empty($normalizedEvent['id'])
                ? '/api/events/' . $normalizedEvent['id'] . (
                    !empty($normalizedEvent['slug'])
                        ? '?slug=' . rawurlencode((string) $normalizedEvent['slug']) . '&markets=all'
                        : '?markets=all'
                )
                : null,
        ];
    }

    private function dayNameBr(int $w): string
    {
        return ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][$w] ?? '';
    }

    private function humanDiff(int $seconds): string
    {
        if ($seconds < 0) {
            $ago = abs($seconds);
            if ($ago < 60) {
                return 'já começou / encerrou';
            }
            if ($ago < 3600) {
                return 'há ' . (int) floor($ago / 60) . ' min';
            }
            if ($ago < 86400) {
                return 'há ' . (int) floor($ago / 3600) . ' h';
            }
            return 'há ' . (int) floor($ago / 86400) . ' d';
        }
        if ($seconds < 60) {
            return 'em instantes';
        }
        if ($seconds < 3600) {
            return 'em ' . (int) floor($seconds / 60) . ' min';
        }
        if ($seconds < 86400) {
            $h = (int) floor($seconds / 3600);
            $m = (int) floor(($seconds % 3600) / 60);
            return $m > 0 ? "em {$h}h {$m}min" : "em {$h}h";
        }
        $d = (int) floor($seconds / 86400);
        $h = (int) floor(($seconds % 86400) / 3600);
        return $h > 0 ? "em {$d}d {$h}h" : "em {$d}d";
    }

    private function decimalToAmerican(float $decimal): ?int
    {
        if ($decimal <= 1) {
            return null;
        }
        if ($decimal >= 2.0) {
            return (int) round(($decimal - 1) * 100);
        }
        return (int) round(-100 / ($decimal - 1));
    }
}
