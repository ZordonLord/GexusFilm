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
