<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/MovieRepository.php';
require_once __DIR__ . '/TmdbClient.php';

function movie_repository(): ?MovieRepository
{
    try {
        return new MovieRepository(Database::connect());
    } catch (Throwable $exception) {
        error_log('Database unavailable: ' . $exception->getMessage());

        return null;
    }
}

function json_response(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}
