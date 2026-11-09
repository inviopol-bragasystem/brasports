<?php

namespace App\Http;

class Response
{
    public static function json(mixed $data, int $status = 200, array $meta = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');

        $payload = [
            'success' => $status >= 200 && $status < 300,
            'status' => $status,
            'data' => $data,
        ];

        if (!empty($meta)) {
            $payload['meta'] = $meta;
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        exit;
    }

    public static function error(string $message, int $status = 400, array $extra = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        $payload = array_merge([
            'success' => false,
            'status' => $status,
            'error' => $message,
        ], $extra);

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function applyCors(array $cors): void
    {
        if (empty($cors['enabled'])) {
            return;
        }

        header('Access-Control-Allow-Origin: ' . ($cors['allow_origin'] ?? '*'));
        header('Access-Control-Allow-Methods: ' . ($cors['allow_methods'] ?? 'GET, OPTIONS'));
        header('Access-Control-Allow-Headers: ' . ($cors['allow_headers'] ?? 'Content-Type'));

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
