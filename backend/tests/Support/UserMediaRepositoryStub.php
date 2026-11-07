<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Repository\UserMediaRepositoryInterface;

final class UserMediaRepositoryStub implements UserMediaRepositoryInterface
{
    /** @var list<array{user_id: int, list_type: string, media_type: string, source_id: int}> */
    public array $items = [];

    /** @var list<array{user_id: int, media_type: string, source_id: int}> */
    public array $visits = [];

    public array $history = [];

    public int $clearedUserId = 0;

    public function getList(int $userId, string $listType): array
    {
        return array_values(array_map(
            static fn (array $item): array => [
                'media_type' => $item['media_type'],
                'source_id' => $item['source_id'],
                'added_at' => '2026-11-03T00:00:00+00:00',
            ],
            array_filter(
                $this->items,
                static fn (array $item): bool => $item['user_id'] === $userId
                    && $item['list_type'] === $listType,
            ),
        ));
    }

    public function contains(int $userId, string $listType, string $mediaType, int $sourceId): bool
    {
        foreach ($this->items as $item) {
            if (
                $item['user_id'] === $userId
                && $item['list_type'] === $listType
                && $item['media_type'] === $mediaType
                && $item['source_id'] === $sourceId
            ) {
                return true;
            }
        }

        return false;
    }

    public function add(int $userId, string $listType, string $mediaType, int $sourceId): void
    {
        if (!$this->contains($userId, $listType, $mediaType, $sourceId)) {
            $this->items[] = [
                'user_id' => $userId,
                'list_type' => $listType,
                'media_type' => $mediaType,
                'source_id' => $sourceId,
            ];
        }
    }

    public function remove(int $userId, string $listType, string $mediaType, int $sourceId): void
    {
        $this->items = array_values(array_filter(
            $this->items,
            static fn (array $item): bool => !(
                $item['user_id'] === $userId
                && $item['list_type'] === $listType
                && $item['media_type'] === $mediaType
                && $item['source_id'] === $sourceId
            ),
        ));
    }

    public function getHistory(int $userId): array
    {
        return array_values(array_filter(
            $this->history,
            static fn (array $item): bool => $item['user_id'] === $userId,
        ));
    }

    public function recordVisit(int $userId, string $mediaType, int $sourceId): void
    {
        $this->visits[] = ['user_id' => $userId, 'media_type' => $mediaType, 'source_id' => $sourceId];
    }

    public function clearHistory(int $userId): void
    {
        $this->clearedUserId = $userId;
        $this->history = array_values(array_filter(
            $this->history,
            static fn (array $item): bool => $item['user_id'] !== $userId,
        ));
    }
}
