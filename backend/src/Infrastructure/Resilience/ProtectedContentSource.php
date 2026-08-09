<?php

declare(strict_types=1);

namespace App\Infrastructure\Resilience;

use App\Exception\UpstreamException;
use App\Service\ContentSourceInterface;
use Closure;
use Throwable;

final class ProtectedContentSource implements ContentSourceInterface
{
    public function __construct(
        private ContentSourceInterface $source,
        private FileSingleflight $singleflight,
        private FileRequestLimiter $requestLimiter,
        private CircuitBreaker $circuitBreaker,
    ) {
    }

    public function getPopularMovies(): array
    {
        return $this->run(__FUNCTION__, [], fn (): array => $this->source->getPopularMovies());
    }

    public function getTrendingMoviesDay(): array
    {
        return $this->run(__FUNCTION__, [], fn (): array => $this->source->getTrendingMoviesDay());
    }

    public function getNowPlayingMovies(): array
    {
        return $this->run(__FUNCTION__, [], fn (): array => $this->source->getNowPlayingMovies());
    }

    public function getUpcomingMovies(): array
    {
        return $this->run(__FUNCTION__, [], fn (): array => $this->source->getUpcomingMovies());
    }

    public function getMovieGenres(): array
    {
        return $this->run(__FUNCTION__, [], fn (): array => $this->source->getMovieGenres());
    }

    public function discoverMovies(array $params = []): array
    {
        return $this->run(__FUNCTION__, $params, fn (): array => $this->source->discoverMovies($params));
    }

    public function getMovie(int $id): array
    {
        return $this->run(__FUNCTION__, [$id], fn (): array => $this->source->getMovie($id));
    }

    public function search(string $query, array $params = []): array
    {
        return $this->run(__FUNCTION__, [$query, $params], fn (): array => $this->source->search($query, $params));
    }

    public function getTrendingTvDay(): array
    {
        return $this->run(__FUNCTION__, [], fn (): array => $this->source->getTrendingTvDay());
    }

    public function getPopularTv(): array
    {
        return $this->run(__FUNCTION__, [], fn (): array => $this->source->getPopularTv());
    }

    public function getOnTheAirTv(): array
    {
        return $this->run(__FUNCTION__, [], fn (): array => $this->source->getOnTheAirTv());
    }

    public function getAiringTodayTv(): array
    {
        return $this->run(__FUNCTION__, [], fn (): array => $this->source->getAiringTodayTv());
    }

    public function getTvGenres(): array
    {
        return $this->run(__FUNCTION__, [], fn (): array => $this->source->getTvGenres());
    }

    public function getTv(int $id): array
    {
        return $this->run(__FUNCTION__, [$id], fn (): array => $this->source->getTv($id));
    }

    public function getTvSeason(int $seriesId, int $seasonNumber): array
    {
        return $this->run(
            __FUNCTION__,
            [$seriesId, $seasonNumber],
            fn (): array => $this->source->getTvSeason($seriesId, $seasonNumber),
        );
    }

    public function searchTv(string $query, array $params = []): array
    {
        return $this->run(__FUNCTION__, [$query, $params], fn (): array => $this->source->searchTv($query, $params));
    }

    public function discoverTv(array $params = []): array
    {
        return $this->run(__FUNCTION__, $params, fn (): array => $this->source->discoverTv($params));
    }

    /** @param array<int|string, mixed> $arguments */
    private function run(string $operation, array $arguments, Closure $callback): array
    {
        $key = $operation . ':' . hash('sha256', json_encode($arguments, JSON_THROW_ON_ERROR));

        return $this->singleflight->run($key, function () use ($callback): array {
            $this->circuitBreaker->allow();
            $release = $this->requestLimiter->acquire();

            try {
                $result = $callback();
                $this->circuitBreaker->recordSuccess();

                return $result;
            } catch (Throwable $exception) {
                if ($exception instanceof UpstreamException) {
                    $this->circuitBreaker->recordFailure();
                }

                throw $exception;
            } finally {
                $release();
            }
        });
    }
}
