<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Service\MovieService;
use Exception;

class MovieController
{
    public function __construct(private MovieService $movieService)
    {
    }

    public function getTrending(): array
    {
        return $this->movieService->getTrendingMovies();
    }

    public function getPopular(): array
    {
        return $this->movieService->getPopularMovies();
    }

    public function getTopRated(): array
    {
        return $this->movieService->getTopRatedMovies();
    }

    public function getTrendingWeek(): array
    {
        return $this->movieService->getTrendingMoviesWeek();
    }

    public function getNew(): array
    {
        return $this->movieService->getNewMovies();
    }

    public function getNowPlaying(): array
    {
        return $this->movieService->getNowPlayingMovies();
    }

    public function getUpcoming(): array
    {
        return $this->movieService->getUpcomingMovies();
    }

    public function getMovie(int $id): array
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('Movie ID must be positive');
        }

        return $this->movieService->getMovieDetails($id);
    }

    public function search(string $query): array
    {
        $query = trim($query);

        if (empty($query)) {
            throw new \InvalidArgumentException('Query cannot be empty');
        }

        return $this->movieService->searchMovies($query);
    }

    public function discover(array $params): array
    {
        return $this->movieService->discoverMovies($params);
    }

    public function getGenres(): array
    {
        return $this->movieService->getGenres();
    }
}
