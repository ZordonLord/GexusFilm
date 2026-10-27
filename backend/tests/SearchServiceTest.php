<?php

declare(strict_types=1);

namespace App\Tests;

use App\Repository\SearchRepository;
use App\Repository\MovieRepository;
use App\Service\SearchService;
use App\Tests\Support\StubContentSource;
use PHPUnit\Framework\TestCase;

final class SearchServiceTest extends TestCase
{
    public function testDiscoverUsesTmdbEvenWhenPostgresHasLocalResults(): void
    {
        $source = new StubContentSource();
        $mediaRepository = $this->createMock(MovieRepository::class);
        $mediaRepository->expects(self::never())->method('discoverMedia');
        $mediaRepository->method('getCachedResponse')->willReturn(null);
        $mediaRepository->expects(self::once())->method('saveMovieSummaries');
        $mediaRepository->method('saveCachedResponse');
        $service = new SearchService($source, new SearchRepository($mediaRepository), $mediaRepository);

        $result = $service->discover([
            'type' => 'movie',
            'page' => 1,
            'per_page' => 20,
            'sort_by' => 'popularity.desc',
        ]);

        self::assertSame('Discovered Movie', $result['results'][0]['title']);
        self::assertCount(1, $source->discoverMovieRequests);
    }

    public function testDiscoverPassesFiltersAndSortToTmdbWhenPostgresIsAvailable(): void
    {
        $source = new StubContentSource();
        $mediaRepository = $this->createMock(MovieRepository::class);
        $mediaRepository->expects(self::never())->method('discoverMedia');
        $mediaRepository->method('getCachedResponse')->willReturn(null);
        $mediaRepository->method('saveCachedResponse');
        $service = new SearchService($source, new SearchRepository($mediaRepository), $mediaRepository);

        $service->discover([
            'type' => 'movie',
            'page' => 1,
            'per_page' => 20,
            'sort_by' => 'year.desc',
            'genre_ids' => [28],
            'min_rating' => 7.5,
        ]);

        self::assertSame('primary_release_date.desc', $source->lastDiscoverMovieParams['sort_by']);
        self::assertSame('28', $source->lastDiscoverMovieParams['with_genres']);
        self::assertSame(7.5, $source->lastDiscoverMovieParams['vote_average.gte']);
    }

    public function testDiscoverReturnsRequestedTmdbPageInsteadOfFirstPage(): void
    {
        $source = new StubContentSource();
        $source->discoverMovieResponses = [
            2 => [
                'page' => 2,
                'results' => [['id' => 42, 'title' => 'Second page movie']],
                'total_pages' => 5,
                'total_results' => 100,
            ],
        ];
        $mediaRepository = $this->createMock(MovieRepository::class);
        $mediaRepository->expects(self::never())->method('discoverMedia');
        $mediaRepository->method('getCachedResponse')->willReturn(null);
        $mediaRepository->method('saveCachedResponse');
        $service = new SearchService($source, new SearchRepository($mediaRepository), $mediaRepository);

        $result = $service->discover([
            'type' => 'movie',
            'page' => 2,
            'per_page' => 20,
            'sort_by' => 'popularity.desc',
        ]);

        self::assertSame(2, $result['page']);
        self::assertSame('Second page movie', $result['results'][0]['title']);
        self::assertSame(2, $source->lastDiscoverMovieParams['page']);
    }

    public function testDiscoverFallsBackToTmdbWhenPostgresIsUnavailable(): void
    {
        $source = new StubContentSource();
        $service = new SearchService($source, new SearchRepository(null));

        $result = $service->discover([
            'type' => 'movie',
            'page' => 1,
            'per_page' => 20,
            'sort_by' => 'popularity.desc',
        ]);

        self::assertSame('Discovered Movie', $result['results'][0]['title']);
        self::assertCount(1, $source->discoverMovieRequests);
    }

