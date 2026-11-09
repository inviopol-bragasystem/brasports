<?php

namespace App\Http;

/**
 * Cliente HTTP para a Betano.
 * Usa cURL nativo e, em caso de bloqueio Cloudflare (403),
 * faz fallback para o binário curl.exe/curl do sistema (melhor fingerprint TLS).
 */
class Client
{
    private string $baseUrl;
    private int $timeout;
    private string $userAgent;
    private array $defaultHeaders;
    private string $cookieFile;
    private ?string $curlBinary;
    private bool $preferBinary;

    public function __construct(array $config)
    {
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->timeout = (int) ($config['timeout'] ?? 25);
        $this->userAgent = $config['user_agent'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';
        $this->defaultHeaders = $config['headers'] ?? [];
        $this->preferBinary = (bool) ($config['prefer_binary_curl'] ?? true);

        $cookieDir = $config['cookie_dir'] ?? (dirname(__DIR__, 2) . '/cache');
        if (!is_dir($cookieDir)) {
            @mkdir($cookieDir, 0775, true);
        }
        $this->cookieFile = $cookieDir . DIRECTORY_SEPARATOR . 'betano_cookies.txt';
        $this->curlBinary = $this->detectCurlBinary($config['curl_binary'] ?? null);
    }

    public function get(string $path, array $query = [], array $extraHeaders = []): array
    {
        $url = $this->buildUrl($path, $query);
        return $this->request('GET', $url, $extraHeaders);
    }

    public function getAbsolute(string $url, array $extraHeaders = []): array
    {
        return $this->request('GET', $url, $extraHeaders);
    }

    private function buildUrl(string $path, array $query = []): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $url = $path;
        } else {
            $url = $this->baseUrl . '/' . ltrim($path, '/');
        }

        if (!empty($query)) {
            $sep = str_contains($url, '?') ? '&' : '?';
            $url .= $sep . http_build_query($query);
        }

