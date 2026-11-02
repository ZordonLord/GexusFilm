<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\TvService;
use App\Tests\Support\StubContentSource;
use PHPUnit\Framework\TestCase;

final class TvServiceTest extends TestCase
{
    private TvService $service;

    protected function setUp(): void
    {
        $this->service = new TvService(new StubContentSource(), null);
    }

    public function testGetTrendingTvReturnsResultsFromSource(): void
    {
        $data = $this->service->getTrendingTv();

        self::assertSame('Trending TV', $data['results'][0]['name']);
    }

    public function testGetTrendingTvWeekReturnsResultsFromSource(): void
    {
        $data = $this->service->getTrendingTvWeek();

        self::assertSame('Weekly Trending TV', $data['results'][0]['name']);
    }

    public function testGetPopularTvReturnsResultsFromSource(): void
    {
        $data = $this->service->getPopularTv();

        self::assertSame(1, $data['total_results']);
        self::assertSame('Popular TV', $data['results'][0]['name']);
    }

    public function testGetTopRatedTvReturnsResultsFromSource(): void
    {
        $data = $this->service->getTopRatedTv();

        self::assertSame('Top Rated TV', $data['results'][0]['name']);
    }

    public function testGetNewTvReturnsDiscoverResultsFromSource(): void
    {
        $data = $this->service->getNewTv();

        self::assertSame('Discovered TV', $data['results'][0]['name']);
    }

    public function testGetOnTheAirTvReturnsResultsFromSource(): void
    {
        $data = $this->service->getOnTheAirTv();

        self::assertSame('On The Air TV', $data['results'][0]['name']);
    }

    public function testGetAiringTodayTvReturnsResultsFromSource(): void
    {
        $data = $this->service->getAiringTodayTv();

        self::assertSame('Airing Today TV', $data['results'][0]['name']);
    }

    public function testGetTvDetailsReturnsDataFromSource(): void
    {
        $tv = $this->service->getTvDetails(42);

        self::assertSame(42, $tv['id']);
        self::assertSame('TV Show 42', $tv['name']);
        self::assertSame(5, $tv['number_of_seasons']);
        self::assertArrayHasKey('credits', $tv);
        self::assertArrayHasKey('watch/providers', $tv);
        self::assertArrayHasKey('recommendations', $tv);
    }

    public function testGetTvDetailsRefreshesCachedPayloadWithoutExpandedSections(): void
    {
        $source = new StubContentSource();
        $repository = $this->createMock(\App\Repository\MovieRepository::class);
        $repository->method('getCachedResponse')->willReturn(['id' => 42, 'name' => 'Old cache']);
        $repository->method('getTvDetails')->willReturn(['id' => 42, 'name' => 'Old snapshot']);
        $repository->expects(self::once())
            ->method('saveTvDetails')
            ->with(self::callback(static fn (array $snapshot): bool => isset($snapshot['credits'], $snapshot['videos'], $snapshot['watch/providers'])));
        $repository->expects(self::once())->method('saveCachedResponse');
        $service = new TvService($source, $repository);

        $tv = $service->getTvDetails(42);

        self::assertSame('TV Show 42', $tv['name']);
        self::assertArrayHasKey('credits', $tv);
    }

    public function testGetTvDetailsReusesCompleteCachedPayload(): void
    {
        $source = new StubContentSource();
        $tv = [
            'id' => 42,
            'name' => 'Cached TV show',
            'credits' => ['cast' => [], 'crew' => []],
            'videos' => ['results' => []],
            'images' => ['backdrops' => [], 'posters' => [], 'logos' => []],
            'recommendations' => ['results' => []],
            'watch/providers' => ['results' => []],
            'alternative_titles' => ['results' => []],
            'translations' => ['translations' => []],
            'content_ratings' => ['results' => []],
        ];
        $repository = $this->createMock(\App\Repository\MovieRepository::class);
        $repository->method('getCachedResponse')->willReturn($tv);
        $repository->expects(self::never())->method('getTvDetails');
        $service = new TvService($source, $repository);

        self::assertSame($tv, $service->getTvDetails(42));
    }

    public function testGetTvSeasonReturnsDataFromSource(): void
    {
        $data = $this->service->getTvSeason(100, 2);

        self::assertSame(2, $data['season_number']);
        self::assertSame('Season 2', $data['name']);
        self::assertCount(1, $data['episodes']);
    }

    public function testSearchTvReturnsResultsFromSource(): void
    {
        $result = $this->service->searchTv('Breaking Bad');

        self::assertSame('TV Result for Breaking Bad', $result['results'][0]['name']);
    }

    public function testDiscoverTvReturnsResultsFromSource(): void
    {
        $data = $this->service->discoverTv(['genre_id' => 18]);

        self::assertSame('Discovered TV', $data['results'][0]['name']);
    }

    public function testGetGenresReturnsDataFromSource(): void
    {
        $data = $this->service->getGenres();

        self::assertSame('Drama', $data['genres'][0]['name']);
    }
}
