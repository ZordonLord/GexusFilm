<?php

declare(strict_types=1);

namespace App;

final class Response
{
    public static function json(array $data, ?int $status = null): void
    {
        if ($status !== null) {
            http_response_code($status);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
