<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\MovieRepository;
use Exception;

class MovieService
{
    public function __construct(
        private ContentSourceInterface $contentSource,
        private ?MovieRepository $repository
    ) {
    }

    public function getTrendingMovies(): array
    {
        $cacheKey = 'movies:trending:day';

        if ($this->repository) {
            $cached = $this->repository->getCachedResponse($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $data = $this->contentSource->getTrendingMoviesDay();

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

        $data = $this->contentSource->getPopularMovies();

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

        $data = $this->contentSource->getNowPlayingMovies();

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

        $data = $this->contentSource->getUpcomingMovies();

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

        $movie = $this->contentSource->getMovie($id);

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

        $result = $this->contentSource->search($query);

        if ($this->repository) {
            $this->repository->saveCachedResponse($cacheKey, $result, cache_ttl_minutes());
            $this->repository->saveMovieSummaries($result['results'] ?? []);
        }

        return $result;
    }

    public function discoverMovies(array $params): array
    {
        $genreId = (int) ($params['genre_id'] ?? 0);
        $page = (int) ($params['page'] ?? 1);
        $cacheKey = "discover:genre:$genreId:page:$page";

        if ($this->repository) {
            $cached = $this->repository->getCachedResponse($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $tmdbParams = [
            'sort_by' => 'popularity.desc',
            'page' => $page,
        ];

        if ($genreId > 0) {
            $tmdbParams['with_genres'] = $genreId;
        }

        $data = $this->contentSource->discoverMovies($tmdbParams);

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

        $data = $this->contentSource->getMovieGenres();

        if ($this->repository) {
            $this->repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes() * 7);
        }

        return $data;
    }
}
