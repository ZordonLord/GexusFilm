<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Service\TvService;

class TvController
{
    public function __construct(private TvService $tvService)
    {
    }

    public function getTrending(): array
    {
        return $this->tvService->getTrendingTv();
    }

    public function getTrendingWeek(): array
    {
        return $this->tvService->getTrendingTvWeek();
    }

    public function getPopular(): array
    {
        return $this->tvService->getPopularTv();
    }

    public function getTopRated(): array
    {
        return $this->tvService->getTopRatedTv();
    }

    public function getNew(): array
    {
        return $this->tvService->getNewTv();
    }

    public function getOnTheAir(): array
    {
        return $this->tvService->getOnTheAirTv();
    }

    public function getAiringToday(): array
    {
        return $this->tvService->getAiringTodayTv();
    }

    public function getTv(int $id): array
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('TV show ID must be positive');
        }

        return $this->tvService->getTvDetails($id);
    }

    public function getSeason(int $seriesId, int $seasonNumber): array
    {
        if ($seriesId <= 0) {
            throw new \InvalidArgumentException('Series ID must be positive');
        }

        if ($seasonNumber <= 0) {
            throw new \InvalidArgumentException('Season number must be positive');
        }

        return $this->tvService->getTvSeason($seriesId, $seasonNumber);
    }

    public function search(string $query): array
    {
        $query = trim($query);

        if (empty($query)) {
            throw new \InvalidArgumentException('Query cannot be empty');
        }

        return $this->tvService->searchTv($query);
    }

    public function discover(array $params): array
    {
        return $this->tvService->discoverTv($params);
    }

    public function getGenres(): array
    {
        return $this->tvService->getGenres();
    }
}
