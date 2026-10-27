<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\MovieRepository;
use App\Repository\SearchRepository;
use Throwable;

final class SearchService implements SearchServiceInterface
{
    private const TMDB_RESULTS_PER_PAGE = 20;
    private const TMDB_MAX_PAGES = 500;

    public function __construct(
        private ContentSourceInterface $contentSource,
        private SearchRepository $searchRepository,
        private ?MovieRepository $mediaRepository = null,
    ) {
    }

    /** @param array<string, mixed> $criteria */
    public function search(string $query, array $criteria): array
    {
        $criteria['page'] = $this->clampRequestedPage($criteria['page'], $criteria['per_page']);
        // Поисковый кэш содержит ответы TMDB, не ограниченные локальными снапшотами.
        $cacheOperation = in_array($criteria['type'], ['all', 'person'], true) ? 'search-multi-v1' : 'search-tmdb-page-cap-v2';
        $cacheKey = CacheKeyFactory::searchQuery($cacheOperation, ['q' => $query] + $criteria);
        $cached = $this->getCached($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $result = $this->sourceSearch($query, $criteria);
        if (!in_array($criteria['type'], ['all', 'person'], true)) {
            $this->saveSourceResults($result, $criteria['type']);
        }

        $this->saveCached($cacheKey, $result);

        return $result;
    }

    /** @param array<string, mixed> $criteria */
    public function discover(array $criteria): array
    {
        $criteria['page'] = $this->clampRequestedPage($criteria['page'], $criteria['per_page']);
        // Каталожный кэш содержит только выдачу источника; старые локальные ответы не переиспользуются.
        $cacheKey = CacheKeyFactory::searchQuery('discover-tmdb-page-cap-v2', $criteria);
        $cached = $this->getCached($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $result = $this->sourceDiscover($criteria);
        $this->saveSourceResults($result, $criteria['type']);

        $this->saveCached($cacheKey, $result);

        return $result;
    }

    /** @param array<string, mixed> $criteria */
    private function sourceSearch(string $query, array $criteria): array
    {
        if ($criteria['type'] === 'all') {
            return $this->sourceAllSearch($query, $criteria);
        }
        if ($criteria['type'] === 'person') {
            return $this->sourcePersonSearch($query, $criteria);
        }

        $perPage = (int) $criteria['per_page'];
        $requestedPage = (int) $criteria['page'];
        $resultOffset = ($requestedPage - 1) * $perPage;
        $firstUpstreamPage = intdiv($resultOffset, self::TMDB_RESULTS_PER_PAGE) + 1;
        $skipFromFirstPage = $resultOffset % self::TMDB_RESULTS_PER_PAGE;
        $upstreamPages = max(1, (int) ceil(($skipFromFirstPage + $perPage) / self::TMDB_RESULTS_PER_PAGE));
        $upstreamPages = min($upstreamPages, self::TMDB_MAX_PAGES - $firstUpstreamPage + 1);
        $params = [];

        if (($criteria['region'] ?? null) !== null) {
            $params['region'] = $criteria['region'];
        }
        if (isset($criteria['year'])) {
            $params[$criteria['type'] === 'tv' ? 'first_air_date_year' : 'year'] = $criteria['year'];
        }

        $results = [];
        $totalResults = 0;
        $totalUpstreamPages = 0;

        for ($pageOffset = 0; $pageOffset < $upstreamPages; $pageOffset++) {
            $params['page'] = $firstUpstreamPage + $pageOffset;
            $response = $criteria['type'] === 'tv'
                ? $this->contentSource->searchTv($query, $params)
                : $this->contentSource->search($query, $params);
            $results = array_merge($results, is_array($response['results'] ?? null) ? $response['results'] : []);
            $totalResults = (int) ($response['total_results'] ?? $totalResults);
            $totalUpstreamPages = (int) ($response['total_pages'] ?? $totalUpstreamPages);

            if (count($results) >= $skipFromFirstPage + $perPage || $params['page'] >= $totalUpstreamPages) {
                break;
            }
        }

        return [
            'page' => $requestedPage,
            'results' => array_slice($results, $skipFromFirstPage, $perPage),
            'total_pages' => $this->catalogTotalPages($totalUpstreamPages, $perPage),
            'total_results' => $totalResults,
        ];
    }

    /** @param array<string, mixed> $criteria @return array<string, mixed> */
    private function sourceAllSearch(string $query, array $criteria): array
    {
        $perPage = (int) $criteria['per_page'];
        $requestedPage = (int) $criteria['page'];
        $offset = ($requestedPage - 1) * $perPage;
        $skipFromFirstPage = $offset % self::TMDB_RESULTS_PER_PAGE;
        $firstUpstreamPage = intdiv($offset, self::TMDB_RESULTS_PER_PAGE) + 1;
        $upstreamPageCount = max(1, (int) ceil(($skipFromFirstPage + $perPage) / self::TMDB_RESULTS_PER_PAGE));
        $upstreamPageCount = min($upstreamPageCount, self::TMDB_MAX_PAGES - $firstUpstreamPage + 1);
        $results = [];
        $totalPages = 0;
        $totalResults = 0;

        for ($pageOffset = 0; $pageOffset < $upstreamPageCount; $pageOffset++) {
            $upstreamPage = $firstUpstreamPage + $pageOffset;
            $response = $this->contentSource->searchMulti($query, ['page' => $upstreamPage]);
            $totalPages = (int) ($response['total_pages'] ?? $totalPages);
            $totalResults = (int) ($response['total_results'] ?? $totalResults);

            foreach ($response['results'] ?? [] as $item) {
                $type = $item['media_type'] ?? null;
                if (!in_array($type, ['movie', 'tv', 'person'], true)) {
                    continue;
                }
                if ($type !== 'person' && !$this->matchesSearchCriteria($item, $criteria)) {
                    continue;
                }

                $results[] = $item;
            }

            if ($upstreamPage >= min($totalPages, self::TMDB_MAX_PAGES)) {
                break;
            }
        }

        $results = array_slice($results, $skipFromFirstPage, $perPage);
        usort($results, static function (array $left, array $right): int {
            $order = ['movie' => 0, 'tv' => 1, 'person' => 2];

            return ($order[$left['media_type']] ?? 3) <=> ($order[$right['media_type']] ?? 3);
        });

        return [
            'page' => $requestedPage,
            'results' => $results,
            'total_pages' => $this->catalogTotalPages($totalPages, $perPage),
            'total_results' => $totalResults,
        ];
    }

    /** @param array<string, mixed> $criteria @return array<string, mixed> */
    private function sourcePersonSearch(string $query, array $criteria): array
    {
        $perPage = (int) $criteria['per_page'];
        $requestedPage = (int) $criteria['page'];
        $resultOffset = ($requestedPage - 1) * $perPage;
        $firstUpstreamPage = intdiv($resultOffset, self::TMDB_RESULTS_PER_PAGE) + 1;
        $skipFromFirstPage = $resultOffset % self::TMDB_RESULTS_PER_PAGE;
        $upstreamPageCount = max(1, (int) ceil(($skipFromFirstPage + $perPage) / self::TMDB_RESULTS_PER_PAGE));
        $upstreamPageCount = min($upstreamPageCount, self::TMDB_MAX_PAGES - $firstUpstreamPage + 1);
        $results = [];
        $totalResults = 0;
        $totalPages = 0;

        for ($pageOffset = 0; $pageOffset < $upstreamPageCount; $pageOffset++) {
            $response = $this->contentSource->searchPeople($query, ['page' => $firstUpstreamPage + $pageOffset]);
            $results = array_merge($results, is_array($response['results'] ?? null) ? $response['results'] : []);
            $totalResults = (int) ($response['total_results'] ?? $totalResults);
            $totalPages = (int) ($response['total_pages'] ?? $totalPages);

            if ($firstUpstreamPage + $pageOffset >= $totalPages) {
                break;
            }
        }

        return [
            'page' => $requestedPage,
            'results' => array_map(
                static fn (array $person): array => ['media_type' => 'person'] + $person,
                array_slice($results, $skipFromFirstPage, $perPage),
            ),
            'total_pages' => $this->catalogTotalPages($totalPages, $perPage),
            'total_results' => $totalResults,
        ];
    }

    /** @param array<string, mixed> $item @param array<string, mixed> $criteria */
    private function matchesSearchCriteria(array $item, array $criteria): bool
    {
        if (isset($criteria['year'])) {
            $date = $item['release_date'] ?? $item['first_air_date'] ?? '';
            if (substr((string) $date, 0, 4) !== (string) $criteria['year']) {
                return false;
            }
        }

        if (isset($criteria['min_rating']) && (float) ($item['vote_average'] ?? 0) < (float) $criteria['min_rating']) {
            return false;
        }

        return true;
    }

    /** @param array<string, mixed> $criteria */
    private function sourceDiscover(array $criteria): array
    {
        $perPage = (int) $criteria['per_page'];
        $requestedPage = (int) $criteria['page'];
        $resultOffset = ($requestedPage - 1) * $perPage;
        $firstUpstreamPage = intdiv($resultOffset, self::TMDB_RESULTS_PER_PAGE) + 1;
        $skipFromFirstPage = $resultOffset % self::TMDB_RESULTS_PER_PAGE;
        $upstreamPages = max(1, (int) ceil(($skipFromFirstPage + $perPage) / self::TMDB_RESULTS_PER_PAGE));
        $upstreamPages = min($upstreamPages, self::TMDB_MAX_PAGES - $firstUpstreamPage + 1);

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

        return [
            'page' => $requestedPage,
            'results' => $results,
            'total_pages' => $this->catalogTotalPages($totalPages, $perPage),
            'total_results' => $totalResults,
        ];
    }

    private function catalogTotalPages(int $upstreamPages, int $perPage): int
    {
        if ($upstreamPages <= 0) {
            return 0;
        }

        return (int) ceil((min($upstreamPages, self::TMDB_MAX_PAGES) * self::TMDB_RESULTS_PER_PAGE) / $perPage);
    }

    private function clampRequestedPage(int $requestedPage, int $perPage): int
    {
        $lastAvailablePage = $this->catalogTotalPages(self::TMDB_MAX_PAGES, $perPage);

        return min($requestedPage, $lastAvailablePage);
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