    public function testDiscoverCacheKeysDoNotMixFiltersOrPages(): void
    {
        $source = new StubContentSource();
        $mediaRepository = $this->createMock(MovieRepository::class);
        $mediaRepository->expects(self::never())->method('discoverMedia');
        $cacheKeys = [];
        $mediaRepository->expects(self::exactly(3))
            ->method('getCachedResponse')
            ->willReturnCallback(static function (string $key) use (&$cacheKeys): ?array {
                $cacheKeys[] = $key;

                return null;
            });
        $mediaRepository->expects(self::exactly(3))->method('saveCachedResponse');
        $service = new SearchService($source, new SearchRepository($mediaRepository), $mediaRepository);

        foreach (
            [
                ['genre_ids' => [28], 'page' => 1],
                ['genre_ids' => [35], 'page' => 1],
                ['genre_ids' => [28], 'page' => 2],
            ] as $request
        ) {
            $service->discover($request + [
                'type' => 'movie',
                'per_page' => 20,
                'sort_by' => 'popularity.desc',
            ]);
        }

        self::assertCount(3, array_unique($cacheKeys));
        foreach ($cacheKeys as $cacheKey) {
            self::assertStringStartsWith('cache:discover-tmdb:', $cacheKey);
        }
        self::assertCount(3, $source->discoverMovieRequests);
    }

    public function testSearchUsesTmdbEvenWhenPostgresHasLocalResults(): void
    {
        $source = new StubContentSource();
        $mediaRepository = $this->createMock(MovieRepository::class);
        $mediaRepository->expects(self::never())->method('searchMedia');
        $mediaRepository->method('getCachedResponse')->willReturn(null);
        $mediaRepository->expects(self::once())->method('saveMovieSummaries');
        $mediaRepository->method('saveCachedResponse');
        $service = new SearchService($source, new SearchRepository($mediaRepository), $mediaRepository);

        $result = $service->search('matrix', [
            'type' => 'movie',
            'page' => 1,
            'per_page' => 20,
            'sort_by' => 'popularity.desc',
        ]);

        self::assertSame('Result for matrix', $result['results'][0]['title']);
        self::assertCount(1, $source->searchMovieRequests);
    }

    public function testSearchPassesTypeQueryFiltersAndPageToTmdb(): void
    {
        $source = new StubContentSource();
        $mediaRepository = $this->createMock(MovieRepository::class);
        $mediaRepository->expects(self::never())->method('searchMedia');
        $mediaRepository->method('getCachedResponse')->willReturn(null);
        $mediaRepository->method('saveCachedResponse');
        $service = new SearchService($source, new SearchRepository($mediaRepository), $mediaRepository);

        $service->search('matrix', [
            'type' => 'tv',
            'page' => 2,
            'per_page' => 20,
            'sort_by' => 'popularity.desc',
            'year' => 2020,
            'region' => 'RU',
        ]);

        self::assertSame('matrix', $source->searchTvRequests[0]['query']);
        self::assertSame(2, $source->searchTvRequests[0]['params']['page']);
        self::assertSame(2020, $source->searchTvRequests[0]['params']['first_air_date_year']);
        self::assertSame('RU', $source->searchTvRequests[0]['params']['region']);
    }

