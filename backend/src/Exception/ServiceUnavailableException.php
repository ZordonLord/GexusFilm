<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class ServiceUnavailableException extends RuntimeException
{
    public function __construct(string $message = 'Service unavailable', public readonly int $retryAfter = 1)
    {
        parent::__construct($message);
    }
}
