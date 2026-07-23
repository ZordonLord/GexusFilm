<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Router;

function createRouter(): Router
{
    $router = new Router();
    $movieController = movie_controller();

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
        ];
        $data = $movieController->discover($params);
        json_response($data);
    });

    return $router;
}
