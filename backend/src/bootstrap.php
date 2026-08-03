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
use App\Infrastructure\Redis\RedisClient;
use App\Infrastructure\Redis\RedisGateway;
use App\Infrastructure\Redis\RedisHealthChecker;
use App\Repository\MovieRepository;
use App\Service\CacheService;
use App\Service\RateLimiter;

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
    return new \App\TmdbClient(tmdb_api_key());
}

function movie_service(): MovieService
{
    return new MovieService(content_source(), movie_repository(), cache_service());
}

function movie_controller(): MovieController
{
    return new MovieController(movie_service());
}

function tv_service(): TvService
{
    return new TvService(content_source(), movie_repository(), cache_service());
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

function rate_limiter(): ?RateLimiter
{
    $gateway = redis_gateway();

    return $gateway === null ? null : new RateLimiter($gateway);
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
