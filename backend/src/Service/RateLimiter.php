<?php

declare(strict_types=1);

namespace App\Service;

use App\Infrastructure\Redis\RedisGateway;
use Throwable;

final class RateLimiter implements RateLimitCheckerInterface
{
    public function __construct(
        private RedisGateway $gateway,
        private ?RateLimitCheckerInterface $fallback = null,
    ) {
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
            // Локальный fallback сохраняет защиту API, пока распределённый Redis недоступен.
            error_log('Redis rate limit failed: ' . $exception->getMessage());

            if ($this->fallback !== null) {
                return $this->fallback->check($clientId, $bucket, $limit, $windowSeconds);
            }

            return new RateLimitResult(true, $limit, $limit, $resetAt);
        }
    }
}
