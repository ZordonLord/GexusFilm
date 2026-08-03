<?php

declare(strict_types=1);

namespace App\Service;

final class RateLimitResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly int $limit,
        public readonly int $remaining,
        public readonly int $resetAt,
        public readonly ?int $retryAfter = null,
    ) {
    }
}
