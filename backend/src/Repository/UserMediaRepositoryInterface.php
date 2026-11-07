<?php

declare(strict_types=1);

namespace App\Repository;

interface UserMediaRepositoryInterface
{
    /** @return list<array{media_type: string, source_id: int, last_viewed_at: string}> */
    public function getList(int $userId, string $listType): array;

    public function contains(int $userId, string $listType, string $mediaType, int $sourceId): bool;

    public function add(int $userId, string $listType, string $mediaType, int $sourceId): void;

    public function remove(int $userId, string $listType, string $mediaType, int $sourceId): void;

    /** @return list<array{media_type: string, source_id: int, last_viewed_at: string}> */
    public function getHistory(int $userId): array;

    public function recordVisit(int $userId, string $mediaType, int $sourceId): void;

    public function clearHistory(int $userId): void;
}