        return $url;
    }

    private function request(string $method, string $url, array $extraHeaders = []): array
    {
        $headers = array_merge($this->defaultHeaders, $extraHeaders);

        // 1) Prefer binary curl (Windows/Cloudflare-friendly)
        if ($this->preferBinary && $this->curlBinary) {
            try {
                return $this->requestBinary($method, $url, $headers);
            } catch (\Throwable $e) {
                // tenta nativo se binário falhar de forma inesperada
                if (!str_contains($e->getMessage(), 'HTTP 403') && !str_contains($e->getMessage(), 'HTTP 503')) {
                    // continua para nativo
                } else {
                    // ainda tenta nativo uma vez
                }
            }
        }

        // 2) Extensão cURL do PHP
        try {
            return $this->requestNative($method, $url, $headers);
        } catch (\RuntimeException $e) {
            // 3) Fallback binário se nativo tomar 403
            if ($this->curlBinary && (str_contains($e->getMessage(), 'HTTP 403') || str_contains($e->getMessage(), 'HTTP 503'))) {
                return $this->requestBinary($method, $url, $headers);
            }
            throw $e;
        }
    }

    private function requestNative(string $method, string $url, array $headers): array
    {
        $ch = curl_init();

        $headerLines = [];
        foreach ($headers as $k => $v) {
            $headerLines[] = $k . ': ' . $v;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_ENCODING => '', // aceita gzip/deflate
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_HEADER => false,
        ]);

        if (strtoupper($method) !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        }

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($errno) {
            throw new \RuntimeException("Falha HTTP cURL ({$errno}): {$error}", 502);
        }

        return $this->parseResponse($url, $status, $contentType, $body === false ? '' : $body);
    }

    private function requestBinary(string $method, string $url, array $headers): array
    {
        if (!$this->curlBinary) {
            throw new \RuntimeException('Binário curl não disponível', 502);
        }

        $tmpDir = dirname($this->cookieFile);
        $bodyFile = $tmpDir . DIRECTORY_SEPARATOR . 'curl_body_' . bin2hex(random_bytes(8)) . '.tmp';
        $metaFile = $tmpDir . DIRECTORY_SEPARATOR . 'curl_meta_' . bin2hex(random_bytes(8)) . '.tmp';

        // No Windows, % é expandido pelo cmd.exe — usar arquivos evita -w com %{...}
        $args = [
            $this->curlBinary,
            '-sS',
            '-L',
            '--max-time', (string) $this->timeout,
            '-X', strtoupper($method),
            '-A', $this->userAgent,
            '-o', $bodyFile,
            '-D', $metaFile,
            '-c', $this->cookieFile,
            '-b', $this->cookieFile,
            '--compressed',
        ];

        foreach ($headers as $k => $v) {
            $args[] = '-H';
            $args[] = $k . ': ' . $v;
        }

        $args[] = $url;

        try {
            $cmd = $this->buildCommand($args) . ' 2>&1';
            $exit = 0;
            $stderr = $this->runShell($cmd, $exit);

            $body = is_file($bodyFile) ? (string) file_get_contents($bodyFile) : '';
            $meta = is_file($metaFile) ? (string) file_get_contents($metaFile) : '';

            if ($exit !== 0 && $body === '') {
                throw new \RuntimeException(
                    'curl binário falhou (exit ' . $exit . '): ' . trim($stderr),
                    502
                );
            }

            $status = $this->parseStatusFromHeaders($meta);
            $contentType = $this->parseHeaderValue($meta, 'content-type');

            // Se não achar status nos headers, assume 200 se body JSON
            if ($status === 0) {
                $status = (str_starts_with(ltrim($body), '{') || str_starts_with(ltrim($body), '[')) ? 200 : 502;
            }

            return $this->parseResponse($url, $status, $contentType, $body);
        } finally {
            if (is_file($bodyFile)) {
                @unlink($bodyFile);
            }
            if (is_file($metaFile)) {
                @unlink($metaFile);
            }
        }
    }

    private function parseStatusFromHeaders(string $rawHeaders): int
    {
        // Pode haver múltiplos blocos HTTP/1.1 por redirect — pega o último
        if (preg_match_all('/^HTTP\/[\d.]+\s+(\d+)/mi', $rawHeaders, $m)) {
            return (int) end($m[1]);
        }
        return 0;
    }

    private function parseHeaderValue(string $rawHeaders, string $name): string
    {
        $pattern = '/^' . preg_quote($name, '/') . ':\s*(.+)$/mi';
        if (preg_match_all($pattern, $rawHeaders, $m)) {
            return trim(end($m[1]));
        }
        return '';
    }

    private function parseResponse(string $url, int $status, string $contentType, string $body): array
    {
        if ($status >= 400) {
            $code = match (true) {
                $status === 403, $status === 429, $status === 503 => 502,
                $status === 404 => 404,
                default => ($status >= 500 ? 502 : 400),
            };
            throw new \RuntimeException("Betano respondeu HTTP {$status} em {$url}", $code);
        }

        $decoded = null;
        if ($body !== '') {
            $decoded = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                if (str_contains($body, '<!DOCTYPE') || str_contains($body, '<html')) {
                    throw new \RuntimeException("Endpoint Betano não encontrado ou bloqueado (HTML): {$url}", 404);
                }
                throw new \RuntimeException('JSON inválido da Betano: ' . json_last_error_msg(), 502);
            }
        }

        return [
            'status' => $status,
            'content_type' => $contentType,
            'url' => $url,
            'data' => $decoded,
            'raw' => $body,
        ];
    }

    private function detectCurlBinary(?string $configured): ?string
    {
        if ($configured && @is_file($configured)) {
            return $configured;
        }

        $candidates = [];
        if (PHP_OS_FAMILY === 'Windows') {
            $candidates[] = 'curl.exe';
            $candidates[] = 'C:\\Windows\\System32\\curl.exe';
        }
        $candidates[] = 'curl';

        foreach ($candidates as $bin) {
            if (str_contains($bin, DIRECTORY_SEPARATOR) || str_contains($bin, '/')) {
                if (@is_file($bin)) {
                    return $bin;
                }
                continue;
            }

            $exit = 0;
            $out = $this->runShell(escapeshellarg($bin) . ' --version 2>&1', $exit);
            if ($exit === 0 && str_contains(strtolower($out), 'curl')) {
                return $bin;
            }
        }

        return null;
    }

    /**
     * Executa comando de shell capturando saída.
     * Preferência: system() (exec/shell_exec/proc_open costumam estar desabilitados no XAMPP).
     */
    private function runShell(string $cmd, int &$exitCode = 0): string
    {
        if (function_exists('exec') && !in_array('exec', $this->disabledFunctions(), true)) {
            $lines = [];
            @exec($cmd, $lines, $exitCode);
            return implode("\n", $lines);
        }

        if (function_exists('shell_exec') && !in_array('shell_exec', $this->disabledFunctions(), true)) {
            $out = @shell_exec($cmd);
            $exitCode = $out === null ? 1 : 0;
            return (string) $out;
        }

        if (function_exists('system') && !in_array('system', $this->disabledFunctions(), true)) {
            ob_start();
            $last = @system($cmd, $exitCode);
            $buf = (string) ob_get_clean();
            if ($buf === '' && is_string($last)) {
                return $last;
            }
            return $buf;
        }

        throw new \RuntimeException(
            'Nenhuma função shell disponível (exec/shell_exec/system desabilitadas no php.ini). Habilite system ou exec, ou use um ambiente sem disable_functions.',
            500
        );
    }

    /** @return list<string> */
    private function disabledFunctions(): array
    {
        static $list = null;
        if ($list === null) {
            $raw = (string) ini_get('disable_functions');
            $list = array_values(array_filter(array_map('trim', explode(',', $raw))));
        }
        return $list;
    }

    /**
     * Monta comando shell escapando argumentos de forma segura.
     */
    private function buildCommand(array $args): string
    {
        $parts = [];
        foreach ($args as $arg) {
            $parts[] = escapeshellarg((string) $arg);
        }
        return implode(' ', $parts);
    }
}

