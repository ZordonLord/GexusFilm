<?php

declare(strict_types=1);

namespace App\Service;

use App\Infrastructure\Redis\RedisGateway;
use Throwable;

final class CacheService
{
    public function __construct(private RedisGateway $gateway)
    {
    }

    public function get(string $key): ?array
    {
        try {
            $value = $this->gateway->get($key);

            if ($value === null) {
                return null;
            }

            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable $exception) {
            error_log('Redis cache read failed: ' . $exception->getMessage());

            return null;
        }
    }

    public function put(string $key, array $value, int $ttlSeconds): void
    {
        try {
            $this->gateway->set($key, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $ttlSeconds);
        } catch (Throwable $exception) {
            error_log('Redis cache write failed: ' . $exception->getMessage());
        }
    }
}
