<?php

declare(strict_types=1);

namespace App\Infrastructure\Resilience;

use App\Service\CacheKeyFactory;
use App\Service\RateLimitCheckerInterface;
use App\Service\RateLimitResult;
use RuntimeException;

final class FileRateLimiter implements RateLimitCheckerInterface
{
    public function __construct(private string $directory)
    {
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Rate limit directory cannot be created.');
        }
    }

    public function check(string $clientId, string $bucket, int $limit, int $windowSeconds): RateLimitResult
    {
        $now = time();
        $resetAt = $now - ($now % $windowSeconds) + $windowSeconds;
        $key = CacheKeyFactory::rateLimit($clientId, $bucket, $windowSeconds);
        $path = $this->directory . '/rate-' . hash('sha256', $key) . '.json';
        $handle = fopen($path, 'c+');

        if ($handle === false) {
            throw new RuntimeException('Rate limit state cannot be opened.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Rate limit state cannot be locked.');
            }

            $state = json_decode(stream_get_contents($handle) ?: '{}', true);
            if (!is_array($state) || (int) ($state['reset_at'] ?? 0) <= $now) {
                $state = ['count' => 0, 'reset_at' => $resetAt];
            }

            $state['count'] = (int) ($state['count'] ?? 0) + 1;
            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($state, JSON_THROW_ON_ERROR));
            fflush($handle);
            flock($handle, LOCK_UN);

            $count = (int) $state['count'];

            return new RateLimitResult(
                $count <= $limit,
                $limit,
                max(0, $limit - $count),
                (int) $state['reset_at'],
                $count > $limit ? max(1, (int) $state['reset_at'] - $now) : null,
            );
        } finally {
            fclose($handle);
        }
    }
}
