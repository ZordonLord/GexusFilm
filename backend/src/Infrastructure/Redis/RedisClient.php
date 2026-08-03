<?php

declare(strict_types=1);

namespace App\Infrastructure\Redis;

use Predis\Client;

final class RedisClient implements RedisGateway
{
    public function __construct(private Client $client)
    {
    }

    public function get(string $key): ?string
    {
        $value = $this->client->get($key);

        return $value === null ? null : (string) $value;
    }

    public function set(string $key, string $value, int $ttlSeconds): void
    {
        $this->client->setex($key, $ttlSeconds, $value);
    }

    public function incrementFixedWindow(string $key, int $windowSeconds): array
    {
        $result = $this->client->eval(
            'local current = redis.call("INCR", KEYS[1]) '
            . 'if current == 1 then redis.call("EXPIRE", KEYS[1], ARGV[1]) end '
            . 'return {current, redis.call("TTL", KEYS[1])}',
            1,
            $key,
            $windowSeconds,
        );

        return [
            'count' => (int) $result[0],
            'ttl' => max(1, (int) $result[1]),
        ];
    }

    public function health(): bool
    {
        return (string) $this->client->ping() === 'PONG';
    }
}
