<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\MovieRepository;

class MovieService
{
    public function __construct(
        private ContentSourceInterface $contentSource,
        private ?MovieRepository $repository,
        private ?CacheService $cache = null,
        private ?MediaIndexPort $mediaIndex = null,
    ) {
    }

    public function getTrendingMovies(): array
    {
        $cacheKey = CacheKeyFactory::catalog('movie', 'trending-day');

        if (($cached = $this->getCached($cacheKey, cache_ttl_config()['catalog'])) !== null) {
            return $cached;
        }

        $data = $this->contentSource->getTrendingMoviesDay();

        $this->saveCached($cacheKey, $data, cache_ttl_config()['catalog']);

        $this->scheduleIndex($this->repository?->saveMovieSummaries($data['results'] ?? []) ?? []);

        return $data;
    }

    public function getPopularMovies(): array
    {
        $cacheKey = CacheKeyFactory::catalog('movie', 'popular');

        if (($cached = $this->getCached($cacheKey, cache_ttl_config()['catalog'])) !== null) {
            return $cached;
        }

        $data = $this->contentSource->getPopularMovies();

        $this->saveCached($cacheKey, $data, cache_ttl_config()['catalog']);

        $this->scheduleIndex($this->repository?->saveMovieSummaries($data['results'] ?? []) ?? []);

        return $data;
    }

    public function getNowPlayingMovies(): array
    {
        $cacheKey = CacheKeyFactory::catalog('movie', 'now-playing');

        if (($cached = $this->getCached($cacheKey, cache_ttl_config()['catalog'])) !== null) {
            return $cached;
        }

        $data = $this->contentSource->getNowPlayingMovies();

        $this->saveCached($cacheKey, $data, cache_ttl_config()['catalog']);

        $this->scheduleIndex($this->repository?->saveMovieSummaries($data['results'] ?? []) ?? []);

        return $data;
    }

    public function getUpcomingMovies(): array
    {
        $cacheKey = CacheKeyFactory::catalog('movie', 'upcoming');

        if (($cached = $this->getCached($cacheKey, cache_ttl_config()['catalog'])) !== null) {
            return $cached;
        }

        $data = $this->contentSource->getUpcomingMovies();

        $this->saveCached($cacheKey, $data, cache_ttl_config()['catalog']);

        $this->scheduleIndex($this->repository?->saveMovieSummaries($data['results'] ?? []) ?? []);

        return $data;
    }

    public function getMovieDetails(int $id): array
    {
        $cacheKey = CacheKeyFactory::details('movie', $id);

        if (($cached = $this->getCached($cacheKey, cache_ttl_config()['details'])) !== null) {
            return $cached;
        }

        if ($this->repository) {
            $cached = $this->repository->getMovieDetails($id);
            if ($cached) {
                return $this->cache?->promote($cacheKey, $cached, cache_ttl_config()['details']) ?? $cached;
            }
        }

        $movie = $this->contentSource->getMovie($id);

        $this->scheduleIndex([$this->repository?->saveMovieDetails($movie)]);

        $this->saveCached($cacheKey, $movie, cache_ttl_config()['details']);

        return $movie;
    }

    public function searchMovies(string $query): array
    {
        $cacheKey = CacheKeyFactory::search('movie', $query);

        if (($cached = $this->getCached($cacheKey, cache_ttl_config()['search'])) !== null) {
            return $cached;
        }

        $result = $this->contentSource->search($query);

        $this->saveCached($cacheKey, $result, cache_ttl_config()['search']);

        $this->scheduleIndex($this->repository?->saveMovieSummaries($result['results'] ?? []) ?? []);

        return $result;
    }

    public function discoverMovies(array $params): array
    {
        $genreId = (int) ($params['genre_id'] ?? 0);
        $page = (int) ($params['page'] ?? 1);
        $cacheKey = CacheKeyFactory::discover('movie', $genreId, $page);

        if (($cached = $this->getCached($cacheKey, cache_ttl_config()['discover'])) !== null) {
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

        $this->saveCached($cacheKey, $data, cache_ttl_config()['discover']);

        $this->scheduleIndex($this->repository?->saveMovieSummaries($data['results'] ?? []) ?? []);

        return $data;
    }

    public function getGenres(): array
    {
        $cacheKey = CacheKeyFactory::genres('movie');

        if (($cached = $this->getCached($cacheKey, cache_ttl_config()['genres'])) !== null) {
            return $cached;
        }

        $data = $this->contentSource->getMovieGenres();

        $this->saveCached($cacheKey, $data, cache_ttl_config()['genres']);

        return $data;
    }

    private function getCached(string $cacheKey, int $ttlSeconds): ?array
    {
        $cached = $this->cache?->get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $cached = $this->repository?->getCachedResponse($cacheKey);

        if ($cached !== null) {
            return $this->cache?->promote($cacheKey, $cached, $ttlSeconds) ?? $cached;
        }

        return $cached;
    }

    /** @param list<array<string, mixed>|null> $records */
    private function scheduleIndex(array $records): void
    {
        $records = array_values(array_filter($records));
        $this->mediaIndex?->scheduleSavedMedia($records);
    }

    private function saveCached(string $cacheKey, array $data, int $ttlSeconds): void
    {
        $this->cache?->put($cacheKey, $data, $ttlSeconds);

        if ($this->repository) {
            $this->repository->saveCachedResponse($cacheKey, $data, max(1, (int) ceil($ttlSeconds / 60)));
        }
    }
}
