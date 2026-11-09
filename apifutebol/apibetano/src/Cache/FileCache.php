<?php

namespace App\Cache;

class FileCache
{
    private string $path;
    private bool $enabled;

    public function __construct(array $config)
    {
        $this->enabled = (bool) ($config['enabled'] ?? true);
        $this->path = rtrim($config['path'] ?? sys_get_temp_dir(), '/\\');

        if ($this->enabled && !is_dir($this->path)) {
            mkdir($this->path, 0775, true);
        }
    }

    public function get(string $key): mixed
    {
        if (!$this->enabled) {
            return null;
        }

        $file = $this->filePath($key);
        if (!is_file($file)) {
            return null;
        }

        $raw = @file_get_contents($file);
        if ($raw === false) {
            return null;
        }

        $payload = @unserialize($raw);
        if (!is_array($payload) || !isset($payload['expires'], $payload['value'])) {
            @unlink($file);
            return null;
        }

        if ($payload['expires'] > 0 && $payload['expires'] < time()) {
            @unlink($file);
            return null;
        }

        return $payload['value'];
    }

    public function set(string $key, mixed $value, int $ttl = 60): void
    {
        if (!$this->enabled) {
            return;
        }

        $file = $this->filePath($key);
        $payload = serialize([
            'expires' => $ttl > 0 ? time() + $ttl : 0,
            'value' => $value,
            'created' => time(),
        ]);

        @file_put_contents($file, $payload, LOCK_EX);
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $cached = $this->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function forget(string $key): void
    {
        $file = $this->filePath($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    public function clear(): int
    {
        $count = 0;
        foreach (glob($this->path . DIRECTORY_SEPARATOR . 'cache_*.bin') ?: [] as $file) {
            if (@unlink($file)) {
                $count++;
            }
        }
        return $count;
    }

    private function filePath(string $key): string
    {
        return $this->path . DIRECTORY_SEPARATOR . 'cache_' . sha1($key) . '.bin';
    }
}
