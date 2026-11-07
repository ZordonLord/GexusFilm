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

$requestBody = [];

if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'], true)) {
    $rawBody = file_get_contents('php://input', false, null, 0, 16385);

    if ($rawBody === false || strlen($rawBody) > 16384) {
        Response::json([
            'error' => [
                'code' => 'PAYLOAD_TOO_LARGE',
                'message' => 'Размер запроса превышает допустимый',
            ],
        ], 413);
        exit;
    }

    if (trim($rawBody) !== '') {
        try {
            $decodedBody = json_decode($rawBody, false, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            Response::json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Тело запроса должно содержать корректный JSON-объект',
                ],
            ], 400);
            exit;
        }

        if (!$decodedBody instanceof stdClass) {
            Response::json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Тело запроса должно содержать JSON-объект',
                ],
            ], 400);
            exit;
        }

        $requestBody = (array) $decodedBody;
    }
}

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

    if ($path === '/api/auth/register' || $path === '/api/auth/login') {
        $authAction = $path === '/api/auth/register' ? 'register' : 'login';
        $authLimits = auth_rate_limit_config();
        try {
            $authResult = auth_rate_limiter()->check(
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'auth-' . $authAction,
                $authLimits[$authAction],
                $authLimits['window_seconds'],
            );
        } catch (Throwable $exception) {
            error_log('Authentication rate limiter unavailable: ' . $exception::class);
            Response::json([
                'error' => [
                    'code' => 'SERVICE_UNAVAILABLE',
                    'message' => 'Сервис временно недоступен',
                ],
            ], 503, ['Retry-After' => '30']);
            exit;
        }

        if (!$authResult->allowed) {
            Response::json([
                'error' => [
                    'code' => 'RATE_LIMITED',
                    'message' => 'Слишком много попыток. Повторите позже',
                ],
            ], 429, ['Retry-After' => (string) $authResult->retryAfter]);
            exit;
        }
    }

    foreach ($headers as $name => $value) {
        header($name . ': ' . $value);
    }
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $path, $requestBody);
