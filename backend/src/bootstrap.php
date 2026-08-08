<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

use App\Database;
use App\Response;
use App\Service\MovieService;
use App\Service\TvService;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\TvController;
use App\Infrastructure\Meilisearch\MeilisearchClient;
use App\Infrastructure\Meilisearch\MeilisearchGateway;
use App\Infrastructure\Meilisearch\MeilisearchHealthChecker;
use App\Infrastructure\Meilisearch\MeilisearchIndexManager;
use App\Infrastructure\Meilisearch\MediaDocumentFactory;
use App\Infrastructure\Redis\RedisClient;
use App\Infrastructure\Redis\RedisGateway;
use App\Infrastructure\Redis\RedisHealthChecker;
use App\Repository\MovieRepository;
use App\Service\CacheService;
use App\Service\RateLimiter;
use App\Service\RateLimitCheckerInterface;
use App\Service\MediaIndexPort;
use App\Service\MediaIndexSyncService;
use App\Infrastructure\Resilience\FileRateLimiter;
use App\Infrastructure\Resilience\ProtectedContentSource;
use App\Infrastructure\Resilience\CircuitBreaker;
use App\Infrastructure\Resilience\FileRequestLimiter;
use App\Infrastructure\Resilience\FileSingleflight;

function movie_repository(): ?MovieRepository
{
    try {
        return new MovieRepository(Database::connect());
    } catch (Throwable $exception) {
        error_log('Database unavailable: ' . $exception->getMessage());

        return null;
    }
}

function content_source(): \App\Service\ContentSourceInterface
{
    static $source;

    if ($source instanceof \App\Service\ContentSourceInterface) {
        return $source;
    }

    $config = tmdb_protection_config();
    $source = new ProtectedContentSource(
        new \App\TmdbClient(tmdb_api_key()),
        new FileSingleflight(
            $config['coordination_directory'],
            $config['singleflight_timeout_ms'],
            $config['singleflight_result_ttl'],
        ),
        new FileRequestLimiter(
            $config['coordination_directory'],
            $config['requests_per_second'],
            $config['max_concurrent'],
            $config['queue_timeout_ms'],
        ),
        new CircuitBreaker(
            $config['coordination_directory'],
            $config['circuit_failure_threshold'],
            $config['circuit_cooldown_seconds'],
        ),
    );

    return $source;
}

function movie_service(): MovieService
{
    return new MovieService(content_source(), movie_repository(), cache_service(), media_index_port());
}

function movie_controller(): MovieController
{
    return new MovieController(movie_service());
}

function tv_service(): TvService
{
    return new TvService(content_source(), movie_repository(), cache_service(), media_index_port());
}

function tv_controller(): TvController
{
    return new TvController(tv_service());
}

function meilisearch_gateway(): MeilisearchGateway
{
    static $gateway = null;

    if ($gateway instanceof MeilisearchGateway) {
        return $gateway;
    }

    $config = meilisearch_config();
    $gateway = new MeilisearchClient($config['host'], $config['api_key']);

    return $gateway;
}

function meilisearch_index_manager(): MeilisearchIndexManager
{
    return new MeilisearchIndexManager(
        meilisearch_gateway(),
        meilisearch_config()['media_index'],
        meilisearch_config()['people_index'],
    );
}

function media_index_port(): ?MediaIndexPort
{
    try {
        $config = meilisearch_config();

        return new MediaIndexSyncService(
            meilisearch_gateway(),
            new MediaDocumentFactory(),
            $config['media_index'],
        );
    } catch (Throwable $exception) {
        error_log('Media index initialization failed: ' . $exception->getMessage());

        return null;
    }
}

function media_index_sync_service(): MediaIndexSyncService
{
    $config = meilisearch_config();

    return new MediaIndexSyncService(
        meilisearch_gateway(),
        new MediaDocumentFactory(),
        $config['media_index'],
    );
}

function meilisearch_is_healthy(): bool
{
    try {
        return (new MeilisearchHealthChecker(meilisearch_gateway()))->isHealthy();
    } catch (Throwable $exception) {
        error_log('Meilisearch health check failed: ' . $exception->getMessage());

        return false;
    }
}

function redis_gateway(): ?RedisGateway
{
    static $gateway;
    static $initialized = false;

    if ($initialized) {
        return $gateway;
    }

    $initialized = true;

    try {
        $config = redis_config();
        $parameters = [
            'scheme' => 'tcp',
            'host' => $config['host'],
            'port' => $config['port'],
            'timeout' => $config['timeout'],
        ];

        if ($config['password'] !== null) {
            $parameters['password'] = $config['password'];
        }

        $gateway = new RedisClient(new \Predis\Client($parameters));
    } catch (Throwable $exception) {
        error_log('Redis client initialization failed: ' . $exception->getMessage());
        $gateway = null;
    }

    return $gateway;
}

function cache_service(): ?CacheService
{
    $gateway = redis_gateway();

    return $gateway === null ? null : new CacheService($gateway);
}

function rate_limiter(): RateLimitCheckerInterface
{
    $gateway = redis_gateway();
    $fallback = new FileRateLimiter(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gexusfilm-rate-limit');

    return $gateway === null ? $fallback : new RateLimiter($gateway, $fallback);
}

function redis_is_healthy(): bool
{
    $gateway = redis_gateway();

    return $gateway !== null && (new RedisHealthChecker($gateway))->isHealthy();
}

function json_response(array $data, ?int $status = null): void
{
    Response::json($data, $status);
}
