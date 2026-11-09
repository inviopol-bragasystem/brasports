<?php

namespace app\helpers;

use app\core\crud\Conn;
use app\models\DadosModel;
use Exception;
use PDO;

class SportsImporter
{
    private const CONFIG_KEY_FILE = ABSPATH . '/app/the_odds_api.json';

    /** Busca eventos de futebol do servidor local da integração Betby. */
    public static function fetchFromBetby(string $mode = 'today'): array
    {
        $baseUrl = rtrim((string)(getenv('BETBY_IMPORT_API_URL') ?: 'http://127.0.0.1:3000'), '/');
        $url = $baseUrl . '/api/events';
        if ($mode === 'live') {
            $url .= '?live=true';
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $status < 200 || $status >= 300) {
            $detail = $error ?: "HTTP {$status}";
            throw new Exception("Não foi possível consultar a API Betby em {$baseUrl}. Verifique se o servidor Betby está iniciado ({$detail}).");
        }

        $payload = json_decode($body, true);
        if (!is_array($payload)) {
            throw new Exception('A API Betby retornou uma resposta inválida.');
        }
        $events = $payload['data'] ?? $payload['events'] ?? $payload;
        if (!is_array($events)) {
            throw new Exception('A API Betby não retornou uma lista de partidas.');
        }

        $normalized = [];
        $leagues = $teams = $references = [];
        $today = (new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
        foreach ($events as $event) {
            if (!is_array($event)) continue;
            $sportName = mb_strtolower((string)($event['sportName'] ?? $event['sportSource'] ?? ''));
            if ($sportName !== '' && !str_contains($sportName, 'futebol') && !str_contains($sportName, 'football') && !str_contains($sportName, 'soccer')) continue;
            $home = trim((string)($event['home'] ?? ''));
            $away = trim((string)($event['away'] ?? ''));
            if ($home === '' || $away === '') continue;

            $isLive = !empty($event['live']);
            if ($mode === 'live' && !$isLive) continue;
            $start = null;
            try {
                if (!empty($event['start'])) $start = (new \DateTimeImmutable((string)$event['start']))->setTimezone(new \DateTimeZone('America/Sao_Paulo'));
            } catch (\Throwable $ignored) {}
            if ($mode !== 'live' && $mode !== 'top' && $start && $start->format('Y-m-d') < $today) continue;

            $date = $start ? $start->format('Y-m-d') : $today;
            $time = $start ? $start->format('H:i:s') : '00:00:00';
            $league = trim((string)($event['league'] ?? 'Futebol')) ?: 'Futebol';
            $scoreHome = isset($event['scoreHome']) && is_numeric($event['scoreHome']) ? (int)$event['scoreHome'] : 0;
            $scoreAway = isset($event['scoreAway']) && is_numeric($event['scoreAway']) ? (int)$event['scoreAway'] : 0;
            $cotacoes = self::betbyMarketsToCotacoes($event['markets'] ?? [], $home, $away);
            $ref = 'betby_' . (string)($event['id'] ?? md5($home . $away . $date));
            $normalized[] = [
                'refid' => $ref, 'source' => 'Betby', 'campeonato' => $league,
                'data' => $date, 'hora' => $time, 'timecasa' => $home, 'timefora' => $away,
                'timecasaimg' => 'default.jpg', 'timeforaimg' => 'default.jpg',
                'timecasaplacar' => $scoreHome, 'timeforaplacar' => $scoreAway,
                'is_live' => $isLive, 'placar' => $isLive ? "{$scoreHome} x {$scoreAway}" : '',
                'periodo' => $isLive ? (string)($event['minute'] ?? 'Ao Vivo') : '',
                'country' => (string)($event['country'] ?? ''), 'cotacoes' => $cotacoes,
            ];
            $leagues[$league] = true; $teams[$home] = true; $teams[$away] = true; $references[] = $ref;
        }

        return ['result' => 1, 'source' => 'betby', 'count' => count($normalized), 'campeonatos' => array_keys($leagues), 'times' => array_keys($teams), 'referencias' => $references, 'jogos' => $normalized];
    }

    private static function betbyMarketsToCotacoes(array $markets, string $home, string $away): array
    {
        $result = ['90' => [], 'pt' => [], 'st' => []];
        foreach ($markets as $market) {
            if (!is_array($market)) continue;
            $name = mb_strtolower((string)($market['name'] ?? ''));
            foreach (($market['outcomes'] ?? []) as $outcome) {
                if (!is_array($outcome) || !isset($outcome[0], $outcome[1]) || !is_numeric($outcome[1])) continue;
                $label = mb_strtolower(trim((string)$outcome[0]));
                $odd = (float)$outcome[1];
                if ($odd <= 1) continue;
                if (str_contains($name, 'resultado final') || str_contains($name, '1x2') || str_contains($name, 'match result')) {
                    if (in_array($label, ['1', 'casa', mb_strtolower($home)], true)) $result['90']['casa'] = $odd;
                    elseif (in_array($label, ['x', 'empate', 'draw'], true)) $result['90']['empate'] = $odd;
                    elseif (in_array($label, ['2', 'fora', mb_strtolower($away)], true)) $result['90']['fora'] = $odd;
                } elseif (str_contains($name, 'ambas')) {
                    if (in_array($label, ['sim', 'yes'], true)) $result['90']['amb'] = $odd;
                    if (in_array($label, ['não', 'nao', 'no'], true)) $result['90']['ambn'] = $odd;
                } elseif (str_contains($name, 'total')) {
                    if (preg_match('/(mais de|over)\s*([0-9]+(?:[.,][0-9]+)?)/u', $label, $m)) {
                        $line = (float)str_replace(',', '.', $m[2]);
                        $key = $line <= 1.5 ? 'gmais2' : ($line <= 2.5 ? 'gmais3' : 'gmais4');
                        $result['90'][$key] = $odd;
                    } elseif (preg_match('/(menos de|under)\s*([0-9]+(?:[.,][0-9]+)?)/u', $label, $m)) {
                        $line = (float)str_replace(',', '.', $m[2]);
                        $key = $line <= 1.5 ? 'gmenos2' : ($line <= 2.5 ? 'gmenos3' : 'gmenos4');
                        $result['90'][$key] = $odd;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Retorna a chave salva da The Odds API
     */
    public static function getSavedTheOddsApiKey(): string
    {
        if (file_exists(self::CONFIG_KEY_FILE)) {
            $data = json_decode(file_get_contents(self::CONFIG_KEY_FILE), true);
            return (string)($data['apiKey'] ?? '');
        }
        return '';
    }

    /**
     * Salva a chave da The Odds API
     */
    public static function saveTheOddsApiKey(string $apiKey): bool
    {
        $dir = dirname(self::CONFIG_KEY_FILE);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $payload = json_encode(['apiKey' => trim($apiKey), 'updated_at' => date('c')], JSON_PRETTY_PRINT);
        return (bool)file_put_contents(self::CONFIG_KEY_FILE, $payload);
    }

    /**
     * Lista de ligas populares da The Odds API
     */
    public static function getTheOddsSportsList(): array
    {
        return [
            ['key' => 'soccer_brazil_campeonato', 'title' => 'Brasil - Brasileirão Série A'],
            ['key' => 'soccer_brazil_serie_b', 'title' => 'Brasil - Brasileirão Série B'],
            ['key' => 'soccer_conmebol_copa_libertadores', 'title' => 'América do Sul - Copa Libertadores'],
            ['key' => 'soccer_conmebol_copa_sudamericana', 'title' => 'América do Sul - Copa Sul-Americana'],
            ['key' => 'soccer_uefa_champs_league', 'title' => 'Europa - UEFA Champions League'],
            ['key' => 'soccer_uefa_europa_league', 'title' => 'Europa - UEFA Europa League'],
            ['key' => 'soccer_epl', 'title' => 'Inglaterra - Premier League'],
            ['key' => 'soccer_spain_la_liga', 'title' => 'Espanha - La Liga'],
            ['key' => 'soccer_italy_serie_a', 'title' => 'Itália - Serie A'],
            ['key' => 'soccer_germany_bundesliga', 'title' => 'Alemanha - Bundesliga'],
            ['key' => 'soccer_france_ligue_one', 'title' => 'França - Ligue 1'],
            ['key' => 'soccer_portugal_primeira_liga', 'title' => 'Portugal - Primeira Liga'],
            ['key' => 'soccer_argentina_primera_division', 'title' => 'Argentina - Liga Profissional'],
        ];
    }

    /**
     * Busca jogos da API Betano (PHP Local)
     */
    public static function fetchFromBetano(string $mode = 'today'): array
    {
        $betanoDir = ABSPATH . '/apifutebol/apibetano';
        if (!file_exists($betanoDir . '/config.php')) {
            throw new Exception("Pasta da API Betano não encontrada em: {$betanoDir}");
        }

        $config = require $betanoDir . '/config.php';

        spl_autoload_register(function ($class) use ($betanoDir) {
            if (str_starts_with($class, 'App\\')) {
                $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 4));
                $file = $betanoDir . '/src/' . $relative . '.php';
                if (is_file($file)) {
                    require_once $file;
                }
            }
        });

        try {
            $client = new \App\Http\Client($config['betano']);
            $cache = new \App\Cache\FileCache($config['cache']);
            $service = new \App\Services\BetanoService($config, $client, $cache);

            if ($mode === 'live') {
                $raw = $service->getLive('FOOT');
            } elseif ($mode === 'top') {
                $raw = $service->getTopEvents(['expand' => '']);
            } elseif ($mode === 'upcoming') {
                $raw = $service->getUpcomingCoupons();
            } else {
                $raw = $service->getToday('futebol', ['expand' => '']);
            }

            $events = $raw['events'] ?? [];
            if (!empty($events)) {
                return self::normalizeBetanoEvents($events, $mode === 'live');
            }
        } catch (\Throwable $e) {
            // Em caso de falha/bloqueio na Betano BR, faz fallback transparente para a API Node/Vai de Bet
        }

        return self::fetchFromNodeApi($mode, 'vaidebet');
    }

    /**
     * Normaliza eventos da Betano para o formato do sistema
     */
    private static function normalizeBetanoEvents(array $events, bool $forceLive = false): array
    {
        $normalized = [];
        $campeonatos = [];
        $times = [];
        $referencias = [];

        foreach ($events as $e) {
            $home = trim((string)($e['home'] ?? ''));
            $away = trim((string)($e['away'] ?? ''));

            if (empty($home) || empty($away)) {
                if (!empty($e['name']) && str_contains($e['name'], ' - ')) {
                    [$home, $away] = array_map('trim', explode(' - ', $e['name'], 2));
                } else {
                    continue;
                }
            }

            $league = trim((string)($e['league_name'] ?? $e['league']['name'] ?? $e['region_name'] ?? 'Futebol Internacional'));
            if (empty($league)) {
                $league = 'Futebol Internacional';
            }

            $date = $e['schedule']['date_br'] ?? date('Y-m-d');
            $time = $e['schedule']['time_br'] ?? date('H:i');
            if (strlen($time) === 5) {
                $time .= ':00';
            }

            $isLive = $forceLive || !empty($e['is_live']) || !empty($e['schedule']['is_live_now']);
            $scoreHome = (int)($e['live']['score']['home'] ?? 0);
            $scoreAway = (int)($e['live']['score']['away'] ?? 0);
            $period = $e['live']['period'] ?? ($isLive ? 'Ao Vivo' : '');

            $refId = 'betano_' . ($e['id'] ?? md5($home . $away . $date));

            // Cotações
            $odds = self::buildCotacoesMapFromBetano($e);

            $normalized[] = [
                'refid' => $refId,
                'source' => 'Betano BR',
                'campeonato' => $league,
                'data' => $date,
                'hora' => $time,
                'timecasa' => $home,
                'timefora' => $away,
                'timecasaimg' => 'default.jpg',
                'timeforaimg' => 'default.jpg',
                'timecasaplacar' => $scoreHome,
                'timeforaplacar' => $awayScore ?? $scoreAway,
                'is_live' => $isLive,
                'placar' => $isLive ? "{$scoreHome} x {$scoreAway}" : '',
                'periodo' => $period,
                'country' => trim((string)($e['region_name'] ?? '')),
                'cotacoes' => $odds,
            ];

            $campeonatos[$league] = true;
            $times[$home] = true;
            $times[$away] = true;
            $referencias[] = $refId;
        }

        return [
            'result' => 1,
            'source' => 'betano',
            'count' => count($normalized),
            'campeonatos' => array_keys($campeonatos),
            'times' => array_keys($times),
            'referencias' => $referencias,
            'jogos' => $normalized,
        ];
    }

    private static function buildCotacoesMapFromBetano(array $e): array
    {
        $c90 = [];
        $pt = [];
        $st = [];

        // 1X2 Principal
        $mres = $e['odds']['match_result']['odds'] ?? [];
        if (!empty($mres['1'])) $c90['casa'] = (float)$mres['1'];
        if (!empty($mres['X'])) $c90['empate'] = (float)$mres['X'];
        if (!empty($mres['2'])) $c90['fora'] = (float)$mres['2'];

        // Dupla chance
        $dblc = $e['odds']['double_chance']['odds'] ?? [];
        if (!empty($dblc['1X'])) $c90['dplcasa'] = (float)$dblc['1X'];
        if (!empty($dblc['12'])) $c90['cof'] = (float)$dblc['12'];
        if (!empty($dblc['X2'])) $c90['dplfora'] = (float)$dblc['X2'];

        // Ambas marcam
        $btts = $e['odds']['both_teams_score']['odds'] ?? [];
        if (!empty($btts['Sim']) || !empty($btts['Yes'])) {
            $c90['amb'] = (float)($btts['Sim'] ?? $btts['Yes']);
        }
        if (!empty($btts['Não']) || !empty($btts['No'])) {
            $c90['ambn'] = (float)($btts['Não'] ?? $btts['No']);
        }

        // Total de gols
        $tg = $e['odds']['total_goals']['odds'] ?? [];
        foreach ($tg as $k => $v) {
            $val = (float)$v;
            if (str_contains($k, 'Mais de 1.5') || str_contains($k, 'Over 1.5')) $c90['gmais2'] = $val;
            if (str_contains($k, 'Menos de 1.5') || str_contains($k, 'Under 1.5')) $c90['gmenos2'] = $val;
            if (str_contains($k, 'Mais de 2.5') || str_contains($k, 'Over 2.5')) $c90['gmais3'] = $val;
            if (str_contains($k, 'Menos de 2.5') || str_contains($k, 'Under 2.5')) $c90['gmenos3'] = $val;
            if (str_contains($k, 'Mais de 3.5') || str_contains($k, 'Over 3.5')) $c90['gmais4'] = $val;
            if (str_contains($k, 'Menos de 3.5') || str_contains($k, 'Under 3.5')) $c90['gmenos4'] = $val;
        }

        // 1° Tempo (pt)
        $ht = $e['odds']['halftime_result']['odds'] ?? [];
        if (!empty($ht['1'])) $pt['casa'] = (float)$ht['1'];
        if (!empty($ht['X'])) $pt['empate'] = (float)$ht['X'];
        if (!empty($ht['2'])) $pt['fora'] = (float)$ht['2'];

        return [
            '90' => $c90,
            'pt' => $pt,
            'st' => $st,
        ];
    }

    /**
     * Busca jogos da API Node.js (apisports)
     */
    public static function fetchFromNodeApi(string $mode = 'today', string $source = 'vaidebet'): array
    {
        // Garante tempo de execução suficiente para o processo Node terminar
        @set_time_limit(120);

        $nodeDir = ABSPATH . '/apifutebol/apisports';
        if (!file_exists($nodeDir . '/src/cli.js')) {
            throw new Exception("Script CLI do Node não encontrado em: {$nodeDir}/src/cli.js");
        }

        $sourceParam = (!empty($source) && $source !== 'all') ? $source : 'vaidebet';
        $json = null;

        // 1. Tenta via HTTP se a API Node estiver rodando na porta 8090
        $ch = curl_init("http://127.0.0.1:8090/api/football/{$mode}" . ($sourceParam ? "?source={$sourceParam}" : ''));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $httpRes = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $httpRes) {
            $json = json_decode($httpRes, true);
            if (!empty($json['data'])) {
                $json = $json['data'];
            }
        }

        // 2. Se não estiver rodando via HTTP, executa via CLI
        if (!$json || empty($json['events'])) {
            $sourceArg = "--source=" . escapeshellarg($sourceParam);
            $modeParam = "--mode=" . escapeshellarg($mode);
            $cmd = "node " . escapeshellarg($nodeDir . '/src/cli.js') . " {$modeParam} {$sourceArg}";

            $tempOut = tempnam(sys_get_temp_dir(), 'node_out_');
            $tempErr = tempnam(sys_get_temp_dir(), 'node_err_');

            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['file', $tempOut, 'w'],
                2 => ['file', $tempErr, 'w'],
            ];

            $proc = @proc_open($cmd, $descriptors, $pipes, $nodeDir);
            if (!is_resource($proc)) {
                @unlink($tempOut);
                @unlink($tempErr);
                throw new Exception("Não foi possível iniciar o processo Node.js.");
            }
            if (isset($pipes[0]) && is_resource($pipes[0])) {
                fclose($pipes[0]);
            }

            // Aguarda o processo Node terminar (geralmente conclui em 2 a 5 segundos)
            $deadline = microtime(true) + 40;
            while (microtime(true) < $deadline) {
                $status = proc_get_status($proc);
                if (!$status || !$status['running']) {
                    break;
                }
                usleep(100000); // 100ms
            }

            @proc_close($proc);

            $output = file_exists($tempOut) ? file_get_contents($tempOut) : '';
            $stderr = file_exists($tempErr) ? file_get_contents($tempErr) : '';
            @unlink($tempOut);
            @unlink($tempErr);

            if (!$output) {
                $errDetail = $stderr ? substr($stderr, 0, 300) : 'sem saída';
                throw new Exception("Não foi possível executar a API Node.js. ({$errDetail})");
            }

            $json = json_decode($output, true);
            if (!$json || empty($json['success'])) {
                $err = $json['error'] ?? substr($output, 0, 300);
                throw new Exception("Falha na API Node.js: {$err}");
            }
        }

        $events = $json['events'] ?? [];
        return self::normalizeNodeEvents($events, $mode === 'live');
    }

    /**
     * Normaliza eventos do Node.js
     */
    private static function normalizeNodeEvents(array $events, bool $forceLive = false): array
    {
        $normalized = [];
        $campeonatos = [];
        $times = [];
        $referencias = [];

        foreach ($events as $e) {
            $home = trim((string)($e['home'] ?? ''));
            $away = trim((string)($e['away'] ?? ''));

            if (empty($home) || empty($away)) {
                continue;
            }

            $league = trim((string)($e['league'] ?? $e['tournament'] ?? 'Futebol'));
            if (empty($league)) {
                $league = 'Futebol';
            }

            $date = date('Y-m-d');
            $time = date('H:i:00');

            if (!empty($e['schedule']['date_br']) && !empty($e['schedule']['time_br'])) {
                $date = trim((string)$e['schedule']['date_br']);
                $time = trim((string)$e['schedule']['time_br']);
                if (strlen($time) === 5) {
                    $time .= ':00';
                }
            } elseif (!empty($e['schedule']['start_time_unix'])) {
                $unix = (int)$e['schedule']['start_time_unix'];
                $date = date('Y-m-d', $unix);
                $time = date('H:i:s', $unix);
            } elseif (!empty($e['schedule']['start_time_br'])) {
                $str = (string)$e['schedule']['start_time_br'];
                $ts = strtotime($str);
                if ($ts && $ts > 0) {
                    $date = date('Y-m-d', $ts);
                    $time = date('H:i:s', $ts);
                } else {
                    $clean = str_replace('T', ' ', $str);
                    $parts = explode(' ', $clean);
                    $date = $parts[0] ?? $date;
                    if (!empty($parts[1])) {
                        $time = substr($parts[1], 0, 8);
                    }
                }
            }

            $country = trim((string)($e['country'] ?? ''));

            $isLive = $forceLive || !empty($e['is_live']);
            $scoreHome = (int)($e['score']['home'] ?? 0);
            $scoreAway = (int)($e['score']['away'] ?? 0);
            $period = $e['score']['period']['description'] ?? ($isLive ? 'Ao Vivo' : '');

            $src = $e['source'] ?? 'Node API';
            $refId = 'node_' . ($e['id'] ?? md5($home . $away . $date));

            // Odds principais
            $c90 = [];
            $mres = $e['odds']['match_result'] ?? $e['odds']['1X2'] ?? [];
            if (!empty($mres['1'])) $c90['casa'] = (float)$mres['1'];
            if (!empty($mres['X'])) $c90['empate'] = (float)$mres['X'];
            if (!empty($mres['2'])) $c90['fora'] = (float)$mres['2'];

            $dblc = $e['odds']['double_chance'] ?? [];
            if (!empty($dblc['1X'])) $c90['dplcasa'] = (float)$dblc['1X'];
            if (!empty($dblc['12'])) $c90['cof'] = (float)$dblc['12'];
            if (!empty($dblc['X2'])) $c90['dplfora'] = (float)$dblc['X2'];

            $btts = $e['odds']['both_teams_score'] ?? [];
            if (!empty($btts['yes']) || !empty($btts['Sim'])) $c90['amb'] = (float)($btts['yes'] ?? $btts['Sim']);
            if (!empty($btts['no']) || !empty($btts['Não'])) $c90['ambn'] = (float)($btts['no'] ?? $btts['Não']);

            // Processar mercados detalhados (markets)
            if (!empty($e['odds']['markets']) && is_array($e['odds']['markets'])) {
                foreach ($e['odds']['markets'] as $m) {
                    $mName = mb_strtolower(trim((string)($m['name'] ?? '')));
                    $mType = (int)($m['type_id'] ?? 0);
                    $choices = $m['choices'] ?? [];
                    if (!is_array($choices)) continue;

                    // Chance dupla
                    if ($mType === 10 || str_contains($mName, 'chance dupla') || str_contains($mName, 'dupla chance') || str_contains($mName, 'double chance')) {
                        foreach ($choices as $ch) {
                            $cName = mb_strtolower(trim((string)($ch['name'] ?? '')));
                            $cPrice = (float)($ch['price'] ?? 0);
                            $cType = (int)($ch['type_id'] ?? 0);
                            if ($cPrice <= 1) continue;
                            if ($cType === 9 || str_contains($cName, 'ou empate') || str_contains($cName, '1x')) {
                                $c90['dplcasa'] = $cPrice;
                            } elseif ($cType === 10 || (str_contains($cName, 'ou') && !str_contains($cName, 'empate')) || str_contains($cName, '12')) {
                                $c90['cof'] = $cPrice;
                            } elseif ($cType === 11 || str_contains($cName, 'empate ou') || str_contains($cName, 'x2')) {
                                $c90['dplfora'] = $cPrice;
                            }
                        }
                    }

                    // Ambas marcam
                    if (str_contains($mName, 'ambas') || str_contains($mName, 'both teams')) {
                        foreach ($choices as $ch) {
                            $cName = mb_strtolower(trim((string)($ch['name'] ?? '')));
                            $cPrice = (float)($ch['price'] ?? 0);
                            if ($cPrice <= 1) continue;
                            if ($cName === 'sim' || $cName === 'yes') {
                                $c90['amb'] = $cPrice;
                            } elseif ($cName === 'não' || $cName === 'nao' || $cName === 'no') {
                                $c90['ambn'] = $cPrice;
                            }
                        }
                    }

                    // Total de gols (Over/Under)
                    if ($mType === 18 || str_contains($mName, 'total de gol') || str_contains($mName, 'total goals')) {
                        foreach ($choices as $ch) {
                            $cName = mb_strtolower(trim((string)($ch['name'] ?? '')));
                            $cPrice = (float)($ch['price'] ?? 0);
                            if ($cPrice <= 1) continue;
                            if (str_contains($cName, '1.5') || str_contains($cName, '1,5')) {
                                if (str_contains($cName, 'mais') || str_contains($cName, 'over')) $c90['gmais2'] = $cPrice;
                                if (str_contains($cName, 'menos') || str_contains($cName, 'under')) $c90['gmenos2'] = $cPrice;
                            } elseif (str_contains($cName, '2.5') || str_contains($cName, '2,5')) {
                                if (str_contains($cName, 'mais') || str_contains($cName, 'over')) $c90['gmais3'] = $cPrice;
                                if (str_contains($cName, 'menos') || str_contains($cName, 'under')) $c90['gmenos3'] = $cPrice;
                            } elseif (str_contains($cName, '3.5') || str_contains($cName, '3,5')) {
                                if (str_contains($cName, 'mais') || str_contains($cName, 'over')) $c90['gmais4'] = $cPrice;
                                if (str_contains($cName, 'menos') || str_contains($cName, 'under')) $c90['gmenos4'] = $cPrice;
                            }
                        }
                    }
                }
            }

            // Estimativas de chance dupla caso não fornecidas explicitamente
            if (!empty($c90['casa']) && !empty($c90['empate']) && !empty($c90['fora'])) {
                if (empty($c90['dplcasa'])) $c90['dplcasa'] = round(1 / ((1 / $c90['casa']) + (1 / $c90['empate'])), 2);
                if (empty($c90['dplfora'])) $c90['dplfora'] = round(1 / ((1 / $c90['fora']) + (1 / $c90['empate'])), 2);
                if (empty($c90['cof'])) $c90['cof'] = round(1 / ((1 / $c90['casa']) + (1 / $c90['fora'])), 2);
            }

            $normalized[] = [
                'refid' => $refId,
                'source' => "Node ({$src})",
                'campeonato' => $league,
                'data' => $date,
                'hora' => $time,
                'country' => $country,
                'timecasa' => $home,
                'timefora' => $away,
                'timecasaimg' => 'default.jpg',
                'timeforaimg' => 'default.jpg',
                'timecasaplacar' => $scoreHome,
                'timeforaplacar' => $scoreAway,
                'is_live' => $isLive,
                'placar' => $isLive ? "{$scoreHome} x {$scoreAway}" : '',
                'periodo' => $period,
                'cotacoes' => [
                    '90' => $c90,
                    'pt' => [],
                    'st' => [],
                ],
            ];

            $campeonatos[$league] = true;
            $times[$home] = true;
            $times[$away] = true;
            $referencias[] = $refId;
        }

        return [
            'result' => 1,
            'source' => 'node',
            'count' => count($normalized),
            'campeonatos' => array_keys($campeonatos),
            'times' => array_keys($times),
            'referencias' => $referencias,
            'jogos' => $normalized,
        ];
    }

    /**
     * Busca jogos da The Odds API v4 (Externa)
     * Documentação: https://the-odds-api.com/liveapi/guides/v4/
     */
    public static function fetchFromTheOddsApi(string $apiKey, string $sport = 'soccer_brazil_campeonato', bool $isLive = false): array
    {
        $apiKey = trim($apiKey);
        if (empty($apiKey)) {
            throw new Exception("Informe uma chave de API válida para The Odds API. Obtenha sua chave em https://the-odds-api.com");
        }

        // Salva a chave usada para conveniência
        self::saveTheOddsApiKey($apiKey);

        $url = "https://api.the-odds-api.com/v4/sports/" . urlencode($sport) . "/odds/?apiKey=" . urlencode($apiKey) . "&regions=eu,us&markets=h2h,totals&oddsFormat=decimal";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BancaEsportiva/2.0');
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new Exception("Erro de conexão com The Odds API: {$err}");
        }

        $data = json_decode($body, true);
        if ($httpCode !== 200 || !is_array($data)) {
            $msg = $data['message'] ?? "The Odds API retornou status {$httpCode}";
            throw new Exception($msg);
        }

        return self::normalizeTheOddsEvents($data, $sport);
    }

    /**
     * Normaliza eventos da The Odds API v4
     */
    private static function normalizeTheOddsEvents(array $events, string $sport): array
    {
        $normalized = [];
        $campeonatos = [];
        $times = [];
        $referencias = [];

        foreach ($events as $e) {
            $home = trim((string)($e['home_team'] ?? ''));
            $away = trim((string)($e['away_team'] ?? ''));

            if (empty($home) || empty($away)) {
                continue;
            }

            $league = trim((string)($e['sport_title'] ?? $sport));
            $commence = $e['commence_time'] ?? date('c');
            $timestamp = strtotime($commence);

            $date = date('Y-m-d', $timestamp);
            $time = date('H:i:s', $timestamp);

            $refId = 'oddsapi_' . ($e['id'] ?? md5($home . $away . $date));

            // Processar cotações a partir dos bookmakers (pega Bet365 ou a primeira disponível)
            $c90 = [];
            $bookmakers = $e['bookmakers'] ?? [];

            // Dá preferência a Bet365 ou Betfair, senão usa a primeira
            $preferred = null;
            foreach ($bookmakers as $bm) {
                if (in_array(strtolower($bm['key']), ['bet365', 'betfair_ex_eu', 'pinnacle', 'unibet'])) {
                    $preferred = $bm;
                    break;
                }
            }
            if (!$preferred && !empty($bookmakers[0])) {
                $preferred = $bookmakers[0];
            }

            if ($preferred && !empty($preferred['markets'])) {
                foreach ($preferred['markets'] as $m) {
                    if ($m['key'] === 'h2h') {
                        foreach ($m['outcomes'] as $out) {
                            $name = $out['name'] ?? '';
                            $price = (float)($out['price'] ?? 1.0);
                            if ($name === $home) {
                                $c90['casa'] = $price;
                            } elseif ($name === $away) {
                                $c90['fora'] = $price;
                            } else {
                                $c90['empate'] = $price;
                            }
                        }
                    } elseif ($m['key'] === 'totals') {
                        foreach ($m['outcomes'] as $out) {
                            $point = (float)($out['point'] ?? 2.5);
                            $price = (float)($out['price'] ?? 1.0);
                            $type = strtolower($out['name'] ?? '');
                            if ($point == 2.5) {
                                if ($type === 'over') $c90['gmais3'] = $price;
                                if ($type === 'under') $c90['gmenos3'] = $price;
                            } elseif ($point == 1.5) {
                                if ($type === 'over') $c90['gmais2'] = $price;
                                if ($type === 'under') $c90['gmenos2'] = $price;
                            } elseif ($point == 3.5) {
                                if ($type === 'over') $c90['gmais4'] = $price;
                                if ($type === 'under') $c90['gmenos4'] = $price;
                            }
                        }
                    }
                }
            }

            // Se tem 1X2, calcula Duplas estimadas se não existirem
            if (!empty($c90['casa']) && !empty($c90['empate']) && !empty($c90['fora'])) {
                $c90['dplcasa'] = round(1 / ((1 / $c90['casa']) + (1 / $c90['empate'])), 2);
                $c90['dplfora'] = round(1 / ((1 / $c90['fora']) + (1 / $c90['empate'])), 2);
                $c90['cof'] = round(1 / ((1 / $c90['casa']) + (1 / $c90['fora'])), 2);
            }

            $country = '';
            $spLower = strtolower($sport);
            if (str_contains($spLower, 'brazil')) {
                $country = 'Brasil';
            } elseif (str_contains($spLower, 'epl') || str_contains($spLower, 'england')) {
                $country = 'Inglaterra';
            } elseif (str_contains($spLower, 'spain') || str_contains($spLower, 'la_liga')) {
                $country = 'Espanha';
            } elseif (str_contains($spLower, 'germany') || str_contains($spLower, 'bundesliga')) {
                $country = 'Alemanha';
            } elseif (str_contains($spLower, 'italy') || str_contains($spLower, 'serie_a')) {
                $country = 'Itália';
            } elseif (str_contains($spLower, 'france') || str_contains($spLower, 'ligue_one')) {
                $country = 'França';
            } elseif (str_contains($spLower, 'portugal')) {
                $country = 'Portugal';
            } elseif (str_contains($spLower, 'argentina')) {
                $country = 'Argentina';
            }

            $normalized[] = [
                'refid' => $refId,
                'source' => 'The Odds API',
                'campeonato' => $league,
                'data' => $date,
                'hora' => $time,
                'country' => $country,
                'timecasa' => $home,
                'timefora' => $away,
                'timecasaimg' => 'default.jpg',
                'timeforaimg' => 'default.jpg',
                'timecasaplacar' => 0,
                'timeforaplacar' => 0,
                'is_live' => false,
                'placar' => '',
                'periodo' => '',
                'cotacoes' => [
                    '90' => $c90,
                    'pt' => [],
                    'st' => [],
                ],
            ];

            $campeonatos[$league] = true;
            $times[$home] = true;
            $times[$away] = true;
            $referencias[] = $refId;
        }

        return [
            'result' => 1,
            'source' => 'theodds',
            'count' => count($normalized),
            'campeonatos' => array_keys($campeonatos),
            'times' => array_keys($times),
            'referencias' => $referencias,
            'jogos' => $normalized,
        ];
    }

    /**
     * Mapeia o nome do campeonato e/ou país informado para o ID correto em sis_options (ref='paises')
     */
    public static function resolvePaisId(string $campName, int $currentPais = 0, string $countryHint = ''): int
    {
        // Se já possui país válido no sistema (> 1 e != 1 que é o Brasil inativo 99)
        if ($currentPais > 1 && $currentPais != 1) {
            return $currentPais;
        }

        $text = mb_strtolower(trim($countryHint . ' ' . $campName), 'UTF-8');

        // Regras de detecção de país
        $rules = [
            38 => [ // Itália
                'itália', 'italia', 'italy', 'coppa italia', 'lega pro', 'taça de itália', 'taca de italia'
            ],
            12 => [ // Brasil
                'brasil', 'brazil', 'brasileir', 'copa do brasil', 'paulista', 'carioca', 'mineiro',
                'gaucho', 'gaúcho', 'baiano', 'cearense', 'paranaense', 'catarinense', 'goiano',
                'pernambucano', 'paraibano', 'potiguar', 'alagoano', 'sergipano', 'maranhense',
                'piauiense', 'amazonense', 'paraense', 'sul-mato-grossense', 'mato-grossense',
                'brasiliense', 'tocantinense', 'acriano', 'capixaba', 'série a', 'série b', 'série c', 'série d',
                'serie a', 'serie b', 'serie c', 'serie d', 'copa do nordeste', 'copa verde'
            ],
            49 => [ // Inglaterra
                'premier league', 'inglaterra', 'england', 'championship', 'efl', 'fa cup',
                'league one', 'league two', 'national league', 'isthmian', 'southern league',
                'northern premier', 'premiership', 'copa da liga inglesa'
            ],
            5 => [ // Espanha
                'laliga', 'la liga', 'espanha', 'spain', 'segunda division', 'copa del rey',
                'primera federacion', 'tercera'
            ],
            7 => [ // Alemanha
                'bundesliga', 'alemanha', 'germany', 'dfb pokal', '3. liga', 'regionalliga', 'oberliga'
            ],
            2 => [ // França
                'ligue 1', 'ligue 2', 'frança', 'france', 'coupe de france', 'nationale 1'
            ],
            9 => [ // Portugal
                'liga portugal', 'primeira liga', 'portugal', 'taça de portugal', 'taca de portugal', 'ligapro', 'segunda liga'
            ],
            6 => [ // Argentina
                'argentina', 'liga profesional', 'copa argentina', 'taça argentina', 'taca argentina', 'primera nacional', 'primera b'
            ],
            45 => [ // Estados Unidos
                'mls', 'usl', 'estados unidos', 'usa'
            ],
            37 => [ // Turquia
                'süper lig', 'super lig', 'turquia', 'turkey', '1 liga bank asya', 'taça da turquia', 'taca da turquia'
            ],
            44 => [ // Holanda
                'eredivisie', 'eerste divisie', 'holanda', 'netherlands'
            ],
            31 => [ // Bélgica
                'pro league', 'bélgica', 'belgica', 'belgium'
            ],
            8 => [ // México
                'liga mx', 'méxico', 'mexico', 'copa mx', 'expansion mx', 'promesas mx'
            ],
            21 => [ // Arábia Saudita
                'liga saudita', 'arábia saudita', 'arabia saudita', 'saudi'
            ],
            57 => [ // Japão
                'j.league', 'j-league', 'japão', 'japao', 'japan', 'taça emperor', 'taca emperor'
            ],
            55 => [ // China
                'super liga chinesa', 'liga da china', 'china'
            ],
            56 => [ // Chile
                'chile', 'taça do chile', 'taca do chile'
            ],
            39 => [ // Colômbia
                'colômbia', 'colombia', 'primera a'
            ],
            76 => [ // Uruguai
                'uruguai', 'uruguay', 'copa do uruguai', 'taca do uruguai'
            ],
            3 => [ // Dinamarca
                'dinamarca', 'denmark', 'superligaen'
            ],
            40 => [ // Suécia
                'suécia', 'suecia', 'sweden', 'allsvenskan', 'segunda liga sueca'
            ],
            59 => [ // Noruega
                'noruega', 'norway', 'eliteserien', 'elitserien', 'taça nm', 'taca nm'
            ],
            35 => [ // Grécia
                'grécia', 'grecia', 'greece', 'taça da grécia', 'taca da grecia'
            ],
            36 => [ // Suiça
                'suiça', 'suica', 'switzerland', 'liga challenge'
            ],
            33 => [ // Roménia
                'roménia', 'romenia', 'romania'
            ],
            46 => [ // Bulgária
                'bulgária', 'bulgaria', 'parva liga'
            ],
            54 => [ // Finlândia
                'finlândia', 'finlandia', 'finland', 'veikkausliiga', 'ykkosliiga'
            ],
            34 => [ // Libertadores
                'libertadores'
            ],
            65 => [ // Sudamericana
                'sudamericana', 'sul-americana'
            ],
            61 => [ // Liga dos Campeões da UEFA
                'champions league', 'liga dos campeões', 'liga dos campeoes', 'uefa champions', 'liga conferência', 'liga conferencia', 'conference league'
            ],
            62 => [ // Liga Europa UEFA
                'europa league', 'liga europa'
            ],
        ];

        foreach ($rules as $paisId => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($text, $kw)) {
                    return $paisId;
                }
            }
        }

