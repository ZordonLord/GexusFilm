<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Router;
use App\Response;

function createRouter(): Router
{
    $router = new Router();

    $router->get('/api/auth/csrf', static function (array $params): void {
        json_response(auth_controller()->csrf());
    });

    $router->get('/api/auth/session', static function (array $params): void {
        json_response(auth_controller()->currentUser());
    });

    $router->post('/api/auth/register', static function (array $params): void {
        json_response(auth_controller()->register($params, request_csrf_token()), 202);
    });

    $router->post('/api/auth/login', static function (array $params): void {
        json_response(auth_controller()->login($params, request_csrf_token()));
    });

    $router->get('/api/me', static function (array $params): void {
        json_response(auth_controller()->profile());
    });

    $router->post('/api/auth/logout', static function (array $params): void {
        json_response(auth_controller()->logout(request_csrf_token()));
    });

    $router->get('/api/me/favorites', static function (array $params): void {
        json_response(user_media_controller()->getFavorites());
    });

    $router->get('/api/me/history', static function (array $params): void {
        json_response(user_media_controller()->getHistory());
    });

    $router->get('/api/me/favorites/{media_type}/{source_id}', static function (array $params): void {
        json_response(user_media_controller()->isFavorite(
            (string) ($params['media_type'] ?? ''),
            (int) ($params['source_id'] ?? 0),
        ));
    });

    $router->post('/api/me/favorites/{media_type}/{source_id}', static function (array $params): void {
        json_response(user_media_controller()->addFavorite(
            request_body_csrf_token($params),
            (string) ($params['media_type'] ?? ''),
            (int) ($params['source_id'] ?? 0),
        ));
    });

    $router->delete('/api/me/favorites/{media_type}/{source_id}', static function (array $params): void {
        json_response(user_media_controller()->removeFavorite(
            request_body_csrf_token($params),
            (string) ($params['media_type'] ?? ''),
            (int) ($params['source_id'] ?? 0),
        ));
    });

    $router->post('/api/me/history/{media_type}/{source_id}', static function (array $params): void {
        json_response(user_media_controller()->recordVisit(
            request_body_csrf_token($params),
            (string) ($params['media_type'] ?? ''),
            (int) ($params['source_id'] ?? 0),
        ));
    });

    $router->delete('/api/me/history', static function (array $params): void {
        json_response(user_media_controller()->clearHistory(request_body_csrf_token($params)));
    });

    $router->get('/api/health', static function (array $params): void {
        json_response([
            'status' => 'ok',
            'services' => [
                'postgresql' => 'configured',
                'tmdb' => 'configured',
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

    // Фильмы с самым высоким рейтингом
    $router->get('/api/movies/top-rated', static function (array $params) use ($movieController): void {
        $data = $movieController->getTopRated();
        json_response($data);
    });

    // Фильмы в тренде за неделю
    $router->get('/api/movies/trending-week', static function (array $params) use ($movieController): void {
        $data = $movieController->getTrendingWeek();
        json_response($data);
    });

    // Новые фильмы за последние 365 дней
    $router->get('/api/movies/new', static function (array $params) use ($movieController): void {
        $data = $movieController->getNew();
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

    $router->get('/api/movie-collections/{id}', static function (array $params) use ($movieController): void {
        $id = (int) ($params['id'] ?? 0);
        json_response($movieController->getMovieCollection($id));
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

    // Сериалы в тренде за неделю
    $router->get('/api/tv-shows/trending-week', static function (array $params) use ($tvController): void {
        $data = $tvController->getTrendingWeek();
        json_response($data);
    });

    // Популярные сериалы
    $router->get('/api/tv-shows/popular', static function (array $params) use ($tvController): void {
        $data = $tvController->getPopular();
        json_response($data);
    });

    // Сериалы с самым высоким рейтингом
    $router->get('/api/tv-shows/top-rated', static function (array $params) use ($tvController): void {
        $data = $tvController->getTopRated();
        json_response($data);
    });

    // Новые сериалы за последние 365 дней
    $router->get('/api/tv-shows/new', static function (array $params) use ($tvController): void {
        $data = $tvController->getNew();
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
