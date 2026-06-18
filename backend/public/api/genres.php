<?php

require_once __DIR__ . '/../../src/bootstrap.php';

$client = new TmdbClient(tmdb_api_key());
$repository = movie_repository();
$cacheKey = 'genres:movie';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($repository) {
    $cached = $repository->getCachedResponse($cacheKey);

    if ($cached) {
        json_response($cached);
        exit;
    }
}

$data = $client->getMovieGenres();

if ($repository) {
    $repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes() * 7);
}

json_response($data);
