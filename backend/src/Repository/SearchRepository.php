<?php

declare(strict_types=1);

namespace App\Repository;

use Throwable;

final class SearchRepository
{
    public function __construct(
        private ?MovieRepository $mediaRepository,
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

        return $this->searchPostgres($query, $criteria);
    }

    /**
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>|null
     */
    public function discover(array $criteria): ?array
    {
        if ($this->requiresRemoteDiscover($criteria)) {
            return null;
        }

        return $this->discoverPostgres($criteria);
    }

    /**
     * Локальная схема не хранит язык, страну и доступность у провайдеров.
     * Нельзя возвращать частично отфильтрованный локальный результат.
     *
     * @param array<string, mixed> $criteria
     */
    private function requiresRemoteDiscover(array $criteria): bool
    {
        $remoteFields = [
            'region',
            'date_from',
            'date_to',
            'vote_count.gte',
            'language',
            'country',
            'watch_region',
            'with_watch_providers',
            'with_watch_monetization_types',
        ];

        foreach ($remoteFields as $field) {
            if (array_key_exists($field, $criteria)) {
                return true;
            }
        }

        return false;
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
}
