<?php

require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

$client = new TmdbClient(tmdb_api_key());
$repository = movie_repository();

$query = trim($_GET['q'] ?? '');

if (!$query) {
    echo json_encode(['error' => 'Empty query'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $cacheKey = 'search:' . mb_strtolower($query);

    if ($repository) {
        $cached = $repository->getCachedResponse($cacheKey);

        if ($cached) {
            json_response($cached);
            exit;
        }
    }

    $result = $client->search($query);

    if ($repository) {
        $repository->saveCachedResponse($cacheKey, $result, cache_ttl_minutes());
        $repository->saveMovieSummaries($result['results'] ?? []);
    }

    json_response($result);
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
