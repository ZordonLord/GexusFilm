<?php

declare(strict_types=1);

namespace App\Infrastructure\Resilience;

use App\Exception\ServiceUnavailableException;
use RuntimeException;

final class CircuitBreaker
{
    public function __construct(
        private string $directory,
        private int $failureThreshold,
        private int $cooldownSeconds,
    ) {
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('TMDB coordination directory cannot be created.');
        }
    }

    public function allow(): void
    {
        $this->withState(function (array &$state): void {
            $now = time();
            $openUntil = (int) ($state['open_until'] ?? 0);

            if ($openUntil > $now) {
                throw new ServiceUnavailableException(
                    'TMDB circuit is open.',
                    max(1, $openUntil - $now),
                );
            }

            if ($openUntil > 0 && !empty($state['probe_in_progress'])) {
                throw new ServiceUnavailableException('TMDB circuit probe is in progress.', 1);
            }

            if ($openUntil > 0) {
                $state['probe_in_progress'] = true;
            }
        });
    }

    public function recordSuccess(): void
    {
        $this->withState(static function (array &$state): void {
            $state = ['failures' => 0, 'open_until' => 0, 'probe_in_progress' => false];
        });
    }

    public function recordFailure(): void
    {
        $this->withState(function (array &$state): void {
            $failures = (int) ($state['failures'] ?? 0) + 1;
            $state['failures'] = $failures;
            $state['probe_in_progress'] = false;

            if ($failures >= $this->failureThreshold) {
                $state['open_until'] = time() + $this->cooldownSeconds;
            }
        });
    }

    private function withState(callable $callback): void
    {
        $handle = fopen($this->directory . '/circuit.json', 'c+');
        if ($handle === false || !flock($handle, LOCK_EX)) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            throw new ServiceUnavailableException('TMDB circuit state is unavailable.');
        }

        try {
            $state = json_decode(stream_get_contents($handle) ?: '{}', true);
            $state = is_array($state) ? $state : [];
            $callback($state);
            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($state, JSON_THROW_ON_ERROR));
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }
}
