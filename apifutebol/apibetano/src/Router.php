<?php

namespace App;

class Router
{
    /** @var array<int, array{method:string,pattern:string,handler:callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method' => 'GET',
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        // Remove subpasta caso chamado diretamente no Apache sob htdocs
        $path = preg_replace('#^/apifutebol/apibetano(/public)?#', '', $path) ?: '/';

        // Remove prefixo /public se o servidor apontar na raiz do projeto
        if (str_starts_with($path, '/public')) {
            $path = substr($path, 7) ?: '/';
        }

        // Suporte a index.php/api/...
        if (str_contains($path, 'index.php')) {
            $path = preg_replace('#.*?index\.php#', '', $path) ?: '/';
            $path = '/' . trim($path, '/');
            if ($path !== '/') {
                $path = rtrim($path, '/');
            }
        }

        foreach ($this->routes as $route) {
            if (strtoupper($method) !== $route['method']) {
                continue;
            }

            $regex = $this->toRegex($route['pattern']);
            if (preg_match($regex, $path, $matches)) {
                $params = array_filter(
                    $matches,
                    static fn ($k) => !is_int($k),
                    ARRAY_FILTER_USE_KEY
                );
                ($route['handler'])($params);
                return;
            }
        }

        \App\Http\Response::error('Rota não encontrada: ' . $path, 404, [
            'hint' => 'Veja GET /api para a lista de endpoints',
        ]);
    }

    private function toRegex(string $pattern): string
    {
        $pattern = '/' . trim($pattern, '/');
        if ($pattern !== '/') {
            $pattern = rtrim($pattern, '/');
        }

        // wildcards com barras primeiro: {path*}
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\*\}#', '(?P<$1>.+)', $pattern);
        // params simples: {id}
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $regex ?? $pattern);

        return '#^' . ($regex ?? $pattern) . '$#u';
    }
}