        return 0;
    }

    /**
     * Importa / sincroniza os jogos selecionados com segurança no banco de dados
     */
    public static function importarJogos(array $jogos, array $options = []): array
    {
        if (empty($jogos)) {
            throw new Exception("Nenhum jogo selecionado para importação.");
        }

        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 1800);

        $pdo = Conn::getConn();
        $pdo->beginTransaction();

        try {
            $criados = 0;
            $alterados = 0;
            $timesCriados = 0;
            $campeonatosCriados = 0;
            $abertosSite = 0;

            $ajustarHorario = !empty($options['ajustarHorario']);
            $nowTimestamp = time();

            // Cache de resolução de times e campeonatos nesta execução
            $timesCache = [];
            $campeonatosCache = [];

            // Statements preparados com reativação de status = 1 garantida
            $stmtGetTime = $pdo->prepare("SELECT id, status FROM sis_times WHERE title = :title LIMIT 1");
            $stmtInsTime = $pdo->prepare("INSERT INTO sis_times (`insert`, `update`, `token`, `title`, `status`) VALUES (NOW(), NOW(), :token, :title, 1)");
            $stmtUpdTime = $pdo->prepare("UPDATE sis_times SET status = 1, `update` = NOW() WHERE id = :id");

            $stmtGetCamp = $pdo->prepare("SELECT id, pais, status FROM sis_campeonatos WHERE title = :title LIMIT 1");
            $stmtInsCamp = $pdo->prepare("INSERT INTO sis_campeonatos (`insert`, `update`, `token`, `title`, `status`, `pais`) VALUES (NOW(), NOW(), :token, :title, 1, :pais)");
            $stmtUpdCamp = $pdo->prepare("UPDATE sis_campeonatos SET pais = :pais, status = 1, `update` = NOW() WHERE id = :id");

            $stmtGetJogo = $pdo->prepare("SELECT id FROM sis_jogos WHERE (refimport = :refimport AND :refimport != '') OR (timecasa = :timecasa AND timefora = :timefora AND data = :data) LIMIT 1");

            $stmtUpdateJogo = $pdo->prepare("
                UPDATE sis_jogos SET 
                    `cotacoes` = :cotacoes,
                    `data` = :data,
                    `hora` = :hora,
                    `timecasa` = :timecasa,
                    `timefora` = :timefora,
                    `campeonato` = :campeonato,
                    `status` = 1,
                    `timecasaplacar` = :timecasaplacar,
                    `timeforaplacar` = :timeforaplacar,
                    `refimport` = :refimport,
                    `update` = NOW()
                WHERE id = :id
            ");

            $stmtInsertJogo = $pdo->prepare("
                INSERT INTO sis_jogos (
                    `insert`, `update`, `token`, `campeonato`, `datacadastro`,
                    `data`, `hora`, `timecasa`, `timefora`, `timecasaplacar`, `timeforaplacar`,
                    `status`, `apostas`, `maxapostas`, `valorapostas`,
                    `timecasaplacarprimeiro`, `timeforaplacarprimeiro`,
                    `timecasaplacarsegundo`, `timeforaplacarsegundo`,
                    `ganhadorprimeiro`, `ganhadorsegundo`,
                    `totalgols`, `totalgolsprimeiro`, `totalgolssegundo`,
                    `cotacoes`, `refimport`, `limite1`, `limite2`, `limite3`
                ) VALUES (
                    NOW(), NOW(), :token, :campeonato, CURDATE(),
                    :data, :hora, :timecasa, :timefora, :timecasaplacar, :timeforaplacar,
                    1, 0, 0, 0.00,
                    0, 0, 0, 0,
                    '', '',
                    0, 0, 0,
                    :cotacoes, :refimport, 300, 500, 1000
                )
            ");

            $limiteCotacao = (float)DadosModel::get()->getLimiteCotacao();

            foreach ($jogos as $j) {
                $homeName = trim((string)($j['timecasa'] ?? ''));
                $awayName = trim((string)($j['timefora'] ?? ''));
                $campName = trim((string)($j['campeonato'] ?? 'Futebol'));
                $dataJogo = trim((string)($j['data'] ?? date('Y-m-d')));
                $horaJogo = trim((string)($j['hora'] ?? date('H:i:s')));
                $refId = trim((string)($j['refid'] ?? ''));
                $scoreHome = (int)($j['timecasaplacar'] ?? 0);
                $scoreAway = (int)($j['timeforaplacar'] ?? 0);

                if (empty($homeName) || empty($awayName)) {
                    continue;
                }

                // Ajusta horário se for passado e a opção estiver ativada
                $tsJogo = strtotime("{$dataJogo} {$horaJogo}");
                $corteLimite = $nowTimestamp + ((int)DadosModel::get()->getMinutosJogo() + 5) * 60;
                if ($ajustarHorario && ($tsJogo === false || $tsJogo <= $corteLimite)) {
                    $tsAjustado = strtotime('+2 hours');
                    $dataJogo = date('Y-m-d', $tsAjustado);
                    $horaJogo = date('H:i:00', $tsAjustado);
                    $tsJogo = strtotime("{$dataJogo} {$horaJogo}");
                }

                if ($tsJogo > $corteLimite) {
                    $abertosSite++;
                }

                // 1. Resolve Time Casa
                if (!isset($timesCache[$homeName])) {
                    $stmtGetTime->execute(['title' => $homeName]);
                    $row = $stmtGetTime->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $timesCache[$homeName] = (int)$row['id'];
                        if ((int)($row['status'] ?? 1) != 1) {
                            $stmtUpdTime->execute(['id' => $row['id']]);
                        }
                    } else {
                        $stmtInsTime->execute([
                            'token' => Utils::gerarToken(),
                            'title' => $homeName,
                        ]);
                        $timesCache[$homeName] = (int)$pdo->lastInsertId();
                        $timesCriados++;
                    }
                }
                $timecasaId = $timesCache[$homeName];

                // 2. Resolve Time Fora
                if (!isset($timesCache[$awayName])) {
                    $stmtGetTime->execute(['title' => $awayName]);
                    $row = $stmtGetTime->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $timesCache[$awayName] = (int)$row['id'];
                        if ((int)($row['status'] ?? 1) != 1) {
                            $stmtUpdTime->execute(['id' => $row['id']]);
                        }
                    } else {
                        $stmtInsTime->execute([
                            'token' => Utils::gerarToken(),
                            'title' => $awayName,
                        ]);
                        $timesCache[$awayName] = (int)$pdo->lastInsertId();
                        $timesCriados++;
                    }
                }
                $timeforaId = $timesCache[$awayName];

                // 3. Resolve Campeonato
                $countryHint = trim((string)($j['country'] ?? ''));
                $targetPaisId = self::resolvePaisId($campName, 0, $countryHint);

                if (!isset($campeonatosCache[$campName])) {
                    $stmtGetCamp->execute(['title' => $campName]);
                    $row = $stmtGetCamp->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $campeonatoId = (int)$row['id'];
                        $campPais = (int)($row['pais'] ?? 0);
                        $campStatus = (int)($row['status'] ?? 1);
                        $newPais = (($campPais === 0 || $campPais === 1) && $targetPaisId > 0) ? $targetPaisId : $campPais;
                        if ($newPais != $campPais || $campStatus != 1) {
                            $stmtUpdCamp->execute([
                                'pais' => $newPais > 0 ? $newPais : ($targetPaisId > 0 ? $targetPaisId : 12),
                                'id' => $campeonatoId,
                            ]);
                        }
                        $campeonatosCache[$campName] = $campeonatoId;
                    } else {
                        $stmtInsCamp->execute([
                            'token' => Utils::gerarToken(),
                            'title' => $campName,
                            'pais' => $targetPaisId > 0 ? $targetPaisId : 12,
                        ]);
                        $campeonatosCache[$campName] = (int)$pdo->lastInsertId();
                        $campeonatosCriados++;
                    }
                }
                $campeonatoId = $campeonatosCache[$campName];

                // 4. Formata cotações
                $cotacoes = is_array($j['cotacoes'] ?? null) ? $j['cotacoes'] : [];
                if ($limiteCotacao > 0 && !empty($cotacoes)) {
                    foreach ($cotacoes as $tempo => &$vals) {
                        if (is_array($vals)) {
                            foreach ($vals as $k => &$v) {
                                $v = min($limiteCotacao, (float)$v);
                            }
                        }
                    }
                }
                $cotacoesJson = json_encode($cotacoes, JSON_UNESCAPED_UNICODE);

                // 5. Verifica se jogo já existe
                $stmtGetJogo->execute([
                    'refimport' => $refId,
                    'timecasa' => $timecasaId,
                    'timefora' => $timeforaId,
                    'data' => $dataJogo,
                ]);
                $jogoRow = $stmtGetJogo->fetch(PDO::FETCH_ASSOC);

                if ($jogoRow) {
                    // Update
                    $stmtUpdateJogo->execute([
                        'id' => (int)$jogoRow['id'],
                        'cotacoes' => $cotacoesJson,
                        'data' => $dataJogo,
                        'hora' => $horaJogo,
                        'timecasa' => $timecasaId,
                        'timefora' => $timeforaId,
                        'campeonato' => $campeonatoId,
                        'timecasaplacar' => $scoreHome,
                        'timeforaplacar' => $scoreAway,
                        'refimport' => $refId,
                    ]);
                    $alterados++;
                } else {
                    // Insert
                    $stmtInsertJogo->execute([
                        'token' => Utils::gerarToken(),
                        'campeonato' => $campeonatoId,
                        'data' => $dataJogo,
                        'hora' => $horaJogo,
                        'timecasa' => $timecasaId,
                        'timefora' => $timeforaId,
                        'timecasaplacar' => $scoreHome,
                        'timeforaplacar' => $scoreAway,
                        'cotacoes' => $cotacoesJson,
                        'refimport' => $refId,
                    ]);
                    $criados++;
                }
            }

            $pdo->commit();

            $total = $criados + $alterados;
            return [
                'result' => 1,
                'criados' => $criados,
                'alterados' => $alterados,
                'times_criados' => $timesCriados,
                'campeonatos_criados' => $campeonatosCriados,
                'abertos_site' => $abertosSite,
                'total' => $total,
                'message' => "Importação concluída com sucesso!\n\n✓ {$criados} jogos novos inseridos\n✓ {$alterados} jogos atualizados\n✓ {$abertosSite} partidas disponíveis para apostas na Página Inicial\n✓ {$timesCriados} novos times cadastrados\n✓ {$campeonatosCriados} novos campeonatos cadastrados\nTotal processado: {$total} partidas.",
            ];

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
