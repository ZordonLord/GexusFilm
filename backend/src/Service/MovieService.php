<?php

declare(strict_types=1);

namespace App\Service;

use App\TmdbClient;
use App\Repository\MovieRepository;
use Exception;

class MovieService
{
    public function __construct(
        private TmdbClient $tmdbClient,
        private ?MovieRepository $repository
    ) {}

    public function getTrendingMovies(): array
    {
        $cacheKey = 'movies:trending:day';
        
        if ($this->repository) {
            $cached = $this->repository->getCachedResponse($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $data = $this->tmdbClient->getTrendingMoviesDay();

        if ($this->repository) {
            $this->repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
            $this->repository->saveMovieSummaries($data['results'] ?? []);
        }

        return $data;
    }

    public function getPopularMovies(): array
    {
        $cacheKey = 'movies:popular';
        
        if ($this->repository) {
            $cached = $this->repository->getCachedResponse($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $data = $this->tmdbClient->getPopularMovies();

        if ($this->repository) {
            $this->repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
            $this->repository->saveMovieSummaries($data['results'] ?? []);
        }

        return $data;
    }

    public function getNowPlayingMovies(): array
    {
        $cacheKey = 'movies:now_playing';
        
        if ($this->repository) {
            $cached = $this->repository->getCachedResponse($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $data = $this->tmdbClient->getNowPlayingMovies();

        if ($this->repository) {
            $this->repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
            $this->repository->saveMovieSummaries($data['results'] ?? []);
        }

        return $data;
    }

    public function getUpcomingMovies(): array
    {
        $cacheKey = 'movies:upcoming';
        
        if ($this->repository) {
            $cached = $this->repository->getCachedResponse($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $data = $this->tmdbClient->getUpcomingMovies();

        if ($this->repository) {
            $this->repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
            $this->repository->saveMovieSummaries($data['results'] ?? []);
        }

        return $data;
    }

    public function getMovieDetails(int $id): array
    {
        if ($this->repository) {
            $cached = $this->repository->getMovieDetails($id);
            if ($cached) {
                return $cached;
            }
        }

        $movie = $this->tmdbClient->getMovie($id);

        if ($this->repository) {
            $this->repository->saveMovieDetails($movie);
        }

        return $movie;
    }

    public function searchMovies(string $query): array
    {
        $cacheKey = 'search:' . mb_strtolower($query);
        
        if ($this->repository) {
            $cached = $this->repository->getCachedResponse($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $result = $this->tmdbClient->search($query);

        if ($this->repository) {
            $this->repository->saveCachedResponse($cacheKey, $result, cache_ttl_minutes());
            $this->repository->saveMovieSummaries($result['results'] ?? []);
        }

        return $result;
    }

    public function discoverMovies(array $params): array
    {
        $genreId = $params['genre_id'] ?? 0;
        $cacheKey = "discover:genre:$genreId";
        
        if ($this->repository) {
            $cached = $this->repository->getCachedResponse($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $data = $this->tmdbClient->discoverMovies([
            'with_genres' => $genreId,
            'sort_by' => 'popularity.desc',
        ]);

        if ($this->repository) {
            $this->repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
            $this->repository->saveMovieSummaries($data['results'] ?? []);
        }

        return $data;
    }

    public function getGenres(): array
    {
        $cacheKey = 'genres:movie';
        
        if ($this->repository) {
            $cached = $this->repository->getCachedResponse($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $data = $this->tmdbClient->getMovieGenres();

        if ($this->repository) {
            $this->repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes() * 7);
        }

        return $data;
    }
}