<?php

require_once __DIR__ . '/../../src/bootstrap.php';

$client = new TmdbClient(tmdb_api_key());
$repository = movie_repository();
$cacheKey = 'movies:popular';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

if ($repository) {
    $cached = $repository->getCachedResponse($cacheKey);

    if ($cached) {
        json_response($cached);
        exit;
    }
}

$data = $client->getPopularMovies();

if ($repository) {
    $repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
    $repository->saveMovieSummaries($data['results'] ?? []);
}

json_response($data);