    public function testSearchPaginatesAcrossTmdbPagesForLargePerPage(): void
    {
        $source = new StubContentSource();
        $source->searchMovieResponses = [
            2 => [
                'page' => 2,
                'results' => array_map(
                    static fn (int $id): array => ['id' => $id, 'title' => 'Movie ' . $id],
                    range(21, 40),
                ),
                'total_pages' => 3,
                'total_results' => 60,
            ],
            3 => [
                'page' => 3,
                'results' => array_map(
                    static fn (int $id): array => ['id' => $id, 'title' => 'Movie ' . $id],
                    range(41, 60),
                ),
                'total_pages' => 3,
                'total_results' => 60,
            ],
        ];
        $mediaRepository = $this->createMock(MovieRepository::class);
        $mediaRepository->method('getCachedResponse')->willReturn(null);
        $mediaRepository->method('saveCachedResponse');
        $service = new SearchService($source, new SearchRepository($mediaRepository), $mediaRepository);

        $result = $service->search('matrix', [
            'type' => 'movie',
            'page' => 2,
            'per_page' => 25,
            'sort_by' => 'popularity.desc',
        ]);

        self::assertSame(2, $result['page']);
        self::assertCount(25, $result['results']);
        self::assertSame(26, $result['results'][0]['id']);
        self::assertSame(50, $result['results'][24]['id']);
        self::assertSame([2, 3], array_column(array_column($source->searchMovieRequests, 'params'), 'page'));
    }

    public function testSearchFallsBackToTmdbWhenPostgresIsUnavailable(): void
    {
        $source = new StubContentSource();
        $service = new SearchService($source, new SearchRepository(null));

        $result = $service->search('matrix', [
            'type' => 'movie',
            'page' => 1,
            'per_page' => 20,
            'sort_by' => 'popularity.desc',
        ]);

        self::assertSame('Result for matrix', $result['results'][0]['title']);
        self::assertCount(1, $source->searchMovieRequests);
    }

    public function testSearchCacheKeysDoNotMixQueriesTypesOrPages(): void
    {
        $source = new StubContentSource();
        $mediaRepository = $this->createMock(MovieRepository::class);
        $cacheKeys = [];
        $mediaRepository->expects(self::exactly(4))
            ->method('getCachedResponse')
            ->willReturnCallback(static function (string $key) use (&$cacheKeys): ?array {
                $cacheKeys[] = $key;

                return null;
            });
        $mediaRepository->expects(self::exactly(4))->method('saveCachedResponse');
        $service = new SearchService($source, new SearchRepository($mediaRepository), $mediaRepository);

        foreach (
            [
                ['q' => 'matrix', 'type' => 'movie', 'page' => 1],
                ['q' => 'dune', 'type' => 'movie', 'page' => 1],
                ['q' => 'matrix', 'type' => 'tv', 'page' => 1],
                ['q' => 'matrix', 'type' => 'movie', 'page' => 2],
            ] as $request
        ) {
            $criteria = [
                'type' => $request['type'],
                'page' => $request['page'],
                'per_page' => 20,
                'sort_by' => 'popularity.desc',
            ];
            $service->search($request['q'], $criteria);
        }

        self::assertCount(4, array_unique($cacheKeys));
        foreach ($cacheKeys as $cacheKey) {
            self::assertStringStartsWith('cache:search-tmdb:', $cacheKey);
        }
    }

    public function testMovieYearSortIsMappedToTmdbReleaseDateSort(): void
    {
        $source = new StubContentSource();
        $service = new SearchService($source, new SearchRepository(null));

        $service->discover([
            'type' => 'movie',
            'page' => 1,
            'per_page' => 20,
            'sort_by' => 'year.desc',
        ]);

        self::assertSame('primary_release_date.desc', $source->lastDiscoverMovieParams['sort_by']);
    }

    public function testTvYearSortIsMappedToTmdbFirstAirDateSort(): void
    {
        $source = new StubContentSource();
        $service = new SearchService($source, new SearchRepository(null));

        $service->discover([
            'type' => 'tv',
            'page' => 1,
            'per_page' => 20,
            'sort_by' => 'year.asc',
        ]);

        self::assertSame('first_air_date.asc', $source->lastDiscoverTvParams['sort_by']);
    }

    public function testNonDateSortIsPassedThroughToTmdb(): void
    {
        $source = new StubContentSource();
        $service = new SearchService($source, new SearchRepository(null));

        $service->discover([
            'type' => 'movie',
            'page' => 1,
            'per_page' => 20,
            'sort_by' => 'vote_average.desc',
        ]);

        self::assertSame('vote_average.desc', $source->lastDiscoverMovieParams['sort_by']);
    }

