<?php

declare(strict_types=1);

namespace App\Infrastructure\Redis;

interface RedisGateway
{
    public function get(string $key): ?string;

    public function set(string $key, string $value, int $ttlSeconds): void;

    /** @return array{count: int, ttl: int} */
    public function incrementFixedWindow(string $key, int $windowSeconds): array;

    public function health(): bool;
}
