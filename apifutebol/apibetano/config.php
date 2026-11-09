<?php

/**
 * Configuração da API Betano Sports
 */
return [
    // Origem dos dados (Betano BR)
    'betano' => [
        'base_url' => 'https://www.betano.bet.br',
        'language_id' => 5,   // pt-BR
        'operator_id' => 8,   // Betano BR
        'platform_type' => 1, // Web
        'timeout' => 25,
        // No Windows, preferir curl.exe evita bloqueio Cloudflare no cURL do PHP
        'prefer_binary_curl' => true,
        'curl_binary' => null, // ex: C:\Windows\System32\curl.exe
        'cookie_dir' => __DIR__ . '/cache',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'headers' => [
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Referer' => 'https://www.betano.bet.br/',
            'Origin' => 'https://www.betano.bet.br',
        ],
    ],

    // Cache em arquivo (segundos)
    'cache' => [
        'enabled' => true,
        'path' => __DIR__ . '/cache',
        'ttl' => [
            'default' => 60,
            'sports' => 300,
            'sport' => 180,
            'league' => 60,
            'events_today' => 45,
            'top_events' => 45,
            'event' => 30,
            'live' => 8,
            'coupons' => 60,
        ],
    ],

    // CORS
    'cors' => [
        'enabled' => true,
        'allow_origin' => '*',
        'allow_methods' => 'GET, OPTIONS',
        'allow_headers' => 'Content-Type, Authorization, X-Requested-With',
    ],

    // Esportes suportados (slug Betano => meta)
    'sports' => [
        'futebol' => ['id' => 'FOOT', 'name' => 'Futebol', 'icon' => '⚽'],
        'basquete' => ['id' => 'BASK', 'name' => 'Basquete', 'icon' => '🏀'],
        'tenis' => ['id' => 'TENN', 'name' => 'Tênis', 'icon' => '🎾'],
        'voleibol' => ['id' => 'VOLL', 'name' => 'Vôlei', 'icon' => '🏐'],
        'handebol' => ['id' => 'HAND', 'name' => 'Handebol', 'icon' => '🤾'],
        'futsal' => ['id' => 'FUTS', 'name' => 'Futsal', 'icon' => '⚽'],
        'futebol-americano' => ['id' => 'AMFO', 'name' => 'Futebol Americano', 'icon' => '🏈'],
        'baseball' => ['id' => 'BASE', 'name' => 'Baseball', 'icon' => '⚾'],
        'hockey' => ['id' => 'ICEH', 'name' => 'Hockey', 'icon' => '🏒'],
        'mma' => ['id' => 'MMAF', 'name' => 'MMA', 'icon' => '🥊'],
        'boxe' => ['id' => 'BOXI', 'name' => 'Boxe', 'icon' => '🥊'],
        'tenis-de-mesa' => ['id' => 'TABL', 'name' => 'Tênis de Mesa', 'icon' => '🏓'],
        'formula-1' => ['id' => 'FORM', 'name' => 'Fórmula 1', 'icon' => '🏎️'],
        'ciclismo' => ['id' => 'CYCL', 'name' => 'Ciclismo', 'icon' => '🚴'],
        'golf' => ['id' => 'GOLF', 'name' => 'Golf', 'icon' => '⛳'],
        'esports' => ['id' => 'ESPS', 'name' => 'Esports', 'icon' => '🎮'],
        'darts' => ['id' => 'DART', 'name' => 'Dardos', 'icon' => '🎯'],
        'snooker' => ['id' => 'SNOO', 'name' => 'Snooker', 'icon' => '🎱'],
        'rugby-union' => ['id' => 'RUGU', 'name' => 'Rugby', 'icon' => '🏉'],
        'motorsports' => ['id' => 'MOTS', 'name' => 'Motorsports', 'icon' => '🏁'],
    ],

    'app' => [
        'name' => 'API Betano Sports',
        'version' => '1.1.0',
        'timezone' => 'America/Sao_Paulo',
        'debug' => false,
    ],
];
