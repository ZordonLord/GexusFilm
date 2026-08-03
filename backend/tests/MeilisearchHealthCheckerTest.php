<?php

declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Meilisearch\MeilisearchGateway;
use App\Infrastructure\Meilisearch\MeilisearchHealthChecker;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MeilisearchHealthCheckerTest extends TestCase
{
    public function testUnavailableMeilisearchIsReportedAsUnhealthy(): void
    {
        $gateway = new class implements MeilisearchGateway {
            public function health(): bool
            {
                throw new RuntimeException('connection refused');
            }

            public function ensureIndex(string $uid, string $primaryKey, array $settings): void
            {
            }
        };

        self::assertFalse((new MeilisearchHealthChecker($gateway))->isHealthy());
    }

    public function testAvailableMeilisearchIsReportedAsHealthy(): void
    {
        $gateway = new class implements MeilisearchGateway {
            public function health(): bool
            {
                return true;
            }

            public function ensureIndex(string $uid, string $primaryKey, array $settings): void
            {
            }
        };

        self::assertTrue((new MeilisearchHealthChecker($gateway))->isHealthy());
    }
}
