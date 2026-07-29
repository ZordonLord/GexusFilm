<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Router;

function createRouter(): Router
{
    $router = new Router();
    $movieController = movie_controller();
    $tvController = tv_controller();

    // ── Movie routes ────────────────────────────────────────

    $router->get('/api/trending', static function () use ($movieController): void {
        $data = $movieController->getTrending();
        json_response($data);
    });

    $router->get('/api/movies', static function () use ($movieController): void {
        $data = $movieController->getPopular();
        json_response($data);
    });

    $router->get('/api/now-playing', static function () use ($movieController): void {
        $data = $movieController->getNowPlaying();
        json_response($data);
    });

    $router->get('/api/upcoming', static function () use ($movieController): void {
        $data = $movieController->getUpcoming();
        json_response($data);
    });

    $router->get('/api/genres', static function () use ($movieController): void {
        $data = $movieController->getGenres();
        json_response($data);
    });

    $router->get('/api/movie', static function () use ($movieController): void {
        $id = (int) ($_GET['id'] ?? 0);
        $data = $movieController->getMovie($id);
        json_response($data);
    });

    $router->get('/api/search', static function () use ($movieController): void {
        $query = $_GET['q'] ?? '';
        $data = $movieController->search($query);
        json_response($data);
    });

    $router->get('/api/discover', static function () use ($movieController): void {
        $params = [
            'genre_id' => (int) ($_GET['genre_id'] ?? 0),
            'page' => (int) ($_GET['page'] ?? 1),
        ];
        $data = $movieController->discover($params);
        json_response($data);
    });

    // ── TV routes ───────────────────────────────────────────

    $router->get('/api/tv/trending', static function () use ($tvController): void {
        $data = $tvController->getTrending();
        json_response($data);
    });

    $router->get('/api/tv/popular', static function () use ($tvController): void {
        $data = $tvController->getPopular();
        json_response($data);
    });

    $router->get('/api/tv/on-the-air', static function () use ($tvController): void {
        $data = $tvController->getOnTheAir();
        json_response($data);
    });

    $router->get('/api/tv/airing-today', static function () use ($tvController): void {
        $data = $tvController->getAiringToday();
        json_response($data);
    });

    $router->get('/api/tv/genres', static function () use ($tvController): void {
        $data = $tvController->getGenres();
        json_response($data);
    });

    $router->get('/api/tv-shows', static function () use ($tvController): void {
        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            $data = $tvController->getTv($id);
            json_response($data);
        } else {
            $data = $tvController->getPopular();
            json_response($data);
        }
    });

    $router->get('/api/tv-shows/season', static function () use ($tvController): void {
        $seriesId = (int) ($_GET['series_id'] ?? 0);
        $seasonNumber = (int) ($_GET['season_number'] ?? 0);
        $data = $tvController->getSeason($seriesId, $seasonNumber);
        json_response($data);
    });

    $router->get('/api/tv/search', static function () use ($tvController): void {
        $query = $_GET['q'] ?? '';
        $data = $tvController->search($query);
        json_response($data);
    });

    $router->get('/api/tv/discover', static function () use ($tvController): void {
        $params = [
            'genre_id' => (int) ($_GET['genre_id'] ?? 0),
            'page' => (int) ($_GET['page'] ?? 1),
        ];
        $data = $tvController->discover($params);
        json_response($data);
    });

    return $router;
}
