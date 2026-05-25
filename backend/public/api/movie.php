<?php

require_once __DIR__ . '/../../src/bootstrap.php';

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

$client = new TmdbClient(tmdb_api_key());
$repository = movie_repository();

$id = (int) ($_GET['id'] ?? 0);

if (!$id) {
    echo json_encode([
        'error' => 'Movie ID required'
    ]);

    exit;
}

if ($repository) {
    $cached = $repository->getMovieDetails($id);

    if ($cached) {
        json_response($cached);
        exit;
    }
}

$movie = $client->getMovie($id);

if ($repository) {
    $repository->saveMovieDetails($movie);
}

json_response($movie);
