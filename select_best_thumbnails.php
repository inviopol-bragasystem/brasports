<?php
/**
 * Encontra as melhores imagens (character/bg) para thumbnails dos jogos Popok
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

$sourceBase = __DIR__ . '/games/popok/public/games';
$destBase = __DIR__ . '/imagens/cassino/popok';
$count = 0;

echo "<h1>Selecionando melhores thumbnails para cada jogo</h1><pre>";

if (!is_dir($destBase)) {
    mkdir($destBase, 0755, true);
}

foreach ($games as $game) {
    $gameDir = "$sourceBase/{$game['code']}/assets/{$game['cdn']}/desktop/assets/webp";
    
    if (!is_dir($gameDir)) {
        // Tentar mobile
        $gameDir = "$sourceBase/{$game['code']}/assets/{$game['cdn']}/mobile/assets/webp";
    }
    
    if (!is_dir($gameDir)) {
        echo "✗ {$game['code']} - Diretório não encontrado\n";
        continue;
    }
    
    // Ordem de preferência: character, bg, logo
    $candidates = [
        'character.webp',
        'character1.webp',
        'character-1.webp',
        'character2.webp',
        'character-2.webp',
        'character3.webp',
        'character-3.webp',
        'bg.webp',
        'bg1.webp',
        'bg-1.webp',
        'bg2.webp',
        'bg-2.webp',
        'bg3.webp',
        'bg-3.webp',
        'logo.webp',
    ];
    
    $found = false;
    foreach ($candidates as $candidate) {
        $src = "$gameDir/$candidate";
        if (file_exists($src)) {
            $dest = "$destBase/{$game['code']}.webp";
            if (copy($src, $dest)) {
                echo "✓ {$game['code']} -> {$candidate}\n";
                $count++;
                $found = true;
                break;
            }
        }
    }
    
    if (!$found) {
        echo "✗ {$game['code']} - Nenhuma imagem encontrada\n";
    }
}

echo "\n✓ Concluído! {$count} thumbnails atualizados.\n";
echo "</pre>";