<?php

declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Redis\RedisGateway;
use App\Infrastructure\Redis\RedisHealthChecker;
use App\Service\CacheKeyFactory;
use App\Service\CacheService;
use App\Service\RateLimiter;
use App\Tests\Support\FailingRedisGateway;
use App\Tests\Support\InMemoryRedisGateway;
use PHPUnit\Framework\TestCase;

final class RedisCacheTest extends TestCase
{
    public function testCacheServiceStoresAndReadsJsonValue(): void
    {
        $gateway = new InMemoryRedisGateway();
        $cache = new CacheService($gateway);
        $value = ['results' => [['id' => 42, 'title' => 'Cached Movie']]];

        $cache->put('cache:test', $value, 300);

        self::assertSame($value, $cache->get('cache:test'));
        self::assertSame(300, $gateway->lastTtl);
    }

    public function testCacheServicePromotesPersistentValueToRedis(): void
    {
        $gateway = new InMemoryRedisGateway();
        $cache = new CacheService($gateway);
        $value = ['results' => [['id' => 7]]];

        self::assertSame($value, $cache->promote('cache:promotion', $value, 120));
        self::assertSame($value, $cache->get('cache:promotion'));
        self::assertSame(120, $gateway->lastTtl);
    }

    public function testCacheServiceFailsOpenWhenRedisIsUnavailable(): void
    {
        $cache = new CacheService(new FailingRedisGateway());

        $cache->put('cache:test', ['value' => true], 300);

        self::assertNull($cache->get('cache:test'));
    }

    public function testRateLimiterBlocksOnlyAfterConfiguredLimit(): void
    {
        $gateway = new InMemoryRedisGateway();
        $limiter = new RateLimiter($gateway);

        $first = $limiter->check('127.0.0.1', 'default', 2, 60);
        $second = $limiter->check('127.0.0.1', 'default', 2, 60);
        $third = $limiter->check('127.0.0.1', 'default', 2, 60);

        self::assertTrue($first->allowed);
        self::assertSame(1, $first->remaining);
        self::assertTrue($second->allowed);
        self::assertSame(0, $second->remaining);
        self::assertFalse($third->allowed);
        self::assertSame(1, $third->retryAfter);
    }

    public function testRateLimiterFailsOpenWhenRedisIsUnavailable(): void
    {
        $result = (new RateLimiter(new FailingRedisGateway()))->check('127.0.0.1', 'search', 60, 60);

        self::assertTrue($result->allowed);
        self::assertSame(60, $result->remaining);
    }

    public function testCacheKeysSeparateMediaTypesAndNormalizeSearchQuery(): void
    {
        self::assertNotSame(
            CacheKeyFactory::search('movie', 'Matrix'),
            CacheKeyFactory::search('tv', 'Matrix'),
        );
        self::assertSame(
            CacheKeyFactory::search('movie', ' Matrix '),
            CacheKeyFactory::search('movie', 'matrix'),
        );
    }

    public function testRedisHealthCheckerReportsUnavailableGateway(): void
    {
        self::assertFalse((new RedisHealthChecker(new FailingRedisGateway()))->isHealthy());
    }
}
