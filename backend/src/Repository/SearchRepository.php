<?php

declare(strict_types=1);

namespace App\Repository;

use App\Infrastructure\Meilisearch\MeilisearchGateway;
use Throwable;

final class SearchRepository
{
    public function __construct(
        private ?MovieRepository $mediaRepository,
        private ?MeilisearchGateway $searchGateway,
        private string $mediaIndex = 'media',
    ) {
    }

    /**
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>|null null означает недоступность всех локальных источников.
     */
    public function search(string $query, array $criteria): ?array
    {
        if (($criteria['region'] ?? null) !== null) {
            return null;
        }

        $result = $this->searchMeilisearch($query, $criteria);

        return $result ?? $this->searchPostgres($query, $criteria);
    }

    /**
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>|null
     */
    public function discover(array $criteria): ?array
    {
        if (($criteria['region'] ?? null) !== null) {
            return null;
        }

        $result = $this->searchMeilisearch(null, $criteria);

        return $result ?? $this->discoverPostgres($criteria);
    }

    /** @param array<string, mixed> $criteria */
    private function searchMeilisearch(?string $query, array $criteria): ?array
    {
        if ($this->searchGateway === null) {
            return null;
        }

        try {
            $page = (int) $criteria['page'];
            $perPage = (int) $criteria['per_page'];
            $response = $this->searchGateway->search(
                $this->mediaIndex,
                $query,
                $this->meilisearchParameters($criteria, $page, $perPage),
            );
            $total = (int) ($response['estimatedTotalHits'] ?? $response['totalHits'] ?? 0);

            return [
                'page' => $page,
                'results' => array_map(
                    fn (array $document): array => $this->documentToResult($document),
                    array_values(array_filter($response['hits'] ?? [], 'is_array')),
                ),
                'total_pages' => $total === 0 ? 0 : (int) ceil($total / $perPage),
                'total_results' => $total,
            ];
        } catch (Throwable $exception) {
            error_log('Meilisearch search failed: ' . $exception::class);

            return null;
        }
    }

    /** @param array<string, mixed> $criteria */
    private function searchPostgres(string $query, array $criteria): ?array
    {
        if ($this->mediaRepository === null) {
            return null;
        }

        try {
            return $this->mediaRepository->searchMedia($query, $criteria);
        } catch (Throwable $exception) {
            error_log('PostgreSQL search failed: ' . $exception::class);

            return null;
        }
    }

    /** @param array<string, mixed> $criteria */
    private function discoverPostgres(array $criteria): ?array
    {
        if ($this->mediaRepository === null) {
            return null;
        }

        try {
            return $this->mediaRepository->discoverMedia($criteria);
        } catch (Throwable $exception) {
            error_log('PostgreSQL discover failed: ' . $exception::class);

            return null;
        }
    }

    /** @param array<string, mixed> $criteria @return array<string, mixed> */
    private function meilisearchParameters(array $criteria, int $page, int $perPage): array
    {
        $filters = ['media_type = ' . $criteria['type']];

        if (isset($criteria['genre_id'])) {
            $filters[] = 'genres = ' . (int) $criteria['genre_id'];
        }

        if (isset($criteria['year'])) {
            $filters[] = 'year = ' . (int) $criteria['year'];
        }

        if (isset($criteria['min_rating'])) {
            $filters[] = 'vote_average >= ' . (float) $criteria['min_rating'];
        }

        $parameters = [
            'filter' => implode(' AND ', $filters),
            'offset' => ($page - 1) * $perPage,
            'limit' => $perPage,
        ];

        if (isset($criteria['sort_by'])) {
            [$field, $direction] = explode('.', (string) $criteria['sort_by'], 2);
            $parameters['sort'] = [$field . ':' . $direction];
        }

        return $parameters;
    }

    /** @param array<string, mixed> $document @return array<string, mixed> */
    private function documentToResult(array $document): array
    {
        $mediaType = (string) ($document['media_type'] ?? 'movie');
        $result = [
            'id' => is_numeric($document['source_id'] ?? null)
                ? (int) $document['source_id']
                : ($document['source_id'] ?? null),
            'media_type' => $mediaType,
            'overview' => $document['overview'] ?? null,
            'poster_path' => $document['poster_path'] ?? null,
            'backdrop_path' => $document['backdrop_path'] ?? null,
            'vote_average' => $document['vote_average'] ?? null,
            'popularity' => $document['popularity'] ?? null,
            'genre_ids' => $document['genres'] ?? [],
        ];

        if ($mediaType === 'tv') {
            $result['name'] = $document['title'] ?? null;
            $result['original_name'] = $document['original_title'] ?? null;
            $result['first_air_date'] = $document['release_date'] ?? null;
        } else {
            $result['title'] = $document['title'] ?? null;
            $result['original_title'] = $document['original_title'] ?? null;
            $result['release_date'] = $document['release_date'] ?? null;
        }

        return array_filter($result, static fn (mixed $value): bool => $value !== null);
    }
}
