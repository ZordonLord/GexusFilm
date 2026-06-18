<?php

require_once __DIR__ . '/../../src/bootstrap.php';

$genreId = isset($_GET['genre_id']) ? (int) $_GET['genre_id'] : 0;

if ($genreId <= 0) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    http_response_code(400);
    json_response(['error' => 'genre_id is required']);
    exit;
}

$client = new TmdbClient(tmdb_api_key());
$repository = movie_repository();
$cacheKey = "discover:genre:$genreId";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($repository) {
    $cached = $repository->getCachedResponse($cacheKey);

    if ($cached) {
        json_response($cached);
        exit;
    }
}

$data = $client->discoverMovies([
    'with_genres' => $genreId,
    'sort_by' => 'popularity.desc',
]);

if ($repository) {
    $repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
    $repository->saveMovieSummaries($data['results'] ?? []);
}

json_response($data);
