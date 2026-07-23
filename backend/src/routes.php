<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Router;
use App\TmdbClient;

function createRouter(): Router
{
    $router = new Router();

    $router->get('/api/trending', static function (): void {
        $client = new TmdbClient(tmdb_api_key());
        $repository = movie_repository();
        $cacheKey = 'movies:trending:day';

        if ($repository) {
            $cached = $repository->getCachedResponse($cacheKey);

            if ($cached) {
                json_response($cached);
                return;
            }
        }

        $data = $client->getTrendingMoviesDay();

        if ($repository) {
            $repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
            $repository->saveMovieSummaries($data['results'] ?? []);
        }

        json_response($data);
    });

    $router->get('/api/movies', static function (): void {
        $client = new TmdbClient(tmdb_api_key());
        $repository = movie_repository();
        $cacheKey = 'movies:popular';

        if ($repository) {
            $cached = $repository->getCachedResponse($cacheKey);

            if ($cached) {
                json_response($cached);
                return;
            }
        }

        $data = $client->getPopularMovies();

        if ($repository) {
            $repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
            $repository->saveMovieSummaries($data['results'] ?? []);
        }

        json_response($data);
    });

    $router->get('/api/now-playing', static function (): void {
        $client = new TmdbClient(tmdb_api_key());
        $repository = movie_repository();
        $cacheKey = 'movies:now_playing';

        if ($repository) {
            $cached = $repository->getCachedResponse($cacheKey);

            if ($cached) {
                json_response($cached);
                return;
            }
        }

        $data = $client->getNowPlayingMovies();

        if ($repository) {
            $repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
            $repository->saveMovieSummaries($data['results'] ?? []);
        }

        json_response($data);
    });

    $router->get('/api/upcoming', static function (): void {
        $client = new TmdbClient(tmdb_api_key());
        $repository = movie_repository();
        $cacheKey = 'movies:upcoming';

        if ($repository) {
            $cached = $repository->getCachedResponse($cacheKey);

            if ($cached) {
                json_response($cached);
                return;
            }
        }

        $data = $client->getUpcomingMovies();

        if ($repository) {
            $repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
            $repository->saveMovieSummaries($data['results'] ?? []);
        }

        json_response($data);
    });

    $router->get('/api/genres', static function (): void {
        $client = new TmdbClient(tmdb_api_key());
        $repository = movie_repository();
        $cacheKey = 'genres:movie';

        if ($repository) {
            $cached = $repository->getCachedResponse($cacheKey);

            if ($cached) {
                json_response($cached);
                return;
            }
        }

        $data = $client->getMovieGenres();

        if ($repository) {
            $repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes() * 7);
        }

        json_response($data);
    });

    $router->get('/api/movie', static function (): void {
        $client = new TmdbClient(tmdb_api_key());
        $repository = movie_repository();

        $id = (int) ($_GET['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            json_response([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Movie ID required',
                ],
            ]);

            return;
        }

        if ($repository) {
            $cached = $repository->getMovieDetails($id);

            if ($cached) {
                json_response($cached);
                return;
            }
        }

        $movie = $client->getMovie($id);

        if ($repository) {
            $repository->saveMovieDetails($movie);
        }

        json_response($movie);
    });

    $router->get('/api/search', static function (): void {
        $client = new TmdbClient(tmdb_api_key());
        $repository = movie_repository();

        $query = trim($_GET['q'] ?? '');

        if (!$query) {
            http_response_code(400);
            json_response([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Empty query',
                ],
            ]);

            return;
        }

        $cacheKey = 'search:' . mb_strtolower($query);

        if ($repository) {
            $cached = $repository->getCachedResponse($cacheKey);

            if ($cached) {
                json_response($cached);
                return;
            }
        }

        $result = $client->search($query);

        if ($repository) {
            $repository->saveCachedResponse($cacheKey, $result, cache_ttl_minutes());
            $repository->saveMovieSummaries($result['results'] ?? []);
        }

        json_response($result);
    });

    $router->get('/api/discover', static function (): void {
        $genreId = isset($_GET['genre_id']) ? (int) $_GET['genre_id'] : 0;

        if ($genreId <= 0) {
            http_response_code(400);
            json_response([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'genre_id is required',
                ],
            ]);

            return;
        }

        $client = new TmdbClient(tmdb_api_key());
        $repository = movie_repository();
        $cacheKey = "discover:genre:$genreId";

        if ($repository) {
            $cached = $repository->getCachedResponse($cacheKey);

            if ($cached) {
                json_response($cached);
                return;
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
    });

    return $router;
}
