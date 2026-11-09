-- ============================================
-- INTEGRAÇÃO POPOK COMPLETA - Todos os Jogos
-- ============================================

-- 1. Adicionar colunas se não existem
ALTER TABLE casino_games ADD COLUMN IF NOT EXISTS provider VARCHAR(20) DEFAULT 'PGSOFT';
ALTER TABLE casino_games ADD COLUMN IF NOT EXISTS game_path VARCHAR(255) DEFAULT NULL;
ALTER TABLE casino_games ADD COLUMN IF NOT EXISTS popok_game_id VARCHAR(20) DEFAULT NULL;

-- 2. Atualizar jogos existentes
UPDATE casino_games SET provider = 'PGSOFT' WHERE provider IS NULL;

-- 3. Remover jogos antigos do Popok (para duplicar)
DELETE FROM casino_games WHERE provider = 'POPOK';

-- 4. Inserir TODOS os jogos do Popok com imagens corretas
INSERT INTO casino_games (name, game_code, game_id, category, provider, game_path, popok_game_id, image, rtp, active, featured, ordem) VALUES
('Yo Dragon', 'yo-dragon', '167', 'Popok', 'POPOK', '/games/popok/public/games/yo-dragon', '167', '/games/popok/public/games/yo-dragon/assets/goDragon/desktop/assets/webp/logo.webp', 96.00, 1, 1, 1),
('Alpaca Pays', 'alpaca-pays', '157', 'Popok', 'POPOK', '/games/popok/public/games/alpaca-pays', '157', '/games/popok/public/games/alpaca-pays/assets/alpacaPays/desktop/assets/webp/logo.webp', 96.00, 1, 0, 2),
('Rico Tiger', 'rico-tiger', '203', 'Popok', 'POPOK', '/games/popok/public/games/rico-tiger', '203', '/games/popok/public/games/rico-tiger/assets/ricoTiger/desktop/assets/webp/logo.webp', 96.00, 1, 0, 3),
('Macaco Milionário', 'macaco-milionario', '279', 'Popok', 'POPOK', '/games/popok/public/games/macaco-milionario', '279', '/games/popok/public/games/macaco-milionario/assets/macacoMilionario/desktop/assets/webp/logo.webp', 96.00, 1, 0, 4),
('Sumo Baby', 'sumo-baby', '163', 'Popok', 'POPOK', '/games/popok/public/games/sumo-baby', '163', '/games/popok/public/games/sumo-baby/assets/babySumo/desktop/assets/webp/logo.webp', 96.00, 1, 0, 5),
('Fortune Panda', 'fortune-panda', '162', 'Popok', 'POPOK', '/games/popok/public/games/fortune-panda', '162', '/games/popok/public/games/fortune-panda/assets/fortunePanda/desktop/assets/webp/logo.webp', 96.00, 1, 1, 6),
('Rico Rabbit', 'rico-rabbit', '195', 'Popok', 'POPOK', '/games/popok/public/games/rico-rabbit', '195', '/games/popok/public/games/rico-rabbit/assets/ricoRabbit/desktop/assets/webp/logo.webp', 96.00, 1, 0, 7),
('Eye on the Money', 'eye-on-the-money', '181', 'Popok', 'POPOK', '/games/popok/public/games/eye-on-the-money', '181', '/games/popok/public/games/eye-on-the-money/assets/eyeOnTheMoney/desktop/assets/webp/logo.webp', 96.00, 1, 0, 8),
('Rico Gorila', 'rico-gorila', '164', 'Popok', 'POPOK', '/games/popok/public/games/rico-gorila', '164', '/games/popok/public/games/rico-gorila/assets/gorillaGoldRush/desktop/assets/webp/logo.webp', 96.00, 1, 0, 9),
('Cavalo de Fogo', 'cavalo-de-fogo', '294', 'Popok', 'POPOK', '/games/popok/public/games/cavalo-de-fogo', '294', '/games/popok/public/games/cavalo-de-fogo/assets/cavaloDeFogoFortune/desktop/assets/webp/logo.webp', 96.00, 1, 0, 10),
('Rico DJ', 'rico-dj', '175', 'Popok', 'POPOK', '/games/popok/public/games/rico-dj', '175', '/games/popok/public/games/rico-dj/assets/chameleon/desktop/assets/webp/logo.webp', 96.00, 1, 0, 11),
('Papai Noel da Fortuna', 'papai-noel-da-fortuna', '169', 'Popok', 'POPOK', '/games/popok/public/games/papai-noel-da-fortuna', '169', '/games/popok/public/games/papai-noel-da-fortuna/assets/papaiNoeldafortuna/desktop/assets/webp/logo.webp', 96.00, 1, 0, 12);

-- 5. Verificar jogos cadastrados
SELECT id, name, game_code, provider, category, active, image FROM casino_games WHERE provider = 'POPOK' ORDER BY ordem ASC;
