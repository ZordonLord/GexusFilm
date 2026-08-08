<?php

declare(strict_types=1);

namespace App\Infrastructure\Resilience;

use App\Exception\ServiceUnavailableException;
use Closure;
use RuntimeException;

final class FileSingleflight
{
    public function __construct(
        private string $directory,
        private int $timeoutMs,
        private int $resultTtlSeconds,
    ) {
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('TMDB coordination directory cannot be created.');
        }
    }

    /** @return array<string, mixed> */
    public function run(string $key, Closure $operation): array
    {
        $hash = hash('sha256', $key);
        $lockPath = $this->directory . '/singleflight-' . $hash . '.lock';
        $resultPath = $this->directory . '/singleflight-' . $hash . '.json';
        $deadline = microtime(true) + ($this->timeoutMs / 1000);

        while (microtime(true) < $deadline) {
            $cached = $this->readResult($resultPath);
            if ($cached !== null) {
                return $cached;
            }

            $handle = fopen($lockPath, 'c+');
            if ($handle === false) {
                throw new ServiceUnavailableException('TMDB request coordination is unavailable.');
            }

            if (flock($handle, LOCK_EX | LOCK_NB)) {
                try {
                    $cached = $this->readResult($resultPath);
                    if ($cached !== null) {
                        return $cached;
                    }

                    $data = $operation();
                    $this->writeResult($resultPath, $data);

                    return $data;
                } finally {
                    flock($handle, LOCK_UN);
                    fclose($handle);
                }
            }

            fclose($handle);
            usleep(10000);
        }

        throw new ServiceUnavailableException('TMDB request is already in progress.', 1);
    }

    /** @return array<string, mixed>|null */
    private function readResult(string $path): ?array
    {
        if (!is_file($path) || filemtime($path) + $this->resultTtlSeconds < time()) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $result = json_decode($content, true);

        return is_array($result) ? $result : null;
    }

    /** @param array<string, mixed> $data */
    private function writeResult(string $path, array $data): void
    {
        $temporaryPath = $path . '.' . getmypid() . '.tmp';
        file_put_contents($temporaryPath, json_encode($data, JSON_THROW_ON_ERROR), LOCK_EX);
        rename($temporaryPath, $path);
    }
}
