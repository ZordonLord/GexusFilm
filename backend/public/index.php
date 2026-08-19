<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/routes.php';

use App\Response;

$requestUri = $_SERVER['REQUEST_URI'];
$parsed = parse_url($requestUri);
$path = $parsed['path'] ?? '/';
$query = $parsed['query'] ?? '';

$path = preg_replace('#\.php$#', '', $path) ?? $path;

if ($query !== '') {
    $_SERVER['REQUEST_URI'] = $path . '?' . $query;
}

$router = createRouter();

$isHealthRoute = $path === '/api/health';
$isOptionsRequest = $_SERVER['REQUEST_METHOD'] === 'OPTIONS';
$isApiRequest = str_starts_with($path, '/api/');

if (!$isHealthRoute && !$isOptionsRequest && $isApiRequest) {
    $rateConfig = rate_limit_config();
    $bucket = in_array($path, ['/api/search', '/api/discover'], true) ? 'search' : 'default';
    $result = rate_limiter()->check(
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $bucket,
        $rateConfig[$bucket],
        $rateConfig['window_seconds'],
    );
    $headers = [
        'X-RateLimit-Limit' => (string) $result->limit,
        'X-RateLimit-Remaining' => (string) $result->remaining,
        'X-RateLimit-Reset' => (string) $result->resetAt,
    ];

    if (!$result->allowed) {
        $headers['Retry-After'] = (string) $result->retryAfter;
        Response::json([
            'error' => [
                'code' => 'RATE_LIMITED',
                'message' => 'Слишком много запросов',
            ],
        ], 429, $headers);

        exit;
    }

    foreach ($headers as $name => $value) {
        header($name . ': ' . $value);
    }
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $path);
