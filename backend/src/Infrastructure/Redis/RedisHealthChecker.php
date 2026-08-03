<?php

declare(strict_types=1);

namespace App\Infrastructure\Redis;

use Throwable;

final class RedisHealthChecker
{
    public function __construct(private RedisGateway $gateway)
    {
    }

    public function isHealthy(): bool
    {
        try {
            return $this->gateway->health();
        } catch (Throwable) {
            return false;
        }
    }
}
