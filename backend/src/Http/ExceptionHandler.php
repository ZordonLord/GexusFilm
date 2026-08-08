<?php

declare(strict_types=1);

namespace App\Http;

use App\Exception\ServiceUnavailableException;
use App\Exception\UpstreamException;
use App\Exception\UpstreamTimeoutException;
use Throwable;
use InvalidArgumentException;

class ExceptionHandler
{
    public static function handle(Throwable $exception): void
    {
        error_log('Exception caught: ' . $exception::class . ' in ' . $exception->getFile() . ':' . $exception->getLine());

        if ($exception instanceof InvalidArgumentException) {
            http_response_code(400);
            self::jsonResponse([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $exception->getMessage(),
                ],
            ]);
            return;
        }

        if ($exception instanceof UpstreamTimeoutException) {
            http_response_code(504);
            self::jsonResponse([
                'error' => [
                    'code' => 'UPSTREAM_TIMEOUT',
                    'message' => 'Внешний сервис не ответил вовремя',
                ],
            ]);

            return;
        }

        if ($exception instanceof UpstreamException) {
            http_response_code(502);
            self::jsonResponse([
                'error' => [
                    'code' => 'UPSTREAM_ERROR',
                    'message' => 'Внешний сервис временно недоступен',
                ],
            ]);

            return;
        }

        if ($exception instanceof ServiceUnavailableException) {
            http_response_code(503);
            header('Retry-After: ' . $exception->retryAfter);
            self::jsonResponse([
                'error' => [
                    'code' => 'SERVICE_UNAVAILABLE',
                    'message' => 'Сервис временно перегружен, повторите запрос позже',
                ],
            ]);

            return;
        }

        http_response_code(500);
        self::jsonResponse([
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => 'Внутренняя ошибка сервера',
            ],
        ]);
    }

    private static function jsonResponse(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
