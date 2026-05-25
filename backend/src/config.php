<?php

function load_env_file(): void
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $loaded = true;
    $path = __DIR__ . '/../.env';

    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");

        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

function env_value(string $key, string $default = ''): string
{
    load_env_file();

    $value = getenv($key);

    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

function tmdb_api_key(): string
{
    $apiKey = env_value('TMDB_API_KEY');

    if ($apiKey === '') {
        throw new RuntimeException('TMDB_API_KEY is not set. Copy backend/.env.example to backend/.env and add your TMDB API key.');
    }

    return $apiKey;
}

function db_config(): array
{
    return [
        'host' => env_value('DB_HOST', '127.0.0.1'),
        'port' => env_value('DB_PORT', '5432'),
        'database' => env_value('DB_DATABASE', 'zordonfilm'),
        'user' => env_value('DB_USERNAME', 'zordonfilm'),
        'password' => env_value('DB_PASSWORD', 'zordonfilm'),
    ];
}

function cache_ttl_minutes(): int
{
    return max(1, (int) env_value('CACHE_TTL_MINUTES', '1440'));
}
