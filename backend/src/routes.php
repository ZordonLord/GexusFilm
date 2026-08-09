<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Router;
use App\Response;

function createRouter(): Router
{
    $router = new Router();

    $router->get('/api/health', static function (array $params): void {
        $meilisearchAvailable = meilisearch_is_healthy();
        $redisAvailable = redis_is_healthy();

        json_response([
            'status' => $meilisearchAvailable && $redisAvailable ? 'ok' : 'degraded',
            'services' => [
                'meilisearch' => $meilisearchAvailable ? 'available' : 'unavailable',
                'redis' => $redisAvailable ? 'available' : 'unavailable',
            ],
        ], 200);
    });

    $movieController = movie_controller();
    $tvController = tv_controller();
    $searchController = search_controller();

    // ═══════════════════════════════════════════════════════════════
    //  НОВЫЕ REST-маршруты (целевое состояние)
    // ═══════════════════════════════════════════════════════════════

    // ── Фильмы ───────────────────────────────────────────────────

    // Трендовые фильмы
    $router->get('/api/movies/trending', static function (array $params) use ($movieController): void {
        $data = $movieController->getTrending();
        json_response($data);
    });

    // Популярные фильмы
    $router->get('/api/movies/popular', static function (array $params) use ($movieController): void {
        $data = $movieController->getPopular();
        json_response($data);
    });

    // Фильмы в кинотеатрах сейчас
    $router->get('/api/movies/now-playing', static function (array $params) use ($movieController): void {
        $data = $movieController->getNowPlaying();
        json_response($data);
    });

    // Ожидаемые премьеры фильмов
    $router->get('/api/movies/upcoming', static function (array $params) use ($movieController): void {
        $data = $movieController->getUpcoming();
        json_response($data);
    });

    // Детали фильма по ID
    $router->get('/api/movies/{id}', static function (array $params) use ($movieController): void {
        $id = (int) ($params['id'] ?? 0);
        $data = $movieController->getMovie($id);
        json_response($data);
    });

    // ── Сериалы ─────────────────────────────────────────────────

    // Трендовые сериалы
    $router->get('/api/tv-shows/trending', static function (array $params) use ($tvController): void {
        $data = $tvController->getTrending();
        json_response($data);
    });

    // Популярные сериалы
    $router->get('/api/tv-shows/popular', static function (array $params) use ($tvController): void {
        $data = $tvController->getPopular();
        json_response($data);
    });

    // Сериалы в эфире сейчас
    $router->get('/api/tv-shows/on-the-air', static function (array $params) use ($tvController): void {
        $data = $tvController->getOnTheAir();
        json_response($data);
    });

    // Сериалы, выходящие сегодня
    $router->get('/api/tv-shows/airing-today', static function (array $params) use ($tvController): void {
        $data = $tvController->getAiringToday();
        json_response($data);
    });

    // Детали сериала по ID (без ID — популярные)
    $router->get('/api/tv-shows/{id}', static function (array $params) use ($tvController): void {
        $id = (int) ($params['id'] ?? 0);

        if ($id > 0) {
            $data = $tvController->getTv($id);
            json_response($data);
        } else {
            $data = $tvController->getPopular();
            json_response($data);
        }
    });

    // Информация о сезоне сериала
    $router->get('/api/tv-shows/{id}/season/{number}', static function (array $params) use ($tvController): void {
        $seriesId = (int) ($params['id'] ?? 0);
        $seasonNumber = (int) ($params['number'] ?? 0);
        $data = $tvController->getSeason($seriesId, $seasonNumber);
        json_response($data);
    });

    // ── Общие (фильмы + сериалы) ────────────────────────────────

    // Трендовые: ?type=all (фильмы) или ?type=tv (сериалы)
    $router->get('/api/trending', static function (array $params) use ($movieController, $tvController): void {
        $type = $params['type'] ?? 'all';

        if ($type === 'tv') {
            $data = $tvController->getTrending();
        } else {
            $data = $movieController->getTrending();
        }

        json_response($data);
    });

    // Жанры: ?type=movie или ?type=tv
    $router->get('/api/genres', static function (array $params) use ($movieController, $tvController): void {
        $type = $params['type'] ?? 'movie';

        if ($type === 'tv') {
            $data = $tvController->getGenres();
        } else {
            $data = $movieController->getGenres();
        }

        json_response($data);
    });

    // Поиск: ?q=запрос&type=movie|tv
    $router->get('/api/search', static function (array $params) use ($searchController): void {
        json_response($searchController->search($params));
    });

    // Discover (подборка): ?type=movie|tv&genre_id=&page=
    $router->get('/api/discover', static function (array $params) use ($searchController): void {
        json_response($searchController->discover($params));
    });

    return $router;
}
