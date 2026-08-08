<?php

declare(strict_types=1);

namespace App\Infrastructure\Resilience;

use App\Exception\ServiceUnavailableException;
use RuntimeException;

final class FileRequestLimiter
{
    public function __construct(
        private string $directory,
        private int $requestsPerSecond,
        private int $maxConcurrent,
        private int $queueTimeoutMs,
    ) {
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('TMDB coordination directory cannot be created.');
        }
    }

    /** @return callable(): void */
    public function acquire(): callable
    {
        $path = $this->directory . '/request-limiter.json';
        $deadline = microtime(true) + ($this->queueTimeoutMs / 1000);

        while (microtime(true) < $deadline) {
            $handle = fopen($path, 'c+');
            if ($handle === false) {
                throw new ServiceUnavailableException('TMDB request limiter is unavailable.');
            }

            if (!flock($handle, LOCK_EX)) {
                fclose($handle);
                usleep(10000);
                continue;
            }

            $now = microtime(true);
            $state = json_decode(stream_get_contents($handle) ?: '{}', true);
            $state = is_array($state) ? $state : [];
            $windowStarted = (float) ($state['window_started'] ?? $now);
            $requests = (int) ($state['requests'] ?? 0);
            $concurrent = (int) ($state['concurrent'] ?? 0);

            if ($now - $windowStarted >= 1) {
                $windowStarted = $now;
                $requests = 0;
            }

            if ($requests < $this->requestsPerSecond && $concurrent < $this->maxConcurrent) {
                $requests++;
                $concurrent++;
                $this->writeState($handle, $windowStarted, $requests, $concurrent);
                flock($handle, LOCK_UN);
                fclose($handle);

                return function () use ($path): void {
                    $this->release($path);
                };
            }

            flock($handle, LOCK_UN);
            fclose($handle);
            usleep(10000);
        }

        throw new ServiceUnavailableException('TMDB request limit is temporarily exhausted.', 1);
    }

    private function release(string $path): void
    {
        $handle = fopen($path, 'c+');
        if ($handle === false || !flock($handle, LOCK_EX)) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            return;
        }

        $state = json_decode(stream_get_contents($handle) ?: '{}', true);
        $state = is_array($state) ? $state : [];
        $this->writeState(
            $handle,
            (float) ($state['window_started'] ?? microtime(true)),
            (int) ($state['requests'] ?? 0),
            max(0, (int) ($state['concurrent'] ?? 0) - 1),
        );
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    private function writeState($handle, float $windowStarted, int $requests, int $concurrent): void
    {
        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, json_encode([
            'window_started' => $windowStarted,
            'requests' => $requests,
            'concurrent' => $concurrent,
        ], JSON_THROW_ON_ERROR));
        fflush($handle);
    }
}
