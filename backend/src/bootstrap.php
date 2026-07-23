<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

use App\Database;
use App\Response;
use App\Service\MovieService;
use App\Http\Controllers\MovieController;
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

function movie_service(): MovieService
{
    return new MovieService(new \App\TmdbClient(tmdb_api_key()), movie_repository());
}

function movie_controller(): MovieController
{
    return new MovieController(movie_service());
}

function json_response(array $data): void
{
    Response::json($data);
}
