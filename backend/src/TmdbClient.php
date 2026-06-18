<?php

class TmdbClient
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
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);

        return json_decode($response, true);
    }

    public function getPopularMovies()
    {
        return $this->request('/movie/popular');
    }

    public function getTrendingMoviesDay()
    {
        return $this->request('/trending/movie/day');
    }

    public function getNowPlayingMovies()
    {
        return $this->request('/movie/now_playing');
    }

    public function getUpcomingMovies()
    {
        return $this->request('/movie/upcoming');
    }

    public function getMovieGenres()
    {
        return $this->request('/genre/movie/list');
    }

    public function discoverMovies(array $params = [])
    {
        return $this->request('/discover/movie', $params);
    }

    public function getMovie(int $id)
    {
        return $this->request("/movie/$id");
    }

    public function search(string $query)
    {
        return $this->request('/search/movie', [
            'query' => $query
        ]);
    }
}