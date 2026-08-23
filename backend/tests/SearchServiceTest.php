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
}
