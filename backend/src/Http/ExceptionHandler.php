<?php

declare(strict_types=1);

namespace App\Http;

use Throwable;
use InvalidArgumentException;

class ExceptionHandler
{
    public static function handle(Throwable $exception): void
    {
        error_log('Exception caught: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
        
        if ($exception instanceof InvalidArgumentException) {
            http_response_code(400);
            self::jsonResponse([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $exception->getMessage(),
                ],
            ]);
        } else {
            http_response_code(500);
            self::jsonResponse([
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Internal server error: ' . $exception->getMessage(),
                ],
            ]);
        }
    }

    private static function jsonResponse(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}