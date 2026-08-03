<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Infrastructure\Redis\RedisGateway;
use RuntimeException;

final class FailingRedisGateway implements RedisGateway
{
    public function get(string $key): ?string
    {
        throw new RuntimeException('Redis unavailable');
    }

    public function set(string $key, string $value, int $ttlSeconds): void
    {
        throw new RuntimeException('Redis unavailable');
    }

    public function incrementFixedWindow(string $key, int $windowSeconds): array
    {
        throw new RuntimeException('Redis unavailable');
    }

    public function health(): bool
    {
        throw new RuntimeException('Redis unavailable');
    }
}
