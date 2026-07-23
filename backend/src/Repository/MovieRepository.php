<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

class MovieRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function getCachedResponse(string $cacheKey): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT response FROM api_cache WHERE cache_key = :cache_key AND expires_at > NOW()'
        );
        $statement->execute(['cache_key' => $cacheKey]);

        $response = $statement->fetchColumn();

        return $response ? json_decode($response, true) : null;
    }

    public function saveCachedResponse(string $cacheKey, array $response, int $ttlMinutes): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO api_cache (cache_key, response, expires_at)
             VALUES (:cache_key, CAST(:response AS jsonb), NOW() + (CAST(:ttl_minutes AS integer) * INTERVAL \'1 minute\'))
             ON CONFLICT (cache_key)
             DO UPDATE SET
                response = EXCLUDED.response,
                expires_at = EXCLUDED.expires_at,
                updated_at = NOW()'
        );

        $statement->execute([
            'cache_key' => $cacheKey,
            'response' => json_encode($response, JSON_UNESCAPED_UNICODE),
            'ttl_minutes' => $ttlMinutes,
        ]);
    }

    public function getMovieDetails(int $tmdbId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT tmdb_payload FROM movies
             WHERE tmdb_id = :tmdb_id AND has_details = TRUE'
        );
        $statement->execute(['tmdb_id' => $tmdbId]);

        $movie = $statement->fetchColumn();

        return $movie ? json_decode($movie, true) : null;
    }

    public function saveMovieSummary(array $movie): void
    {
        if (!isset($movie['id'])) {
            return;
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO movies (
                tmdb_id, title, original_title, overview, poster_path, backdrop_path,
                release_date, vote_average, popularity, genre_ids, tmdb_payload
             )
             VALUES (
                :tmdb_id, :title, :original_title, :overview, :poster_path, :backdrop_path,
                :release_date, :vote_average, :popularity, CAST(:genre_ids AS jsonb), CAST(:tmdb_payload AS jsonb)
             )
             ON CONFLICT (tmdb_id)
             DO UPDATE SET
                title = EXCLUDED.title,
                original_title = EXCLUDED.original_title,
                overview = EXCLUDED.overview,
                poster_path = EXCLUDED.poster_path,
                backdrop_path = EXCLUDED.backdrop_path,
                release_date = EXCLUDED.release_date,
                vote_average = EXCLUDED.vote_average,
                popularity = EXCLUDED.popularity,
                genre_ids = EXCLUDED.genre_ids,
                tmdb_payload = CASE
                    WHEN movies.has_details THEN movies.tmdb_payload
                    ELSE EXCLUDED.tmdb_payload
                END,
                updated_at = NOW()'
        );

        $statement->execute($this->movieSummaryParams($movie));
    }

    public function saveMovieDetails(array $movie): void
    {
        if (!isset($movie['id'])) {
            return;
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO movies (
                tmdb_id, title, original_title, overview, poster_path, backdrop_path,
                release_date, runtime, vote_average, popularity, genres, tmdb_payload, has_details
             )
             VALUES (
                :tmdb_id, :title, :original_title, :overview, :poster_path, :backdrop_path,
                :release_date, :runtime, :vote_average, :popularity, CAST(:genres AS jsonb), CAST(:tmdb_payload AS jsonb), TRUE
             )
             ON CONFLICT (tmdb_id)
             DO UPDATE SET
                title = EXCLUDED.title,
                original_title = EXCLUDED.original_title,
                overview = EXCLUDED.overview,
                poster_path = EXCLUDED.poster_path,
                backdrop_path = EXCLUDED.backdrop_path,
                release_date = EXCLUDED.release_date,
                runtime = EXCLUDED.runtime,
                vote_average = EXCLUDED.vote_average,
                popularity = EXCLUDED.popularity,
                genres = EXCLUDED.genres,
                tmdb_payload = EXCLUDED.tmdb_payload,
                has_details = TRUE,
                updated_at = NOW()'
        );

        $statement->execute($this->movieDetailsParams($movie));
    }

    public function saveMovieSummaries(array $movies): void
    {
        foreach ($movies as $movie) {
            $this->saveMovieSummary($movie);
        }
    }

    private function movieSummaryParams(array $movie): array
    {
        $params = $this->movieBaseParams($movie);
        $params['genre_ids'] = json_encode($movie['genre_ids'] ?? [], JSON_UNESCAPED_UNICODE);

        return $params;
    }

    private function movieDetailsParams(array $movie): array
    {
        $params = $this->movieBaseParams($movie);
        $params['runtime'] = $movie['runtime'] ?? null;
        $params['genres'] = json_encode($movie['genres'] ?? [], JSON_UNESCAPED_UNICODE);

        return $params;
    }

    private function movieBaseParams(array $movie): array
    {
        $releaseDate = $movie['release_date'] ?? null;

        if ($releaseDate === '') {
            $releaseDate = null;
        }

        return [
            'tmdb_id' => (int) $movie['id'],
            'title' => $movie['title'] ?? null,
            'original_title' => $movie['original_title'] ?? null,
            'overview' => $movie['overview'] ?? null,
            'poster_path' => $movie['poster_path'] ?? null,
            'backdrop_path' => $movie['backdrop_path'] ?? null,
            'release_date' => $releaseDate,
            'vote_average' => $movie['vote_average'] ?? null,
            'popularity' => $movie['popularity'] ?? null,
            'tmdb_payload' => json_encode($movie, JSON_UNESCAPED_UNICODE),
        ];
    }
}