    public function testExcludedGenresUseTmdbWithoutGenresParameter(): void
    {
        $source = new StubContentSource();
        $service = new SearchService($source, new SearchRepository(null));

        $service->discover([
            'type' => 'movie',
            'page' => 1,
            'per_page' => 20,
            'sort_by' => 'popularity.desc',
            'genre_ids' => [28, 12],
            'exclude_genre_ids' => [27, 10749],
        ]);

        self::assertSame('28,12', $source->lastDiscoverMovieParams['with_genres']);
        self::assertSame('27,10749', $source->lastDiscoverMovieParams['without_genres']);
    }

    public function testMovieExtendedFiltersUseMovieDateAndTmdbFilterNames(): void
    {
        $source = new StubContentSource();
        $service = new SearchService($source, new SearchRepository(null));

        $service->discover([
            'type' => 'movie',
            'page' => 1,
            'per_page' => 20,
            'sort_by' => 'popularity.desc',
            'date_from' => '2026-01-01',
            'date_to' => '2026-08-23',
            'vote_count.gte' => 200,
            'language' => 'en',
            'country' => 'US',
            'watch_region' => 'RU',
            'with_watch_providers' => [8, 119],
            'with_watch_monetization_types' => ['flatrate', 'free'],
        ]);

        self::assertSame('2026-01-01', $source->lastDiscoverMovieParams['primary_release_date.gte']);
        self::assertSame('2026-08-23', $source->lastDiscoverMovieParams['primary_release_date.lte']);
        self::assertSame(200, $source->lastDiscoverMovieParams['vote_count.gte']);
        self::assertSame('en', $source->lastDiscoverMovieParams['with_original_language']);
        self::assertSame('US', $source->lastDiscoverMovieParams['with_origin_country']);
        self::assertSame('RU', $source->lastDiscoverMovieParams['watch_region']);
        self::assertSame('8|119', $source->lastDiscoverMovieParams['with_watch_providers']);
        self::assertSame('flatrate|free', $source->lastDiscoverMovieParams['with_watch_monetization_types']);
    }

    public function testTvExtendedFiltersUseFirstAirDate(): void
    {
        $source = new StubContentSource();
        $service = new SearchService($source, new SearchRepository(null));

        $service->discover([
            'type' => 'tv',
            'page' => 1,
            'per_page' => 20,
            'sort_by' => 'popularity.desc',
            'date_from' => '2025-01-01',
            'date_to' => '2025-12-31',
        ]);

        self::assertSame('2025-01-01', $source->lastDiscoverTvParams['first_air_date.gte']);
        self::assertSame('2025-12-31', $source->lastDiscoverTvParams['first_air_date.lte']);
    }

    public function testYearRangeUsesCalendarBoundariesForMovieAndTv(): void
    {
        $source = new StubContentSource();
        $service = new SearchService($source, new SearchRepository(null));

        $service->discover([
            'type' => 'movie',
            'page' => 1,
            'per_page' => 20,
            'sort_by' => 'popularity.desc',
            'year_from' => 2020,
            'year_to' => 2022,
        ]);

        self::assertSame('2020-01-01', $source->lastDiscoverMovieParams['primary_release_date.gte']);
        self::assertSame('2022-12-31', $source->lastDiscoverMovieParams['primary_release_date.lte']);

        $service->discover([
            'type' => 'tv',
            'page' => 1,
            'per_page' => 20,
            'sort_by' => 'popularity.desc',
            'year_from' => 2020,
            'year_to' => 2022,
        ]);

        self::assertSame('2020-01-01', $source->lastDiscoverTvParams['first_air_date.gte']);
        self::assertSame('2022-12-31', $source->lastDiscoverTvParams['first_air_date.lte']);
    }
}
