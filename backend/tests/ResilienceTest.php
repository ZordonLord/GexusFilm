<?php

declare(strict_types=1);

namespace App\Tests;

use App\Exception\ServiceUnavailableException;
use App\Exception\UpstreamException;
use App\Exception\UpstreamTimeoutException;
use App\Http\ExceptionHandler;
use App\Infrastructure\Resilience\CircuitBreaker;
use App\Infrastructure\Resilience\FileRequestLimiter;
use App\Infrastructure\Resilience\FileSingleflight;
use App\Infrastructure\Resilience\ProtectedContentSource;
use App\Tests\Support\StubContentSource;
use PHPUnit\Framework\TestCase;

final class ResilienceTest extends TestCase
{
    public function testUpstreamTimeoutIsMappedToSafeGatewayTimeout(): void
    {
        ob_start();
        ExceptionHandler::handle(new UpstreamTimeoutException('private transport details'));
        $body = ob_get_clean();

        self::assertSame(504, http_response_code());
        self::assertStringContainsString('UPSTREAM_TIMEOUT', $body);
        self::assertStringNotContainsString('private transport details', $body);
    }

    public function testUpstreamErrorIsMappedToSafeBadGateway(): void
    {
        ob_start();
        ExceptionHandler::handle(new UpstreamException('private upstream details'));
        $body = ob_get_clean();

        self::assertSame(502, http_response_code());
        self::assertStringContainsString('UPSTREAM_ERROR', $body);
        self::assertStringNotContainsString('private upstream details', $body);
    }

    public function testSingleflightReusesShortLivedResultForSameKey(): void
    {
        $directory = $this->temporaryDirectory();
        $singleflight = new FileSingleflight($directory, 100, 10);
        $calls = 0;

        $first = $singleflight->run('same-request', function () use (&$calls): array {
            $calls++;

            return ['value' => 'result'];
        });
        $second = $singleflight->run('same-request', function () use (&$calls): array {
            $calls++;

            return ['value' => 'unexpected'];
        });

        self::assertSame(['value' => 'result'], $first);
        self::assertSame($first, $second);
        self::assertSame(1, $calls);
        $this->removeDirectory($directory);
    }

    public function testSingleflightKeepsDifferentKeysIndependent(): void
    {
        $directory = $this->temporaryDirectory();
        $singleflight = new FileSingleflight($directory, 100, 10);

        self::assertSame(['value' => 1], $singleflight->run('request-one', static fn (): array => ['value' => 1]));
        self::assertSame(['value' => 2], $singleflight->run('request-two', static fn (): array => ['value' => 2]));
        $this->removeDirectory($directory);
    }

    public function testProtectedSourceAppliesSingleflightToSourceCalls(): void
    {
        $directory = $this->temporaryDirectory();
        $source = new StubContentSource();
        $protected = new ProtectedContentSource(
            $source,
            new FileSingleflight($directory, 100, 10),
            new FileRequestLimiter($directory, 10, 1, 100),
            new CircuitBreaker($directory, 3, 10),
        );

        self::assertSame(42, $protected->getMovie(42)['id']);
        self::assertSame(42, $protected->getMovie(42)['id']);
        $this->removeDirectory($directory);
    }

    public function testCircuitBreakerOpensAfterConfiguredFailures(): void
    {
        $directory = $this->temporaryDirectory();
        $breaker = new CircuitBreaker($directory, 2, 10);

        $breaker->allow();
        $breaker->recordFailure();
        $breaker->allow();
        $breaker->recordFailure();

        $this->expectException(ServiceUnavailableException::class);
        $breaker->allow();
    }

    private function temporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/gexusfilm-resilience-' . bin2hex(random_bytes(4));
        mkdir($directory, 0770, true);

        return $directory;
    }

    private function removeDirectory(string $directory): void
    {
        array_map('unlink', glob($directory . '/*') ?: []);
        rmdir($directory);
    }
}
