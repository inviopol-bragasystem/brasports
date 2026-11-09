<?php
/**
 * Script de Integração Popok - Execute este arquivo para cadastrar todos os jogos
 * 
 * Uso: Acesse http://seusite.com/popok_setup.php no navegador
 * Ou execute via CLI: php popok_setup.php
 */

require_once __DIR__ . '/inc.config.php';

use app\core\crud\Conn;

$pdo = Conn::getConn();

echo "<h1>Integração Popok - Setup</h1>";
echo "<pre>";

// 1. Adicionar colunas se não existem
echo "1. Verificando colunas...\n";
try {
    $pdo->exec("ALTER TABLE casino_games ADD COLUMN IF NOT EXISTS provider VARCHAR(20) DEFAULT 'PGSOFT'");
    $pdo->exec("ALTER TABLE casino_games ADD COLUMN IF NOT EXISTS game_path VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE casino_games ADD COLUMN IF NOT EXISTS popok_game_id VARCHAR(20) DEFAULT NULL");
    echo "   ✓ Colunas verificadas\n";
} catch (Exception $e) {
    echo "   ✗ Erro: " . $e->getMessage() . "\n";
}

// 2. Atualizar jogos existentes
echo "2. Atualizando jogos existentes...\n";
$pdo->exec("UPDATE casino_games SET provider = 'PGSOFT' WHERE provider IS NULL");
echo "   ✓ Jogos PGSoft atualizados\n";

// 3. Remover jogos antigos do Popok
echo "3. Removendo jogos antigos do Popok...\n";
$pdo->exec("DELETE FROM casino_games WHERE provider = 'POPOK'");
echo "   ✓ Jogos antigos removidos\n";

// 4. Inserir todos os jogos do Popok
echo "4. Inserindo jogos do Popok...\n";

$games = [
    ['Yo Dragon', 'yo-dragon', '167', '/games/popok/public/games/yo-dragon', '167', '/games/popok/public/games/yo-dragon/assets/goDragon/desktop/assets/webp/logo.webp', 1, 1],
    ['Alpaca Pays', 'alpaca-pays', '157', '/games/popok/public/games/alpaca-pays', '157', '/games/popok/public/games/alpaca-pays/assets/alpacaPays/desktop/assets/webp/logo.webp', 1, 0],
    ['Rico Tiger', 'rico-tiger', '203', '/games/popok/public/games/rico-tiger', '203', '/games/popok/public/games/rico-tiger/assets/ricoTiger/desktop/assets/webp/logo.webp', 1, 0],
    ['Macaco Milionário', 'macaco-milionario', '279', '/games/popok/public/games/macaco-milionario', '279', '/games/popok/public/games/macaco-milionario/assets/macacoMilionario/desktop/assets/webp/logo.webp', 1, 0],
    ['Sumo Baby', 'sumo-baby', '163', '/games/popok/public/games/sumo-baby', '163', '/games/popok/public/games/sumo-baby/assets/babySumo/desktop/assets/webp/logo.webp', 1, 0],
    ['Fortune Panda', 'fortune-panda', '162', '/games/popok/public/games/fortune-panda', '162', '/games/popok/public/games/fortune-panda/assets/fortunePanda/desktop/assets/webp/logo.webp', 1, 1],
    ['Rico Rabbit', 'rico-rabbit', '195', '/games/popok/public/games/rico-rabbit', '195', '/games/popok/public/games/rico-rabbit/assets/ricoRabbit/desktop/assets/webp/logo.webp', 1, 0],
    ['Eye on the Money', 'eye-on-the-money', '181', '/games/popok/public/games/eye-on-the-money', '181', '/games/popok/public/games/eye-on-the-money/assets/eyeOnTheMoney/desktop/assets/webp/logo.webp', 1, 0],
    ['Rico Gorila', 'rico-gorila', '164', '/games/popok/public/games/rico-gorila', '164', '/games/popok/public/games/rico-gorila/assets/gorillaGoldRush/desktop/assets/webp/logo.webp', 1, 0],
    ['Cavalo de Fogo', 'cavalo-de-fogo', '294', '/games/popok/public/games/cavalo-de-fogo', '294', '/games/popok/public/games/cavalo-de-fogo/assets/cavaloDeFogoFortune/desktop/assets/webp/logo.webp', 1, 0],
    ['Rico DJ', 'rico-dj', '175', '/games/popok/public/games/rico-dj', '175', '/games/popok/public/games/rico-dj/assets/chameleon/desktop/assets/webp/logo.webp', 1, 0],
    ['Papai Noel da Fortuna', 'papai-noel-da-fortuna', '169', '/games/popok/public/games/papai-noel-da-fortuna', '169', '/games/popok/public/games/papai-noel-da-fortuna/assets/papaiNoeldafortuna/desktop/assets/webp/logo.webp', 1, 0],
];

$stmt = $pdo->prepare("INSERT INTO casino_games (name, game_code, game_id, category, provider, game_path, popok_game_id, image, rtp, active, featured, ordem) 
    VALUES (:name, :code, :gid, 'Popok', 'POPOK', :path, :pgid, :img, 96.00, 1, :feat, :ord)
    ON DUPLICATE KEY UPDATE name = VALUES(name), image = VALUES(image), provider = VALUES(provider), game_path = VALUES(game_path)");

$count = 0;
foreach ($games as $i => $g) {
    try {
        $stmt->execute([
            'name' => $g[0],
            'code' => $g[1],
            'gid' => $g[2],
            'path' => $g[3],
            'pgid' => $g[4],
            'img' => $g[5],
            'feat' => $g[6],
            'ord' => $i + 1
        ]);
        $count++;
        echo "   ✓ {$g[0]} ({$g[1]})\n";
    } catch (Exception $e) {
        echo "   ✗ {$g[0]}: " . $e->getMessage() . "\n";
    }
}

echo "\n5. Total de jogos inseridos: {$count}\n";

// 6. Verificar jogos cadastrados
echo "\n6. Jogos Popok cadastrados:\n";
$stmt = $pdo->query("SELECT id, name, game_code, provider, active, image FROM casino_games WHERE provider = 'POPOK' ORDER BY ordem ASC");
$gamesDb = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($gamesDb as $g) {
    echo "   - {$g['name']} ({$g['game_code']}) - Ativo: {$g['active']}\n";
}

echo "\n✓ Integração concluída com sucesso!\n";
echo "</pre>";
echo "<p><a href='cassino'>Ir para o Cassino</a></p>";
