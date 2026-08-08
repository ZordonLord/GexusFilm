<?php

declare(strict_types=1);

namespace App;

use App\Service\ContentSourceInterface;
use App\Exception\UpstreamException;
use App\Exception\UpstreamTimeoutException;
use JsonException;

class TmdbClient implements ContentSourceInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://api.themoviedb.org/3';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    private function request(string $endpoint, array $params = []): array
    {
        $params['api_key'] = $this->apiKey;
        $params['language'] = 'ru-RU';

        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            // API-ключ находится в query string, поэтому нельзя передавать его на внешний redirect.
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $errorCode = curl_errno($ch);
            curl_close($ch);

            if (in_array($errorCode, [CURLE_OPERATION_TIMEDOUT, CURLE_COULDNT_CONNECT], true)) {
                throw new UpstreamTimeoutException('TMDB request timed out.');
            }

            throw new UpstreamException('TMDB request failed.');
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 408 || $httpCode === 504) {
            throw new UpstreamTimeoutException('TMDB request timed out.');
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new UpstreamException('TMDB returned an upstream error.');
        }

        try {
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UpstreamException('TMDB returned invalid data.', 0, $exception);
        }

        if (!is_array($data)) {
            throw new UpstreamException('TMDB response has an invalid structure.');
        }

        return $data;
    }

    // -- Movies --

    public function getPopularMovies(): array
    {
        return $this->request('/movie/popular');
    }

    public function getTrendingMoviesDay(): array
    {
        return $this->request('/trending/movie/day');
    }

    public function getNowPlayingMovies(): array
    {
        return $this->request('/movie/now_playing');
    }

    public function getUpcomingMovies(): array
    {
        return $this->request('/movie/upcoming');
    }

    public function getMovieGenres(): array
    {
        return $this->request('/genre/movie/list');
    }

    public function discoverMovies(array $params = []): array
    {
        return $this->request('/discover/movie', $params);
    }

    public function getMovie(int $id): array
    {
        return $this->request("/movie/$id");
    }

    public function search(string $query): array
    {
        return $this->request('/search/movie', [
            'query' => $query
        ]);
    }

    // -- TV Shows --

    public function getTrendingTvDay(): array
    {
        return $this->request('/trending/tv/day');
    }

    public function getPopularTv(): array
    {
        return $this->request('/tv/popular');
    }

    public function getOnTheAirTv(): array
    {
        return $this->request('/tv/on_the_air');
    }

    public function getAiringTodayTv(): array
    {
        return $this->request('/tv/airing_today');
    }

    public function getTvGenres(): array
    {
        return $this->request('/genre/tv/list');
    }

    public function getTv(int $id): array
    {
        return $this->request("/tv/$id");
    }

    public function getTvSeason(int $seriesId, int $seasonNumber): array
    {
        return $this->request("/tv/$seriesId/season/$seasonNumber");
    }

    public function searchTv(string $query): array
    {
        return $this->request('/search/tv', [
            'query' => $query
        ]);
    }

    public function discoverTv(array $params = []): array
    {
        return $this->request('/discover/tv', $params);
    }
}
