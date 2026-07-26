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
    // ── Movies ──────────────────────────────────────────────

    public function getPopularMovies(): array
    {
        return $this->listResponse([['id' => 1, 'title' => 'Popular Movie']]);
    }

    public function getTrendingMoviesDay(): array
    {
        return $this->listResponse([['id' => 2, 'title' => 'Trending Movie']]);
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
        return $this->listResponse([['id' => 5, 'title' => 'Discovered Movie']]);
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

    public function search(string $query): array
    {
        return $this->listResponse([['id' => 6, 'title' => 'Result for ' . $query]]);
    }

    // ── TV Shows ────────────────────────────────────────────

    public function getTrendingTvDay(): array
    {
        return $this->listResponse([['id' => 10, 'name' => 'Trending TV']]);
    }

    public function getPopularTv(): array
    {
        return $this->listResponse([['id' => 11, 'name' => 'Popular TV']]);
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

    public function searchTv(string $query): array
    {
        return $this->listResponse([['id' => 14, 'name' => 'TV Result for ' . $query]]);
    }

    public function discoverTv(array $params = []): array
    {
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
