<?php

declare(strict_types=1);

namespace App;

use App\Service\ContentSourceInterface;
use Exception;

class TmdbClient implements ContentSourceInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://api.themoviedb.org/3';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    private function request(string $endpoint, array $params = [])
    {
        $params['api_key'] = $this->apiKey;
        $params['language'] = 'ru-RU';

        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);

        return json_decode($response, true);
    }

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
}
