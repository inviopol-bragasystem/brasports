<?php
/**
 * Busca TODAS as imagens disponíveis no CDN para cada jogo e lista
 */

$games = [
    ['code' => 'yo-dragon', 'cdn' => 'goDragon'],
    ['code' => 'alpaca-pays', 'cdn' => 'alpacaPays'],
    ['code' => 'rico-tiger', 'cdn' => 'ricoTiger'],
    ['code' => 'macaco-milionario', 'cdn' => 'macacoMilionario'],
    ['code' => 'sumo-baby', 'cdn' => 'babySumo'],
    ['code' => 'fortune-panda', 'cdn' => 'fortunePanda'],
    ['code' => 'rico-rabbit', 'cdn' => 'ricoRabbit'],
    ['code' => 'eye-on-the-money', 'cdn' => 'eyeOnTheMoney'],
    ['code' => 'rico-gorila', 'cdn' => 'gorillaGoldRush'],
    ['code' => 'cavalo-de-fogo', 'cdn' => 'cavaloDeFogoFortune'],
    ['code' => 'rico-dj', 'cdn' => 'chameleon'],
    ['code' => 'papai-noel-da-fortuna', 'cdn' => 'papaiNoeldafortuna'],
];

$cdnBase = 'https://res.pokgaming91.com';

echo "<h1>Listando TODAS as imagens do CDN para cada jogo</h1><pre>";

foreach ($games as $game) {
    echo "\n=== {$game['code']} ({$game['cdn']}) ===\n";
    
    // Tentar listar diretório mobile
    $mobileDir = "$cdnBase/{$game['cdn']}/mobile/assets/webp/";
    $desktopDir = "$cdnBase/{$game['cdn']}/desktop/assets/webp/";
    
    // Tentar acessar e ver o que retorna (pode não listar diretório)
    $patterns = [
        'cover.webp', 'thumbnail.webp', 'poster.webp', 'logo.webp',
        'bg.webp', 'bg1.webp', 'bg2.webp', 'bg3.webp', 'bg4.webp', 'bg5.webp',
        'background.webp', 'background1.webp', 'background2.webp',
        'loading.webp', 'splash.webp', 'splash_screen.webp',
        'main.webp', 'main_bg.webp', 'game_bg.webp',
        'character.webp', 'character1.webp', 'character2.webp',
        'icon.webp', 'icon_large.webp',
    ];
    
    foreach ($patterns as $pattern) {
        $url = "$cdnBase/{$game['cdn']}/mobile/assets/webp/$pattern";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo "  ✓ MOBILE/$pattern (HTTP $httpCode, " . number_format($contentLength/1024, 1) . " KB)\n";
        }
    }
    
    foreach ($patterns as $pattern) {
        $url = "$cdnBase/{$game['cdn']}/desktop/assets/webp/$pattern";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo "  ✓ DESKTOP/$pattern (HTTP $httpCode, " . number_format($contentLength/1024, 1) . " KB)\n";
        }
    }
}

echo "\n✓ Concluído!\n";
echo "</pre>";