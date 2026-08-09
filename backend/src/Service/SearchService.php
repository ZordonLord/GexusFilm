<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\MovieRepository;
use App\Repository\SearchRepository;
use Throwable;

final class SearchService implements SearchServiceInterface
{
    public function __construct(
        private ContentSourceInterface $contentSource,
        private SearchRepository $searchRepository,
        private ?MovieRepository $mediaRepository = null,
        private ?CacheService $cache = null,
        private ?MediaIndexPort $mediaIndex = null,
    ) {
    }

    /** @param array<string, mixed> $criteria */
    public function search(string $query, array $criteria): array
    {
        $cacheKey = CacheKeyFactory::searchQuery('search', ['q' => $query] + $criteria);
        $cached = $this->getCached($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $result = $this->searchRepository->search($query, $criteria);

        if ($result === null) {
            $result = $this->sourceSearch($query, $criteria);
            $this->saveSourceResults($result, $criteria['type']);
        }

        $this->saveCached($cacheKey, $result);

        return $result;
    }

    /** @param array<string, mixed> $criteria */
    public function discover(array $criteria): array
    {
        $cacheKey = CacheKeyFactory::searchQuery('discover', $criteria);
        $cached = $this->getCached($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $result = $this->searchRepository->discover($criteria);

        if ($result === null) {
            $result = $this->sourceDiscover($criteria);
            $this->saveSourceResults($result, $criteria['type']);
        }

        $this->saveCached($cacheKey, $result);

        return $result;
    }

    /** @param array<string, mixed> $criteria */
    private function sourceSearch(string $query, array $criteria): array
    {
        $params = ['page' => $criteria['page']];

        if (($criteria['region'] ?? null) !== null) {
            $params['region'] = $criteria['region'];
        }
        if (isset($criteria['year'])) {
            $params[$criteria['type'] === 'tv' ? 'first_air_date_year' : 'year'] = $criteria['year'];
        }

        $result = $criteria['type'] === 'tv'
            ? $this->contentSource->searchTv($query, $params)
            : $this->contentSource->search($query, $params);

        return $this->normalizeResponse($result, (int) $criteria['page']);
    }

    /** @param array<string, mixed> $criteria */
    private function sourceDiscover(array $criteria): array
    {
        $params = [
            'page' => $criteria['page'],
            'sort_by' => $criteria['sort_by'],
        ];

        if (isset($criteria['genre_id'])) {
            $params['with_genres'] = $criteria['genre_id'];
        }
        if (isset($criteria['year'])) {
            $params[$criteria['type'] === 'tv' ? 'first_air_date_year' : 'primary_release_year'] = $criteria['year'];
        }
        if (isset($criteria['min_rating'])) {
            $params['vote_average.gte'] = $criteria['min_rating'];
        }
        if (($criteria['region'] ?? null) !== null) {
            $params['region'] = $criteria['region'];
        }

        $result = $criteria['type'] === 'tv'
            ? $this->contentSource->discoverTv($params)
            : $this->contentSource->discoverMovies($params);

        return $this->normalizeResponse($result, (int) $criteria['page']);
    }

    /** @param array<string, mixed> $response */
    private function normalizeResponse(array $response, int $page): array
    {
        return [
            'page' => (int) ($response['page'] ?? $page),
            'results' => is_array($response['results'] ?? null) ? $response['results'] : [],
            'total_pages' => (int) ($response['total_pages'] ?? 0),
            'total_results' => (int) ($response['total_results'] ?? 0),
        ];
    }

    /** @param array<string, mixed> $response */
    private function saveSourceResults(array $response, string $type): void
    {
        $results = is_array($response['results'] ?? null) ? $response['results'] : [];
        try {
            $saved = $type === 'tv'
                ? $this->mediaRepository?->saveTvSummaries($results) ?? []
                : $this->mediaRepository?->saveMovieSummaries($results) ?? [];
            $this->mediaIndex?->scheduleSavedMedia($saved);
        } catch (Throwable $exception) {
            error_log('Search result persistence failed: ' . $exception::class);
        }
    }

    private function getCached(string $key): ?array
    {
        $cached = $this->cache?->get($key);

        if ($cached !== null) {
            return $cached;
        }

        try {
            return $this->mediaRepository?->getCachedResponse($key);
        } catch (Throwable $exception) {
            error_log('Search persistent cache read failed: ' . $exception::class);

            return null;
        }
    }

    /** @param array<string, mixed> $response */
    private function saveCached(string $key, array $response): void
    {
        $ttl = cache_ttl_config()['search'];
        $this->cache?->put($key, $response, $ttl);
        try {
            $this->mediaRepository?->saveCachedResponse($key, $response, max(1, (int) ceil($ttl / 60)));
        } catch (Throwable $exception) {
            error_log('Search persistent cache write failed: ' . $exception::class);
        }
    }
}
