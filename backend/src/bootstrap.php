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
use App\Repository\MovieRepository;

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

function json_response(array $data, ?int $status = null): void
{
    Response::json($data, $status);
}
