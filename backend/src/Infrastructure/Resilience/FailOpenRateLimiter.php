<?php

declare(strict_types=1);

namespace App\Infrastructure\Resilience;

use App\Service\RateLimitCheckerInterface;
use App\Service\RateLimitResult;

final class FailOpenRateLimiter implements RateLimitCheckerInterface
{
    public function check(string $clientId, string $bucket, int $limit, int $windowSeconds): RateLimitResult
    {
        $now = time();
        $resetAt = $now - ($now % $windowSeconds) + $windowSeconds;

        // Это только последний аварийный путь: блокировать каталог из-за
        // недоступности временной защиты хуже, чем временно пропустить API.
        return new RateLimitResult(true, $limit, $limit, $resetAt);
    }
}
