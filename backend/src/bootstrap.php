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

function json_response(array $data): void
{
    Response::json($data);
}
