<?php

declare(strict_types=1);

namespace App;

final class Response
{
    public static function json(array $data): void
    {
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
