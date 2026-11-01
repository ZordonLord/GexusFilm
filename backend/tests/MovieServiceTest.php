<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\MovieService;
use App\Tests\Support\StubContentSource;
use PHPUnit\Framework\TestCase;

final class MovieServiceTest extends TestCase
{
    private MovieService $service;

    protected function setUp(): void
    {
        $this->service = new MovieService(new StubContentSource(), null);
    }

    public function testGetPopularMoviesReturnsResultsFromSource(): void
    {
        $data = $this->service->getPopularMovies();

        self::assertSame(1, $data['total_results']);
        self::assertSame('Popular Movie', $data['results'][0]['title']);
    }

    public function testGetTopRatedMoviesReturnsResultsFromSource(): void
    {
        $data = $this->service->getTopRatedMovies();

        self::assertSame('Top Rated Movie', $data['results'][0]['title']);
    }

    public function testGetTrendingMoviesReturnsResultsFromSource(): void
    {
        $data = $this->service->getTrendingMovies();

        self::assertSame('Trending Movie', $data['results'][0]['title']);
    }

    public function testGetTrendingMoviesWeekReturnsResultsFromSource(): void
    {
        $data = $this->service->getTrendingMoviesWeek();

        self::assertSame('Weekly Trending Movie', $data['results'][0]['title']);
    }

    public function testGetNowPlayingMoviesReturnsResultsFromSource(): void
    {
        $data = $this->service->getNowPlayingMovies();

        self::assertSame('Now Playing Movie', $data['results'][0]['title']);
    }

    public function testGetUpcomingMoviesReturnsResultsFromSource(): void
    {
        $data = $this->service->getUpcomingMovies();

        self::assertSame('Upcoming Movie', $data['results'][0]['title']);
    }

    public function testGetNewMoviesReturnsDiscoverResultsFromSource(): void
    {
        $data = $this->service->getNewMovies();

        self::assertSame('Discovered Movie', $data['results'][0]['title']);
    }

    public function testGetMovieDetailsReturnsDataFromSource(): void
    {
        $movie = $this->service->getMovieDetails(42);

        self::assertSame(42, $movie['id']);
        self::assertSame('Movie 42', $movie['title']);
        self::assertSame(120, $movie['runtime']);
        self::assertArrayHasKey('credits', $movie);
        self::assertArrayHasKey('watch/providers', $movie);
        self::assertSame('Movie 42 English Title', $movie['alternative_titles']['titles'][0]['title']);
        self::assertSame('Movie 42 English Translation', $movie['translations']['translations'][0]['data']['title']);
    }

    public function testGetMovieCollectionReturnsCollectionPartsFromSource(): void
    {
        $collection = $this->service->getMovieCollection(900);

        self::assertSame(900, $collection['id']);
        self::assertCount(2, $collection['parts']);
        self::assertSame(43, $collection['parts'][1]['id']);
    }

    public function testGetMovieCollectionUsesCachedResponse(): void
    {
        $collection = ['id' => 900, 'name' => 'Cached collection', 'parts' => []];
        $repository = $this->createMock(\App\Repository\MovieRepository::class);
        $repository->expects(self::once())
            ->method('getCachedResponse')
            ->with('cache:collection:movie:900')
            ->willReturn($collection);
        $repository->expects(self::never())->method('saveCachedResponse');
        $source = new StubContentSource();
        $service = new MovieService($source, $repository);

        self::assertSame($collection, $service->getMovieCollection(900));
    }

    public function testGetMovieDetailsRefreshesCachedPayloadWithoutExpandedSections(): void
    {
        $source = new StubContentSource();
        $repository = $this->createMock(\App\Repository\MovieRepository::class);
        $repository->method('getCachedResponse')->willReturn(['id' => 42, 'title' => 'Old cache']);
        $repository->method('getMovieDetails')->willReturn(['id' => 42, 'title' => 'Old snapshot']);
        $repository->expects(self::once())
            ->method('saveMovieDetails')
            ->with(self::callback(static fn (array $snapshot): bool => isset($snapshot['credits'], $snapshot['videos'])));
        $repository->expects(self::once())->method('saveCachedResponse');
        $service = new MovieService($source, $repository);

        $movie = $service->getMovieDetails(42);

        self::assertSame('Movie 42', $movie['title']);
        self::assertSame([42], $source->movieDetailsRequests);
        self::assertArrayHasKey('credits', $movie);
    }

    public function testGetMovieDetailsReusesCompleteCachedPayload(): void
    {
        $source = new StubContentSource();
        $movie = [
            'id' => 42,
            'title' => 'Cached movie',
            'credits' => ['cast' => [], 'crew' => []],
            'videos' => ['results' => []],
            'images' => ['backdrops' => [], 'posters' => [], 'logos' => []],
            'recommendations' => ['results' => []],
            'release_dates' => ['results' => []],
            'watch/providers' => ['results' => []],
            'alternative_titles' => ['titles' => []],
            'translations' => ['translations' => []],
        ];
        $repository = $this->createMock(\App\Repository\MovieRepository::class);
        $repository->method('getCachedResponse')->willReturn($movie);
        $repository->expects(self::never())->method('getMovieDetails');
        $service = new MovieService($source, $repository);

        self::assertSame($movie, $service->getMovieDetails(42));
        self::assertSame([], $source->movieDetailsRequests);
    }

    public function testSearchMoviesReturnsResultsFromSource(): void
    {
        $result = $this->service->searchMovies('Inception');

        self::assertSame('Result for Inception', $result['results'][0]['title']);
    }

    public function testDiscoverMoviesReturnsResultsFromSource(): void
    {
        $data = $this->service->discoverMovies(['genre_id' => 28]);

        self::assertSame('Discovered Movie', $data['results'][0]['title']);
    }

    public function testGetGenresReturnsDataFromSource(): void
    {
        $data = $this->service->getGenres();

        self::assertSame('Action', $data['genres'][0]['name']);
    }
}
