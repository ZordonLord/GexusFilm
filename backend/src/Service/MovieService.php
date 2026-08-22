<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\MovieRepository;
use DateInterval;
use DateTimeImmutable;

class MovieService
{
    public function __construct(
        private ContentSourceInterface $contentSource,
        private ?MovieRepository $repository,
    ) {
    }

    public function getTrendingMovies(): array
    {
        $cacheKey = CacheKeyFactory::catalog('movie', 'trending-day');

        if (($cached = $this->getCached($cacheKey)) !== null) {
            return $cached;
        }

        $data = $this->contentSource->getTrendingMoviesDay();

        $this->saveCached($cacheKey, $data);
        $this->repository?->saveMovieSummaries($data['results'] ?? []);

        return $data;
    }

    public function getTrendingMoviesWeek(): array
    {
        $cacheKey = CacheKeyFactory::catalog('movie', 'trending-week');

        if (($cached = $this->getCached($cacheKey)) !== null) {
            return $cached;
        }

        $data = $this->contentSource->getTrendingMoviesWeek();

        $this->saveCached($cacheKey, $data);
        $this->repository?->saveMovieSummaries($data['results'] ?? []);

        return $data;
    }

    public function getPopularMovies(): array
    {
        $cacheKey = CacheKeyFactory::catalog('movie', 'popular');

        if (($cached = $this->getCached($cacheKey)) !== null) {
            return $cached;
        }

        $data = $this->contentSource->getPopularMovies();

        $this->saveCached($cacheKey, $data);
        $this->repository?->saveMovieSummaries($data['results'] ?? []);

        return $data;
    }

    public function getTopRatedMovies(): array
    {
        $cacheKey = CacheKeyFactory::catalog('movie', 'top-rated');

        if (($cached = $this->getCached($cacheKey)) !== null) {
            return $cached;
        }

        $data = $this->contentSource->getTopRatedMovies();

        $this->saveCached($cacheKey, $data);
        $this->repository?->saveMovieSummaries($data['results'] ?? []);

        return $data;
    }

    public function getNowPlayingMovies(): array
    {
        $cacheKey = CacheKeyFactory::catalog('movie', 'now-playing');

        if (($cached = $this->getCached($cacheKey)) !== null) {
            return $cached;
        }

        $data = $this->contentSource->getNowPlayingMovies();

        $this->saveCached($cacheKey, $data);
        $this->repository?->saveMovieSummaries($data['results'] ?? []);

        return $data;
    }

    public function getUpcomingMovies(): array
    {
        $cacheKey = CacheKeyFactory::catalog('movie', 'upcoming');

        if (($cached = $this->getCached($cacheKey)) !== null) {
            return $cached;
        }

        $data = $this->contentSource->getUpcomingMovies();

        $this->saveCached($cacheKey, $data);
        $this->repository?->saveMovieSummaries($data['results'] ?? []);

        return $data;
    }

    public function getNewMovies(): array
    {
        $cacheKey = CacheKeyFactory::catalog('movie', 'new');

        if (($cached = $this->getCached($cacheKey)) !== null) {
            return $cached;
        }

        $today = new DateTimeImmutable('today');
        $data = $this->contentSource->discoverMovies([
            'sort_by' => 'primary_release_date.desc',
            'primary_release_date.gte' => $today->sub(new DateInterval('P365D'))->format('Y-m-d'),
            'primary_release_date.lte' => $today->format('Y-m-d'),
            'page' => 1,
        ]);

        $this->saveCached($cacheKey, $data);
        $this->repository?->saveMovieSummaries($data['results'] ?? []);

        return $data;
    }

    public function getMovieDetails(int $id): array
    {
        $cacheKey = CacheKeyFactory::details('movie', $id);

        if (($cached = $this->getCached($cacheKey)) !== null) {
            return $cached;
        }

        if ($this->repository) {
            $cached = $this->repository->getMovieDetails($id);
            if ($cached) {
                return $cached;
            }
        }

        $movie = $this->contentSource->getMovie($id);

        $this->repository?->saveMovieDetails($movie);
        $this->saveCached($cacheKey, $movie);

        return $movie;
    }

    public function searchMovies(string $query): array
    {
        $cacheKey = CacheKeyFactory::search('movie', $query);

        if (($cached = $this->getCached($cacheKey)) !== null) {
            return $cached;
        }

        $result = $this->contentSource->search($query);

        $this->saveCached($cacheKey, $result);
        $this->repository?->saveMovieSummaries($result['results'] ?? []);

        return $result;
    }

    public function discoverMovies(array $params): array
    {
        $genreId = (int) ($params['genre_id'] ?? 0);
        $page = (int) ($params['page'] ?? 1);
        $cacheKey = CacheKeyFactory::discover('movie', $genreId, $page);

        if (($cached = $this->getCached($cacheKey)) !== null) {
            return $cached;
        }

        $tmdbParams = [
            'sort_by' => 'popularity.desc',
            'page' => $page,
        ];

        if ($genreId > 0) {
            $tmdbParams['with_genres'] = $genreId;
        }

        $data = $this->contentSource->discoverMovies($tmdbParams);

        $this->saveCached($cacheKey, $data);
        $this->repository?->saveMovieSummaries($data['results'] ?? []);

        return $data;
    }

    public function getGenres(): array
    {
        $cacheKey = CacheKeyFactory::genres('movie');

        if (($cached = $this->getCached($cacheKey)) !== null) {
            return $cached;
        }

        $data = $this->contentSource->getMovieGenres();

        $this->saveCached($cacheKey, $data);

        return $data;
    }

    private function getCached(string $cacheKey): ?array
    {
        return $this->repository?->getCachedResponse($cacheKey);
    }

    private function saveCached(string $cacheKey, array $data): void
    {
        $this->repository?->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
    }
}
