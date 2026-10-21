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
        $perPage = (int) $criteria['per_page'];
        $requestedPage = (int) $criteria['page'];
        $resultOffset = ($requestedPage - 1) * $perPage;
        $firstUpstreamPage = intdiv($resultOffset, 20) + 1;
        $skipFromFirstPage = $resultOffset % 20;
        $upstreamPages = max(1, (int) ceil(($skipFromFirstPage + $perPage) / 20));

        $params = [
            'sort_by' => $this->sourceSort($criteria),
        ];

        if (isset($criteria['genre_ids'])) {
            $params['with_genres'] = implode(',', $criteria['genre_ids']);
        } elseif (isset($criteria['genre_id'])) {
            $params['with_genres'] = $criteria['genre_id'];
        }
        if (isset($criteria['exclude_genre_ids'])) {
            $params['without_genres'] = implode(',', $criteria['exclude_genre_ids']);
        }
        if (isset($criteria['year_from'])) {
            $params[$criteria['type'] === 'tv' ? 'first_air_date.gte' : 'primary_release_date.gte'] = $criteria['year_from'] . '-01-01';
        }
        if (isset($criteria['year_to'])) {
            $params[$criteria['type'] === 'tv' ? 'first_air_date.lte' : 'primary_release_date.lte'] = $criteria['year_to'] . '-12-31';
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
        if (isset($criteria['date_from'])) {
            $params[$criteria['type'] === 'tv' ? 'first_air_date.gte' : 'primary_release_date.gte'] = $criteria['date_from'];
        }
        if (isset($criteria['date_to'])) {
            $params[$criteria['type'] === 'tv' ? 'first_air_date.lte' : 'primary_release_date.lte'] = $criteria['date_to'];
        }
        if (isset($criteria['vote_count.gte'])) {
            $params['vote_count.gte'] = $criteria['vote_count.gte'];
        }
        if (isset($criteria['language'])) {
            $params['with_original_language'] = $criteria['language'];
        }
        if (isset($criteria['country'])) {
            $params['with_origin_country'] = $criteria['country'];
        }
        if (isset($criteria['watch_region'])) {
            $params['watch_region'] = $criteria['watch_region'];
        }
        if (isset($criteria['with_watch_providers'])) {
            $params['with_watch_providers'] = implode('|', $criteria['with_watch_providers']);
        }
        if (isset($criteria['with_watch_monetization_types'])) {
            $params['with_watch_monetization_types'] = implode('|', $criteria['with_watch_monetization_types']);
        }

        $results = [];
        $totalResults = 0;
        $totalPages = 0;

        for ($pageOffset = 0; $pageOffset < $upstreamPages; $pageOffset++) {
            $params['page'] = $firstUpstreamPage + $pageOffset;
            $response = $criteria['type'] === 'tv'
                ? $this->contentSource->discoverTv($params)
                : $this->contentSource->discoverMovies($params);
            $results = array_merge($results, is_array($response['results'] ?? null) ? $response['results'] : []);
            $totalResults = (int) ($response['total_results'] ?? $totalResults);
            $totalPages = (int) ($response['total_pages'] ?? $totalPages);

            if (count($results) >= $skipFromFirstPage + $perPage || $params['page'] >= $totalPages) {
                break;
            }
        }

        $results = array_slice($results, $skipFromFirstPage, $perPage);

        return [
            'page' => $requestedPage,
            'results' => $results,
            'total_pages' => $totalResults === 0 ? 0 : (int) ceil($totalResults / $perPage),
            'total_results' => $totalResults,
        ];
    }

    /**
     * Внутреннее year-сокращение сохраняет API-контракт, но TMDB требует
     * разные поля даты для фильмов и сериалов.
     *
     * @param array<string, mixed> $criteria
     */
    private function sourceSort(array $criteria): string
    {
        $sort = (string) $criteria['sort_by'];

        if ($sort === 'year.desc' || $sort === 'year.asc') {
            $field = $criteria['type'] === 'tv' ? 'first_air_date' : 'primary_release_date';

            return $field . ($sort === 'year.desc' ? '.desc' : '.asc');
        }

        return $sort;
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
        } catch (Throwable $exception) {
            error_log('Search result persistence failed: ' . $exception::class);
        }
    }

    private function getCached(string $key): ?array
    {
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
        try {
            $this->mediaRepository?->saveCachedResponse($key, $response, cache_ttl_minutes());
        } catch (Throwable $exception) {
            error_log('Search persistent cache write failed: ' . $exception::class);
        }
    }
}
