<?php

use app\APP;
use app\core\crud\Conn;
use app\helpers\Cache;
use app\helpers\Seo;
use app\helpers\Session;
use app\modules\admin\Admin;
use app\modules\api\API;
use app\modules\auth\Auth;
use app\modules\cron\Cron;
use app\modules\website\Site;
use app\modules\website2\Site2;
use app\modules\website3\Site3;
use app\modules\website4\Site4;
use app\modules\website5\Site5;
use app\modules\website6\Site6;
use app\modules\website7\Site7;
use app\modules\website8\Site8;
use app\modules\website9\Site9;
use app\modules\website10\Site10;
use app\modules\website11\Site11;
use app\modules\website12\Site12;
use app\modules\website13\Site13;
use app\modules\website14\Site14;

/*
|--------------------------------------------------------------------------
| 1. CONFIGURAÇÕES PRINCIPAIS (EDITE APENAS ESTA SEÇÃO NA INSTALAÇÃO)
|--------------------------------------------------------------------------
| Altere apenas estes dados. Todas as outras partes do sistema (incluindo
| conexao.php, jogos.php, atualiza.php, rotas e painel) são unificadas aqui!
*/

// --- BANCO DE DADOS ---
$DB_HOST = 'localhost';
$DB_USER = 'sql_chinelopg777_grupo555_net_br';
$DB_PASS = '616e27118903a'; // Senha do banco de dados MySQL/MariaDB
$DB_NAME = 'sql_chinelopg777_grupo555_net_br';           // Nome do banco de dados

// --- DOMÍNIO / URL DO SITE ---
// Deixe vazio ('') para detectar automaticamente o domínio (funciona direto em localhost ou cPanel),
// ou se preferir fixe a URL (exemplo: 'https://meusite.com').
$SITE_URL = '';

// --- INFORMAÇÕES BÁSICAS ---
$SITE_TITLE = 'Banca Esportiva';
$SITE_EMAIL = 'contato@bancaesportiva.com';

// --- LAYOUT DA APLICAÇÃO ---
// Opções disponíveis:
// 'site1' -> Layout Bonanza Sport (Padrão)
// 'site2' -> Layout Alternativo 2
// 'site3' -> Layout Alternativo 3
// 'site4' -> Bet365 (Verde & Amarelo)
// 'site5' -> Banca de Apostas (Vermelho & Preto)
// 'site6' -> Betano (Laranja & Grafite)
// 'site7' -> Primor Bet (Cyber Neon Verde)
// 'site8' -> Superbet (Verde Esmeralda & Escuro)
// 'site9' -> Moderno Escuro (Minimalista)
// 'site10' -> DP Sports (Azul Royal & Marinho)
// 'site11' -> Bet24 (baseado no antigo bet24hs.site/v4, claro/escuro)
// 'site12' / 'goldesporte' -> GoldEsporte (baseado no antigo goldesportebet.com/classico/, dourado & preto)
// 'site13' / '99play' -> 99Play (baseado no 99play.io, azul noturno & elétrico)
// 'site14' / 'topsportsbet' -> TopSportsBet (baseado em topsportsbet.com.br, grafite & verde)
$SITE_LAYOUT = 'site14';

// --- MODO DEPURAR ERROS (DEBUG) ---
// false = Oculta erros na tela (Recomendado para produção)
// true  = Exibe erros detalhados na tela caso ocorra algum problema
$DEBUG_MODE = false;

/*
|--------------------------------------------------------------------------
| FIM DA SEÇÃO EDITÁVEL - NÃO É NECESSÁRIO MODIFICAR NADA ABAIXO
|--------------------------------------------------------------------------
*/

include_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__ . '/app/boot.inc.php';

// Controle de exibição de erros
if ($DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Configuração de diretórios temporários
$cacheDir = __DIR__ . DIRECTORY_SEPARATOR . '_temp' . DIRECTORY_SEPARATOR . 'cache';
$sessionDir = __DIR__ . DIRECTORY_SEPARATOR . '_temp' . DIRECTORY_SEPARATOR . 'session';
if (!file_exists($cacheDir)) {
    @mkdir($cacheDir, 0777, true);
}
if (!file_exists($sessionDir)) {
    @mkdir($sessionDir, 0777, true);
}
Cache::setDirectory($cacheDir);
Session::setDirectory($sessionDir);

// Detecção automática da URL se não estiver definida
if (empty($SITE_URL)) {
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $isHttps = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1))
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false)
        || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
        || (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) === 'on')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    $protocol = $isHttps ? 'https://' : 'http://';
    $SITE_URL = $protocol . $host;
}
$SITE_URL = rtrim($SITE_URL, '/');

