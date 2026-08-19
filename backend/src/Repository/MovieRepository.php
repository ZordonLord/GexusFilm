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

    // ── Movie details ────────────────────────────────────────

    public function getMovieDetails(int $tmdbId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT tmdb_payload FROM movies
             WHERE tmdb_id = :tmdb_id AND media_type = \'movie\' AND has_details = TRUE'
        );
        $statement->execute(['tmdb_id' => $tmdbId]);

        $movie = $statement->fetchColumn();

        return $movie ? json_decode($movie, true) : null;
    }

    /** @return array<string, mixed>|null */
    public function saveMovieSummary(array $movie): ?array
    {
        if (!isset($movie['id'])) {
            return null;
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO movies (
                tmdb_id, media_type, title, original_title, overview, poster_path, backdrop_path,
                release_date, vote_average, popularity, genre_ids, tmdb_payload
             )
             VALUES (
                :tmdb_id, :media_type, :title, :original_title, :overview, :poster_path, :backdrop_path,
                :release_date, :vote_average, :popularity, CAST(:genre_ids AS jsonb), CAST(:tmdb_payload AS jsonb)
             )
             ON CONFLICT (tmdb_id, media_type)
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
                 updated_at = NOW()
             RETURNING id'
        );

        $statement->execute($this->movieSummaryParams($movie));

        return $this->getMediaRecordById((int) $statement->fetchColumn());
    }

    /** @return array<string, mixed>|null */
    public function saveMovieDetails(array $movie): ?array
    {
        if (!isset($movie['id'])) {
            return null;
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO movies (
                tmdb_id, media_type, title, original_title, overview, poster_path, backdrop_path,
                release_date, runtime, vote_average, popularity, genres, tmdb_payload, has_details
             )
             VALUES (
                :tmdb_id, :media_type, :title, :original_title, :overview, :poster_path, :backdrop_path,
                :release_date, :runtime, :vote_average, :popularity, CAST(:genres AS jsonb), CAST(:tmdb_payload AS jsonb), TRUE
             )
             ON CONFLICT (tmdb_id, media_type)
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
                 updated_at = NOW()
             RETURNING id'
        );

        $statement->execute($this->movieDetailsParams($movie));

        return $this->getMediaRecordById((int) $statement->fetchColumn());
    }

    /** @return list<array<string, mixed>> */
    public function saveMovieSummaries(array $movies): array
    {
        $saved = [];

        foreach ($movies as $movie) {
            $record = $this->saveMovieSummary($movie);

            if ($record !== null) {
                $saved[] = $record;
            }
        }

        return $saved;
    }

    // ── TV details ───────────────────────────────────────────

    public function getTvDetails(int $tmdbId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT tmdb_payload FROM movies
             WHERE tmdb_id = :tmdb_id AND media_type = \'tv\' AND has_details = TRUE'
        );
        $statement->execute(['tmdb_id' => $tmdbId]);

        $tv = $statement->fetchColumn();

        return $tv ? json_decode($tv, true) : null;
    }

    /** @return array<string, mixed>|null */
    public function saveTvSummary(array $tv): ?array
    {
        if (!isset($tv['id'])) {
            return null;
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO movies (
                tmdb_id, media_type, title, original_title, overview, poster_path, backdrop_path,
                release_date, vote_average, popularity, genre_ids, tmdb_payload
             )
             VALUES (
                :tmdb_id, :media_type, :title, :original_title, :overview, :poster_path, :backdrop_path,
                :release_date, :vote_average, :popularity, CAST(:genre_ids AS jsonb), CAST(:tmdb_payload AS jsonb)
             )
             ON CONFLICT (tmdb_id, media_type)
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
                 updated_at = NOW()
             RETURNING id'
        );

        $statement->execute($this->tvSummaryParams($tv));

        return $this->getMediaRecordById((int) $statement->fetchColumn());
    }

    /** @return array<string, mixed>|null */
    public function saveTvDetails(array $tv): ?array
    {
        if (!isset($tv['id'])) {
            return null;
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO movies (
                tmdb_id, media_type, title, original_title, overview, poster_path, backdrop_path,
                release_date, runtime, vote_average, popularity, genres, tmdb_payload, has_details
             )
             VALUES (
                :tmdb_id, :media_type, :title, :original_title, :overview, :poster_path, :backdrop_path,
                :release_date, :runtime, :vote_average, :popularity, CAST(:genres AS jsonb), CAST(:tmdb_payload AS jsonb), TRUE
             )
             ON CONFLICT (tmdb_id, media_type)
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
                 updated_at = NOW()
             RETURNING id'
        );

        $statement->execute($this->tvDetailsParams($tv));

        return $this->getMediaRecordById((int) $statement->fetchColumn());
    }

    /** @return list<array<string, mixed>> */
    public function saveTvSummaries(array $tvShows): array
    {
        $saved = [];

        foreach ($tvShows as $tv) {
            $record = $this->saveTvSummary($tv);

            if ($record !== null) {
                $saved[] = $record;
            }
        }

        return $saved;
    }

    /**
     * Ищет сохранённые записи медиа. Сортировка выбирается только из allowlist.
     *
     * @param array<string, mixed> $criteria
     * @return array{page: int, results: list<array<string, mixed>>, total_pages: int, total_results: int}
     */
    public function searchMedia(string $query, array $criteria): array
    {
        return $this->queryMedia($query, $criteria);
    }

    /**
     * Выполняет discover по сохранённому каталогу.
     *
     * @param array<string, mixed> $criteria
     * @return array{page: int, results: list<array<string, mixed>>, total_pages: int, total_results: int}
     */
    public function discoverMedia(array $criteria): array
    {
        return $this->queryMedia(null, $criteria);
    }

    /**
     * Возвращает записи для полной переиндексации в стабильном порядке.
     *
     * @return list<array<string, mixed>>
     */
    public function getMediaRecordsBatch(int $afterId = 0, int $limit = 100): array
    {
        $limit = min(500, max(1, $limit));
        $statement = $this->pdo->prepare(
            'SELECT
                id,
                \'tmdb\' AS source,
                tmdb_id AS source_id,
                media_type,
                title,
                original_title,
                overview,
                poster_path,
                backdrop_path,
                release_date,
                vote_average,
                popularity,
                genre_ids,
                genres
             FROM movies
             WHERE id > :after_id
             ORDER BY id ASC
             LIMIT :limit'
        );
        $statement->bindValue(':after_id', $afterId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_map(
            fn (array $record): array => $this->decodeMediaRecord($record),
            $statement->fetchAll(),
        );
    }

    // ── Private helpers ──────────────────────────────────────

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
            'media_type' => 'movie',
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

    private function tvSummaryParams(array $tv): array
    {
        $params = $this->tvBaseParams($tv);
        $params['genre_ids'] = json_encode($tv['genre_ids'] ?? [], JSON_UNESCAPED_UNICODE);

        return $params;
    }

    private function tvDetailsParams(array $tv): array
    {
        $params = $this->tvBaseParams($tv);
        $params['runtime'] = $tv['episode_run_time'][0] ?? null;
        $params['genres'] = json_encode($tv['genres'] ?? [], JSON_UNESCAPED_UNICODE);

        return $params;
    }

    private function tvBaseParams(array $tv): array
    {
        $firstAirDate = $tv['first_air_date'] ?? null;

        if ($firstAirDate === '') {
            $firstAirDate = null;
        }

        return [
            'tmdb_id' => (int) $tv['id'],
            'media_type' => 'tv',
            'title' => $tv['name'] ?? null,
            'original_title' => $tv['original_name'] ?? null,
            'overview' => $tv['overview'] ?? null,
            'poster_path' => $tv['poster_path'] ?? null,
            'backdrop_path' => $tv['backdrop_path'] ?? null,
            'release_date' => $firstAirDate,
            'vote_average' => $tv['vote_average'] ?? null,
            'popularity' => $tv['popularity'] ?? null,
            'tmdb_payload' => json_encode($tv, JSON_UNESCAPED_UNICODE),
        ];
    }

    /** @return array<string, mixed>|null */
    private function getMediaRecordById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                id,
                \'tmdb\' AS source,
                tmdb_id AS source_id,
                media_type,
                title,
                original_title,
                overview,
                poster_path,
                backdrop_path,
                release_date,
                vote_average,
                popularity,
                genre_ids,
                genres
             FROM movies
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $record = $statement->fetch();

        return $record === false ? null : $this->decodeMediaRecord($record);
    }

    /** @param array<string, mixed> $record @return array<string, mixed> */
    private function decodeMediaRecord(array $record): array
    {
        foreach (['genre_ids', 'genres'] as $field) {
            if (is_string($record[$field] ?? null)) {
                $record[$field] = json_decode($record[$field], true) ?? [];
            }
        }

        return $record;
    }

    /**
     * @param string|null $query
     * @param array<string, mixed> $criteria
     * @return array{page: int, results: list<array<string, mixed>>, total_pages: int, total_results: int}
     */
    private function queryMedia(?string $query, array $criteria): array
    {
        $where = ['media_type = :media_type'];
        $params = ['media_type' => $criteria['type']];

        if ($query !== null) {
            $where[] = '(title ILIKE :query_title OR original_title ILIKE :query_original)';
            $params['query_title'] = '%' . $query . '%';
            $params['query_original'] = '%' . $query . '%';
        }

        if (isset($criteria['genre_id'])) {
            $where[] = "genre_ids @> CAST(:genre_ids AS jsonb)";
            $params['genre_ids'] = json_encode([(int) $criteria['genre_id']], JSON_THROW_ON_ERROR);
        }

        if (isset($criteria['year'])) {
            $where[] = 'release_date >= :year_start AND release_date < :year_end';
            $params['year_start'] = sprintf('%d-01-01', $criteria['year']);
            $params['year_end'] = sprintf('%d-01-01', $criteria['year'] + 1);
        }

        if (isset($criteria['min_rating'])) {
            $where[] = 'vote_average >= :min_rating';
            $params['min_rating'] = $criteria['min_rating'];
        }

        $sortMap = [
            'popularity.desc' => 'popularity DESC NULLS LAST',
            'popularity.asc' => 'popularity ASC NULLS LAST',
            'vote_average.desc' => 'vote_average DESC NULLS LAST',
            'vote_average.asc' => 'vote_average ASC NULLS LAST',
            'year.desc' => 'release_date DESC NULLS LAST',
            'year.asc' => 'release_date ASC NULLS LAST',
        ];
        $sort = $sortMap[$criteria['sort_by'] ?? 'popularity.desc'];
        $page = (int) $criteria['page'];
        $perPage = (int) $criteria['per_page'];
        $offset = ($page - 1) * $perPage;

        $countStatement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM movies WHERE ' . implode(' AND ', $where),
        );
        $countStatement->execute($params);
        $total = (int) $countStatement->fetchColumn();

        $statement = $this->pdo->prepare(
            'SELECT
                tmdb_id AS id,
                tmdb_id AS source_id,
                media_type,
                title,
                original_title,
                overview,
                poster_path,
                backdrop_path,
                release_date,
                vote_average,
                popularity,
                genre_ids,
                genres
             FROM movies
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY ' . $sort . '
             LIMIT :limit OFFSET :offset',
        );
        foreach ($params as $name => $value) {
            $statement->bindValue(':' . $name, $value);
        }
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        $results = array_map(
            fn (array $record): array => $this->decodeMediaRecord($record),
            $statement->fetchAll(),
        );

        return [
            'page' => $page,
            'results' => $results,
            'total_pages' => $total === 0 ? 0 : (int) ceil($total / $perPage),
            'total_results' => $total,
        ];
    }
}
