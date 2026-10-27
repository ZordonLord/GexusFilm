<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Service\ContentSourceInterface;

/**
 * Мок-реализация ContentSourceInterface для unit-тестов.
 *
 * Возвращает предопределённые данные без обращения к внешним API.
 */
final class StubContentSource implements ContentSourceInterface
{
    /** @var array<string, mixed> */
    public array $lastDiscoverMovieParams = [];

    /** @var array<string, mixed> */
    public array $lastDiscoverTvParams = [];

    /** @var list<array<string, mixed>> */
    public array $discoverMovieRequests = [];

    /** @var array<int, array<string, mixed>> */
    public array $discoverMovieResponses = [];

    /** @var list<array{query: string, params: array<string, mixed>}> */
    public array $searchMovieRequests = [];

    /** @var array<int, array<string, mixed>> */
    public array $searchMovieResponses = [];

    /** @var list<array{query: string, params: array<string, mixed>}> */
    public array $searchTvRequests = [];

    /** @var array<int, array<string, mixed>> */
    public array $searchTvResponses = [];

    /** @var list<array{query: string, params: array<string, mixed>}> */
    public array $searchMultiRequests = [];

    /** @var array<int, array<string, mixed>> */
    public array $searchMultiResponses = [];

    /** @var list<array{query: string, params: array<string, mixed>}> */
    public array $searchPeopleRequests = [];

    /** @var array<int, array<string, mixed>> */
    public array $searchPeopleResponses = [];

    // ── Movies ──────────────────────────────────────────────

    public function getPopularMovies(): array
    {
        return $this->listResponse([['id' => 1, 'title' => 'Popular Movie']]);
    }

    public function getTopRatedMovies(): array
    {
        return $this->listResponse([['id' => 7, 'title' => 'Top Rated Movie']]);
    }

    public function getTrendingMoviesDay(): array
    {
        return $this->listResponse([['id' => 2, 'title' => 'Trending Movie']]);
    }

    public function getTrendingMoviesWeek(): array
    {
        return $this->listResponse([['id' => 8, 'title' => 'Weekly Trending Movie']]);
    }

    public function getNowPlayingMovies(): array
    {
        return $this->listResponse([['id' => 3, 'title' => 'Now Playing Movie']]);
    }

    public function getUpcomingMovies(): array
    {
        return $this->listResponse([['id' => 4, 'title' => 'Upcoming Movie']]);
    }

    public function getMovieGenres(): array
    {
        return ['genres' => [['id' => 28, 'name' => 'Action']]];
    }

    public function discoverMovies(array $params = []): array
    {
        $this->lastDiscoverMovieParams = $params;
        $this->discoverMovieRequests[] = $params;

        $page = (int) ($params['page'] ?? 1);

        return $this->discoverMovieResponses[$page] ?? [
            'page' => $page,
            'results' => [['id' => 5, 'title' => 'Discovered Movie']],
            'total_pages' => 1,
            'total_results' => 1,
        ];
    }

    public function getMovie(int $id): array
    {
        return [
            'id' => $id,
            'title' => 'Movie ' . $id,
            'genres' => [['id' => 28, 'name' => 'Action']],
            'runtime' => 120,
        ];
    }

    public function search(string $query, array $params = []): array
    {
        $this->searchMovieRequests[] = ['query' => $query, 'params' => $params];
        $page = (int) ($params['page'] ?? 1);

        return $this->searchMovieResponses[$page] ?? [
            'page' => $page,
            'results' => [['id' => 6, 'title' => 'Result for ' . $query]],
            'total_pages' => 1,
            'total_results' => 1,
        ];
    }

    public function searchMulti(string $query, array $params = []): array
    {
        $this->searchMultiRequests[] = ['query' => $query, 'params' => $params];
        $page = (int) ($params['page'] ?? 1);

        return $this->searchMultiResponses[$page] ?? [
            'page' => $page,
            'results' => [
                ['id' => 20, 'media_type' => 'movie', 'title' => 'Multi Movie'],
                ['id' => 21, 'media_type' => 'tv', 'name' => 'Multi TV'],
                ['id' => 22, 'media_type' => 'person', 'name' => 'Multi Person'],
            ],
            'total_pages' => 1,
            'total_results' => 3,
        ];
    }

    public function searchPeople(string $query, array $params = []): array
    {
        $this->searchPeopleRequests[] = ['query' => $query, 'params' => $params];
        $page = (int) ($params['page'] ?? 1);

        return $this->searchPeopleResponses[$page] ?? [
            'page' => $page,
            'results' => [['id' => 23, 'media_type' => 'person', 'name' => 'Person for ' . $query]],
            'total_pages' => 1,
            'total_results' => 1,
        ];
    }

    // ── TV Shows ────────────────────────────────────────────

    public function getTrendingTvDay(): array
    {
        return $this->listResponse([['id' => 10, 'name' => 'Trending TV']]);
    }

    public function getTrendingTvWeek(): array
    {
        return $this->listResponse([['id' => 16, 'name' => 'Weekly Trending TV']]);
    }

    public function getPopularTv(): array
    {
        return $this->listResponse([['id' => 11, 'name' => 'Popular TV']]);
    }

    public function getTopRatedTv(): array
    {
        return $this->listResponse([['id' => 17, 'name' => 'Top Rated TV']]);
    }

    public function getOnTheAirTv(): array
    {
        return $this->listResponse([['id' => 12, 'name' => 'On The Air TV']]);
    }

    public function getAiringTodayTv(): array
    {
        return $this->listResponse([['id' => 13, 'name' => 'Airing Today TV']]);
    }

    public function getTvGenres(): array
    {
        return ['genres' => [['id' => 18, 'name' => 'Drama']]];
    }

    public function getTv(int $id): array
    {
        return [
            'id' => $id,
            'name' => 'TV Show ' . $id,
            'genres' => [['id' => 18, 'name' => 'Drama']],
            'episode_run_time' => [45],
            'number_of_seasons' => 5,
        ];
    }

    public function getTvSeason(int $seriesId, int $seasonNumber): array
    {
        return [
            'id' => 12345,
            'season_number' => $seasonNumber,
            'name' => 'Season ' . $seasonNumber,
            'episodes' => [
                ['episode_number' => 1, 'name' => 'Episode 1'],
            ],
        ];
    }

    public function searchTv(string $query, array $params = []): array
    {
        $this->searchTvRequests[] = ['query' => $query, 'params' => $params];
        $page = (int) ($params['page'] ?? 1);

        return $this->searchTvResponses[$page] ?? [
            'page' => $page,
            'results' => [['id' => 14, 'name' => 'TV Result for ' . $query]],
            'total_pages' => 1,
            'total_results' => 1,
        ];
    }

    public function discoverTv(array $params = []): array
    {
        $this->lastDiscoverTvParams = $params;

        return $this->listResponse([['id' => 15, 'name' => 'Discovered TV']]);
    }

    // ── Helper ──────────────────────────────────────────────

    private function listResponse(array $results): array
    {
        return [
            'page' => 1,
            'results' => $results,
            'total_pages' => 1,
            'total_results' => count($results),
        ];
    }
}
