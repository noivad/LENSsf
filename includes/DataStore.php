<?php

declare(strict_types=1);

class DataStore
{
    private static ?DataStore $instance = null;

    private string $directory;
    private array $cache = [];

    private function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/');

        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }
    }

    public static function getInstance(string $directory): DataStore
    {
        if (self::$instance === null) {
            self::$instance = new self($directory);
        }

        return self::$instance;
    }

    public function load(string $key): array
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $file = $this->generateFilePath($key);

        if (!file_exists($file)) {
            $this->cache[$key] = [];

            return [];
        }

        $content = file_get_contents($file);
        if ($content === false) {
            throw new RuntimeException('Unable to read from data store.');
        }

        $decoded = json_decode($content, true) ?? [];

        return $this->cache[$key] = $decoded;
    }

    public function save(string $key, array $data): void
    {
        $file = $this->generateFilePath($key);
        $encoded = json_encode($data, JSON_PRETTY_PRINT);

        if ($encoded === false) {
            throw new RuntimeException('Unable to encode data for storage.');
        }

        if (file_put_contents($file, $encoded, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write to data store.');
        }

        $this->cache[$key] = $data;
    }

    private function generateFilePath(string $key): string
    {
        $sanitized = preg_replace('/[^a-z0-9_-]/i', '_', strtolower($key));

        return $this->directory . '/' . $sanitized . '.json';
    }
}
