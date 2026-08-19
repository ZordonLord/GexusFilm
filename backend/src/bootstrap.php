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
use App\Repository\MovieRepository;
use App\Service\RateLimitCheckerInterface;
use App\Service\SearchService;
use App\Repository\SearchRepository;
use App\Http\Controllers\SearchController;
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
        new \App\TmdbClient(tmdb_api_key(), tmdb_ca_bundle()),
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
    return new MovieService(content_source(), movie_repository());
}

function movie_controller(): MovieController
{
    return new MovieController(movie_service());
}

function tv_service(): TvService
{
    return new TvService(content_source(), movie_repository());
}

function tv_controller(): TvController
{
    return new TvController(tv_service());
}

function search_repository(): SearchRepository
{
    return new SearchRepository(movie_repository());
}

function search_service(): SearchService
{
    return new SearchService(
        content_source(),
        search_repository(),
        movie_repository(),
    );
}

function search_controller(): SearchController
{
    return new SearchController(search_service());
}

function rate_limiter(): RateLimitCheckerInterface
{
    static $limiter = null;

    if ($limiter instanceof RateLimitCheckerInterface) {
        return $limiter;
    }

    try {
        $fallback = new FileRateLimiter(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gexusfilm-rate-limit');
    } catch (Throwable $exception) {
        error_log('File rate limiter initialization failed: ' . $exception->getMessage());
        $fallback = null;
    }

    $limiter = $fallback ?? new \App\Infrastructure\Resilience\FailOpenRateLimiter();

    return $limiter;
}

function json_response(array $data, ?int $status = null): void
{
    Response::json($data, $status);
}
