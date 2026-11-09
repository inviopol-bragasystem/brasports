<?php

/**
 * Router para o servidor embutido do PHP:
 *   php -S localhost:8080 router.php
 *
 * Serve arquivos estáticos de public/ e encaminha o resto para o front controller.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$file = __DIR__ . '/public' . $uri;

if ($uri !== '/' && is_file($file)) {
    return false; // serve o arquivo estático
}

require __DIR__ . '/public/index.php';
