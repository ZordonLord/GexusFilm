<?php

declare(strict_types=1);

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

function tmdb_ca_bundle(): ?string
{
    $configuredPath = env_value('TMDB_CAINFO');

    if ($configuredPath !== '' && is_file($configuredPath)) {
        return $configuredPath;
    }

    foreach (['CURL_CA_BUNDLE', 'SSL_CERT_FILE'] as $environmentKey) {
        $environmentPath = env_value($environmentKey);

        if ($environmentPath !== '' && is_file($environmentPath)) {
            return $environmentPath;
        }
    }

    // В Windows-сборке PHP CA bundle часто не прописан в php.ini, но Git for
    // Windows уже поставляет актуальный публичный набор корневых сертификатов.
    $windowsGitBundle = 'C:\\Program Files\\Git\\usr\\ssl\\certs\\ca-bundle.crt';

    return is_file($windowsGitBundle) ? $windowsGitBundle : null;
}

function db_config(): array
{
    return [
        'host' => env_value('DB_HOST', '127.0.0.1'),
        'port' => env_value('DB_PORT', '5432'),
        'database' => env_value('DB_DATABASE', 'gexusfilm'),
        'user' => env_value('DB_USERNAME', 'gexusfilm'),
        'password' => env_value('DB_PASSWORD', 'gexusfilm-local-password'),
    ];
}

function cache_ttl_minutes(): int
{
    return max(1, (int) env_value('CACHE_TTL_MINUTES', '1440'));
}

function rate_limit_config(): array
{
    return [
        'default' => max(1, (int) env_value('RATE_LIMIT_DEFAULT', '120')),
        'search' => max(1, (int) env_value('RATE_LIMIT_SEARCH', '60')),
        'window_seconds' => max(1, (int) env_value('RATE_LIMIT_WINDOW_SECONDS', '60')),
    ];
}

function tmdb_protection_config(): array
{
    return [
        'requests_per_second' => max(1, (int) env_value('TMDB_REQUESTS_PER_SECOND', '10')),
        'max_concurrent' => max(1, (int) env_value('TMDB_MAX_CONCURRENT', '10')),
        'queue_timeout_ms' => max(1, (int) env_value('TMDB_QUEUE_TIMEOUT_MS', '250')),
        'singleflight_timeout_ms' => max(1, (int) env_value('TMDB_SINGLEFLIGHT_TIMEOUT_MS', '2000')),
        'singleflight_result_ttl' => max(1, (int) env_value('TMDB_SINGLEFLIGHT_RESULT_TTL_SECONDS', '15')),
        'circuit_failure_threshold' => max(1, (int) env_value('TMDB_CIRCUIT_FAILURE_THRESHOLD', '5')),
        'circuit_cooldown_seconds' => max(1, (int) env_value('TMDB_CIRCUIT_COOLDOWN_SECONDS', '30')),
        'coordination_directory' => env_value(
            'TMDB_COORDINATION_DIR',
            sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gexusfilm-tmdb',
        ),
    ];
}
