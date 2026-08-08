<?php

declare(strict_types=1);

namespace App\Service;

interface RateLimitCheckerInterface
{
    public function check(string $clientId, string $bucket, int $limit, int $windowSeconds): RateLimitResult;
}
