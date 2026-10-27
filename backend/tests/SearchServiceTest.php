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

    public function testDiscoverUsesTmdbTotalPagesToLimitCatalogPagination(): void
    {
        $source = new StubContentSource();
        $source->discoverMovieResponses[2] = [
            'page' => 2,
            'results' => [['id' => 21, 'title' => 'Last page movie']],
            'total_pages' => 2,
            'total_results' => 1000,
        ];
        $mediaRepository = $this->createMock(MovieRepository::class);
        $mediaRepository->method('getCachedResponse')->willReturn(null);
        $mediaRepository->method('saveCachedResponse');
        $service = new SearchService($source, new SearchRepository($mediaRepository), $mediaRepository);

        $result = $service->discover([
            'type' => 'movie',
            'page' => 2,
            'per_page' => 20,
            'sort_by' => 'popularity.desc',
        ]);

        self::assertSame(2, $result['total_pages']);
        self::assertSame('Last page movie', $result['results'][0]['title']);
    }

    public function testDiscoverMapsTmdbTotalPagesToRequestedPageSize(): void
    {
        $source = new StubContentSource();
        foreach ([1, 2] as $upstreamPage) {
            $source->discoverMovieResponses[$upstreamPage] = [
                'page' => $upstreamPage,
                'results' => array_map(
                    static fn (int $id): array => ['id' => (($upstreamPage - 1) * 20) + $id],
                    range(1, 20),
                ),
                'total_pages' => 7,
                'total_results' => 1000,
            ];
        }
        $mediaRepository = $this->createMock(MovieRepository::class);
        $mediaRepository->method('getCachedResponse')->willReturn(null);
        $mediaRepository->method('saveCachedResponse');
        $service = new SearchService($source, new SearchRepository($mediaRepository), $mediaRepository);

        $result = $service->discover([
            'type' => 'movie',
            'page' => 1,
            'per_page' => 40,
            'sort_by' => 'popularity.desc',
        ]);

        self::assertSame(4, $result['total_pages']);
    }

    public function testDiscoverCapsTmdbPaginationAtFiveHundredPages(): void
    {
        foreach (
            [
                20 => ['page' => 500, 'upstream_pages' => [500]],
                40 => ['page' => 250, 'upstream_pages' => [499, 500]],
                80 => ['page' => 125, 'upstream_pages' => [497, 498, 499, 500]],
            ] as $perPage => $expected
        ) {
            $source = new StubContentSource();
            foreach ($expected['upstream_pages'] as $upstreamPage) {
                $source->discoverMovieResponses[$upstreamPage] = [
                    'page' => $upstreamPage,
                    'results' => array_map(
                        static fn (int $item): array => ['id' => (($upstreamPage - 1) * 20) + $item],
                        range(1, 20),
                    ),
                    'total_pages' => 1001,
                    'total_results' => 20001,
                ];
            }
            $mediaRepository = $this->createMock(MovieRepository::class);
            $mediaRepository->method('getCachedResponse')->willReturn(null);
            $mediaRepository->method('saveCachedResponse');
            $service = new SearchService($source, new SearchRepository($mediaRepository), $mediaRepository);

            $result = $service->discover([
                'type' => 'movie',
                'page' => $expected['page'],
                'per_page' => $perPage,
                'sort_by' => 'popularity.desc',
            ]);

            self::assertSame((int) ceil(10000 / $perPage), $result['total_pages']);
            self::assertSame($expected['upstream_pages'], array_column($source->discoverMovieRequests, 'page'));
            self::assertCount($perPage, $result['results']);
        }
    }

    public function testDiscoverClampsRequestedPageBeyondTmdbLimit(): void
    {
        $source = new StubContentSource();
        $source->discoverMovieResponses[500] = [
            'page' => 500,
            'results' => [['id' => 10000, 'title' => 'Last supported page']],
            'total_pages' => 1001,
            'total_results' => 20001,
        ];
        $mediaRepository = $this->createMock(MovieRepository::class);
        $mediaRepository->method('getCachedResponse')->willReturn(null);
        $mediaRepository->method('saveCachedResponse');
        $service = new SearchService($source, new SearchRepository($mediaRepository), $mediaRepository);

        $result = $service->discover([
            'type' => 'movie',
            'page' => 501,
            'per_page' => 20,
            'sort_by' => 'popularity.desc',
        ]);

        self::assertSame(500, $result['page']);
        self::assertSame(500, $source->lastDiscoverMovieParams['page']);
        self::assertSame('Last supported page', $result['results'][0]['title']);
    }

    public function testDiscoverFetchesWholeTmdbPagesForMultiplesOfTwenty(): void
    {
        foreach ([40 => [5, 6], 80 => [9, 10, 11, 12]] as $perPage => $expectedPages) {
            $source = new StubContentSource();
            foreach (range(1, 20) as $upstreamPage) {
                $source->discoverMovieResponses[$upstreamPage] = [
                    'page' => $upstreamPage,
                    'results' => array_map(
                        static fn (int $item): array => ['id' => (($upstreamPage - 1) * 20) + $item],
                        range(1, 20),
                    ),
                    'total_pages' => 20,
                    'total_results' => 400,
                ];
            }
            $mediaRepository = $this->createMock(MovieRepository::class);
            $mediaRepository->method('getCachedResponse')->willReturn(null);
            $mediaRepository->method('saveCachedResponse');
            $service = new SearchService($source, new SearchRepository($mediaRepository), $mediaRepository);

            $result = $service->discover([
                'type' => 'movie',
                'page' => 3,
                'per_page' => $perPage,
                'sort_by' => 'popularity.desc',
            ]);

            self::assertSame($expectedPages, array_column($source->discoverMovieRequests, 'page'));
            self::assertCount($perPage, $result['results']);
            self::assertSame((3 - 1) * $perPage + 1, $result['results'][0]['id']);
            self::assertSame(3, $result['page']);
        }
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
            self::assertStringStartsWith('cache:discover-tmdb-page-cap-v2:', $cacheKey);
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
            self::assertStringStartsWith('cache:search-tmdb-page-cap-v2:', $cacheKey);
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
