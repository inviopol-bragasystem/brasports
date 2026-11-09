<?php
/**
 * ==============================================================================
 * CONEXÃO UNIFICADA COM O BANCO DE DADOS
 * ==============================================================================
 * Este arquivo agora utiliza automaticamente as credenciais configuradas em
 * 'inc.config.php'. Você NÃO precisa mais editar as senhas ou hosts aqui!
 * Todas as alterações devem ser feitas exclusivamente no topo de inc.config.php.
 * ==============================================================================
 */

ini_set('memory_limit', '-1');

// Carrega as configurações principais do sistema
require_once __DIR__ . '/inc.config.php';

try {
    /** @var \PDO $conexao Instância PDO do sistema */
    $conexao = \app\core\crud\Conn::getConn();
} catch (\Throwable $e) {
    error_log('Database Connection Error in conexao.php: ' . $e->getMessage());
    die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
}
