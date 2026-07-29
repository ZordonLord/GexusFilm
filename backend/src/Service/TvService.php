<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\MovieRepository;

class TvService
{
    public function __construct(
        private ContentSourceInterface $contentSource,
        private ?MovieRepository $repository
    ) {
    }

    public function getTrendingTv(): array
    {
        $cacheKey = 'tv:trending:day';

        if ($this->repository) {
            $cached = $this->repository->getCachedResponse($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $data = $this->contentSource->getTrendingTvDay();

        if ($this->repository) {
            $this->repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
            $this->repository->saveTvSummaries($data['results'] ?? []);
        }

        return $data;
    }

    public function getPopularTv(): array
    {
        $cacheKey = 'tv:popular';

        if ($this->repository) {
            $cached = $this->repository->getCachedResponse($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $data = $this->contentSource->getPopularTv();

        if ($this->repository) {
            $this->repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
            $this->repository->saveTvSummaries($data['results'] ?? []);
        }

        return $data;
    }

    public function getOnTheAirTv(): array
    {
        $cacheKey = 'tv:on_the_air';

        if ($this->repository) {
            $cached = $this->repository->getCachedResponse($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $data = $this->contentSource->getOnTheAirTv();

        if ($this->repository) {
            $this->repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
            $this->repository->saveTvSummaries($data['results'] ?? []);
        }

        return $data;
    }

    public function getAiringTodayTv(): array
    {
        $cacheKey = 'tv:airing_today';

        if ($this->repository) {
            $cached = $this->repository->getCachedResponse($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $data = $this->contentSource->getAiringTodayTv();

        if ($this->repository) {
            $this->repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
            $this->repository->saveTvSummaries($data['results'] ?? []);
        }

        return $data;
    }

    public function getTvDetails(int $id): array
    {
        if ($this->repository) {
            $cached = $this->repository->getTvDetails($id);
            if ($cached) {
                return $cached;
            }
        }

        $tv = $this->contentSource->getTv($id);

        if ($this->repository) {
            $this->repository->saveTvDetails($tv);
        }

        return $tv;
    }

    public function getTvSeason(int $seriesId, int $seasonNumber): array
    {
        $cacheKey = "tv:season:$seriesId:$seasonNumber";

        if ($this->repository) {
            $cached = $this->repository->getCachedResponse($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $data = $this->contentSource->getTvSeason($seriesId, $seasonNumber);

        if ($this->repository) {
            $this->repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
        }

        return $data;
    }

    public function searchTv(string $query): array
    {
        $cacheKey = 'search:tv:' . mb_strtolower($query);

        if ($this->repository) {
            $cached = $this->repository->getCachedResponse($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $result = $this->contentSource->searchTv($query);

        if ($this->repository) {
            $this->repository->saveCachedResponse($cacheKey, $result, cache_ttl_minutes());
            $this->repository->saveTvSummaries($result['results'] ?? []);
        }

        return $result;
    }

    public function discoverTv(array $params): array
    {
        $genreId = (int) ($params['genre_id'] ?? 0);
        $page = (int) ($params['page'] ?? 1);
        $cacheKey = "discover:tv:genre:$genreId:page:$page";

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

        $data = $this->contentSource->discoverTv($tmdbParams);

        if ($this->repository) {
            $this->repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes());
            $this->repository->saveTvSummaries($data['results'] ?? []);
        }

        return $data;
    }

    public function getGenres(): array
    {
        $cacheKey = 'genres:tv';

        if ($this->repository) {
            $cached = $this->repository->getCachedResponse($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $data = $this->contentSource->getTvGenres();

        if ($this->repository) {
            $this->repository->saveCachedResponse($cacheKey, $data, cache_ttl_minutes() * 7);
        }

        return $data;
    }
}
