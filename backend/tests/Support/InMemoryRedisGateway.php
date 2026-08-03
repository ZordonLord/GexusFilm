<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Infrastructure\Redis\RedisGateway;

final class InMemoryRedisGateway implements RedisGateway
{
    /** @var array<string, string> */
    private array $values = [];

    /** @var array<string, int> */
    private array $counters = [];

    public int $lastTtl = 0;

    public function get(string $key): ?string
    {
        return $this->values[$key] ?? null;
    }

    public function set(string $key, string $value, int $ttlSeconds): void
    {
        $this->values[$key] = $value;
        $this->lastTtl = $ttlSeconds;
    }

    public function incrementFixedWindow(string $key, int $windowSeconds): array
    {
        $count = ($this->counters[$key] ?? 0) + 1;
        $this->counters[$key] = $count;

        return ['count' => $count, 'ttl' => 1];
    }

    public function health(): bool
    {
        return true;
    }
}
