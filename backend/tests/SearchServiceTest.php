<?php

declare(strict_types=1);

namespace App\Tests;

use App\Repository\SearchRepository;
use App\Service\SearchService;
use App\Tests\Support\StubContentSource;
use PHPUnit\Framework\TestCase;

final class SearchServiceTest extends TestCase
{
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
