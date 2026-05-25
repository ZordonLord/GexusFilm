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

        $response = file_get_contents($url);

        if (!$response) {
            throw new Exception("TMDb request failed");
        }

        return json_decode($response, true);
    }

    public function getPopularMovies()
    {
        return $this->request('/movie/popular');
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