$config = [
    'config' => [
        'title' => $SITE_TITLE,
        'dominio' => $SITE_URL,
        'email' => $SITE_EMAIL,
        'uri' => $SITE_URL,
        'api' => 'https://apijogos.com/betsports2.php',
        'redes' => [
            'facebook' => '',
            'twitter' => '',
            'instagram' => '',
        ],
        'upload' => [
            'imagens' => 'imagens',
            'arquivos' => 'arquivos',
        ]
    ],
];

$config['modules'] = [
    'site' => null,
    'site1' => ['path' => 'app\\modules\\website', 'class' => Site::class],
    'site2' => ['path' => 'app\\modules\\website2', 'class' => Site2::class],
    'site3' => ['path' => 'app\\modules\\website3', 'class' => Site3::class],
    'site4' => ['path' => 'app\\modules\\website4', 'class' => Site4::class],
    'site5' => ['path' => 'app\\modules\\website5', 'class' => Site5::class],
    'site6' => ['path' => 'app\\modules\\website6', 'class' => Site6::class],
    'site7' => ['path' => 'app\\modules\\website7', 'class' => Site7::class],
    'site8' => ['path' => 'app\\modules\\website8', 'class' => Site8::class],
    'site9' => ['path' => 'app\\modules\\website9', 'class' => Site9::class],
    'site10' => ['path' => 'app\\modules\\website10', 'class' => Site10::class],
    'site11' => ['path' => 'app\\modules\\website11', 'class' => Site11::class],
    'site12' => ['path' => 'app\\modules\\website12', 'class' => Site12::class],
    'goldesporte' => ['path' => 'app\\modules\\website12', 'class' => Site12::class],
    'site13' => ['path' => 'app\\modules\\website13', 'class' => Site13::class],
    '99play' => ['path' => 'app\\modules\\website13', 'class' => Site13::class],
    'site14' => ['path' => 'app\\modules\\website14', 'class' => Site14::class],
    'topsportsbet' => ['path' => 'app\\modules\\website14', 'class' => Site14::class],
    'entrar' => ['path' => 'app\\modules\\auth', 'class' => Auth::class],
    'cron' => ['path' => 'app\\modules\\cron', 'class' => Cron::class],
    'localizacao' => ['path' => 'app\\modules\\localizacao'],
    'admin' => ['path' => 'app\\modules\\admin', 'class' => Admin::class],
    'cdn' => ['path' => 'app\\modules\\cdn'],
    'notificacoes' => ['path' => 'app\\modules\\notificacoes'],
    'api' => ['path' => 'app\\modules\\api', 'class' => API::class],
];

// Define o layout ativo do site
$config['modules']['site'] = $config['modules'][$SITE_LAYOUT] ?? $config['modules']['site1'];

// Credenciais unificadas do Banco de Dados
$dbConfig = [
    'host' => $DB_HOST,
    'username' => $DB_USER,
    'password' => $DB_PASS,
    'database' => $DB_NAME,
];

$config['db'] = [
    'production' => $dbConfig,
    'localhost'  => $dbConfig,
];

Seo::setFavicon('favicon.ico');

// Garantir pastas de upload existentes
if (!file_exists($config['config']['upload']['imagens'])) {
    @mkdir($config['config']['upload']['imagens'], 0777, true);
}
if (!file_exists($config['config']['upload']['arquivos'])) {
    @mkdir($config['config']['upload']['arquivos'], 0777, true);
}

// Inicialização dos serviços com as configurações unificadas
Conn::setConfig($dbConfig);

// Aplica o tema salvo na personalização (admin > Personalizar > Personalização > Temas)
// Caso não exista tema salvo, mantém o layout predefinido em $SITE_LAYOUT
try {
    if ($tema = \app\models\DadosModel::getTemaAtivo()) {
        if (isset($config['modules'][$tema])) {
            $config['modules']['site'] = $config['modules'][$tema];
        }
    }
} catch (\Throwable $e) {
    // Mantém o tema padrão definido em $SITE_LAYOUT
}

APP::setModules($config['modules']);
APP::setConfig($config['config']);

return $config;