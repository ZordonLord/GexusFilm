<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\MovieRepository;

class TvService
{
    public function __construct(
        private ContentSourceInterface $contentSource,
        private ?MovieRepository $repository,
    ) {
    }

    public function getTrendingTv(): array
    {
        $cacheKey = CacheKeyFactory::catalog('tv', 'trending-day');

        if (($cached = $this->getCached($cacheKey)) !== null) {
            return $cached;
        }

        $data = $this->contentSource->getTrendingTvDay();

        $this->saveCached($cacheKey, $data);
        $this->repository?->saveTvSummaries($data['results'] ?? []);

        return $data;
    }

    public function getPopularTv(): array
    {
        $cacheKey = CacheKeyFactory::catalog('tv', 'popular');

        if (($cached = $this->getCached($cacheKey)) !== null) {
            return $cached;
        }

        $data = $this->contentSource->getPopularTv();

        $this->saveCached($cacheKey, $data);
        $this->repository?->saveTvSummaries($data['results'] ?? []);

        return $data;
    }

    public function getOnTheAirTv(): array
    {
        $cacheKey = CacheKeyFactory::catalog('tv', 'on-the-air');

        if (($cached = $this->getCached($cacheKey)) !== null) {
            return $cached;
        }

        $data = $this->contentSource->getOnTheAirTv();

        $this->saveCached($cacheKey, $data);
        $this->repository?->saveTvSummaries($data['results'] ?? []);

        return $data;
    }

    public function getAiringTodayTv(): array
    {
        $cacheKey = CacheKeyFactory::catalog('tv', 'airing-today');

        if (($cached = $this->getCached($cacheKey)) !== null) {
            return $cached;
        }

        $data = $this->contentSource->getAiringTodayTv();

        $this->saveCached($cacheKey, $data);
        $this->repository?->saveTvSummaries($data['results'] ?? []);

        return $data;
    }

    public function getTvDetails(int $id): array
    {
        $cacheKey = CacheKeyFactory::details('tv', $id);

        if (($cached = $this->getCached($cacheKey)) !== null) {
            return $cached;
        }

        if ($this->repository) {
            $cached = $this->repository->getTvDetails($id);
            if ($cached) {
                return $cached;
            }
        }

        $tv = $this->contentSource->getTv($id);

        $this->repository?->saveTvDetails($tv);
        $this->saveCached($cacheKey, $tv);

        return $tv;
    }

    public function getTvSeason(int $seriesId, int $seasonNumber): array
    {
        $cacheKey = CacheKeyFactory::season($seriesId, $seasonNumber);

        if (($cached = $this->getCached($cacheKey)) !== null) {
            return $cached;
        }

        $data = $this->contentSource->getTvSeason($seriesId, $seasonNumber);

        $this->saveCached($cacheKey, $data);

        return $data;
    }

    public function searchTv(string $query): array
    {
        $cacheKey = CacheKeyFactory::search('tv', $query);

        if (($cached = $this->getCached($cacheKey)) !== null) {
            return $cached;
        }

        $result = $this->contentSource->searchTv($query);

        $this->saveCached($cacheKey, $result);
        $this->repository?->saveTvSummaries($result['results'] ?? []);

        return $result;
    }

    public function discoverTv(array $params): array
    {
        $genreId = (int) ($params['genre_id'] ?? 0);
        $page = (int) ($params['page'] ?? 1);
        $cacheKey = CacheKeyFactory::discover('tv', $genreId, $page);

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

        $data = $this->contentSource->discoverTv($tmdbParams);

        $this->saveCached($cacheKey, $data);
        $this->repository?->saveTvSummaries($data['results'] ?? []);

        return $data;
    }

    public function getGenres(): array
    {
        $cacheKey = CacheKeyFactory::genres('tv');

        if (($cached = $this->getCached($cacheKey)) !== null) {
            return $cached;
        }

        $data = $this->contentSource->getTvGenres();

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
