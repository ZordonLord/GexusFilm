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
