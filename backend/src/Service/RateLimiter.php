<?php

declare(strict_types=1);

namespace App\Service;

use App\Infrastructure\Redis\RedisGateway;
use Throwable;

final class RateLimiter
{
    public function __construct(private RedisGateway $gateway)
    {
    }

    public function check(string $clientId, string $bucket, int $limit, int $windowSeconds): RateLimitResult
    {
        $now = time();
        $resetAt = $now - ($now % $windowSeconds) + $windowSeconds;

        try {
            $state = $this->gateway->incrementFixedWindow(
                CacheKeyFactory::rateLimit($clientId, $bucket, $windowSeconds),
                $windowSeconds,
            );
            $remaining = max(0, $limit - $state['count']);

            return new RateLimitResult(
                $state['count'] <= $limit,
                $limit,
                $remaining,
                $resetAt,
                $state['count'] > $limit ? max(1, $state['ttl']) : null,
            );
        } catch (Throwable $exception) {
            // Лимитер не должен превращать временный отказ Redis в отказ каталога.
            error_log('Redis rate limit failed: ' . $exception->getMessage());

            return new RateLimitResult(true, $limit, $limit, $resetAt);
        }
    }
}
