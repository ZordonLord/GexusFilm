<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class UserMediaRepository implements UserMediaRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<array{media_type: string, source_id: int, added_at: string}> */
    public function getList(int $userId, string $listType): array
    {
        $listId = $this->findListId($userId, $listType);
        if ($listId === null) {
            return [];
        }

        $statement = $this->pdo->prepare(
            'SELECT media_type, source_id, added_at
            FROM list_items
            WHERE list_id = :list_id AND source = :source
            ORDER BY added_at DESC, id DESC
            LIMIT 100'
        );
        $statement->execute(['list_id' => $listId, 'source' => 'tmdb']);

        return array_map(
            static fn (array $item): array => [
                'media_type' => (string) $item['media_type'],
                'source_id' => (int) $item['source_id'],
                'added_at' => (string) $item['added_at'],
            ],
            $statement->fetchAll(),
        );
    }

    public function contains(int $userId, string $listType, string $mediaType, int $sourceId): bool
    {
        $listId = $this->findListId($userId, $listType);
        if ($listId === null) {
            return false;
        }

        $statement = $this->pdo->prepare(
            'SELECT 1 FROM list_items
            WHERE list_id = :list_id AND source = :source
                AND media_type = :media_type AND source_id = :source_id'
        );
        $statement->execute([
            'list_id' => $listId,
            'source' => 'tmdb',
            'media_type' => $mediaType,
            'source_id' => $sourceId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function add(int $userId, string $listType, string $mediaType, int $sourceId): void
    {
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO user_lists (user_id, list_type)
                VALUES (:user_id, :list_type)
                ON CONFLICT (user_id, list_type) DO NOTHING'
            );
            $statement->execute(['user_id' => $userId, 'list_type' => $listType]);
            $listId = $this->findListId($userId, $listType);

            $item = $this->pdo->prepare(
                'INSERT INTO list_items (list_id, media_type, source, source_id)
                VALUES (:list_id, :media_type, :source, :source_id)
                ON CONFLICT (list_id, media_type, source, source_id)
                DO NOTHING'
            );
            $item->execute([
                'list_id' => $listId,
                'media_type' => $mediaType,
                'source' => 'tmdb',
                'source_id' => $sourceId,
            ]);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function remove(int $userId, string $listType, string $mediaType, int $sourceId): void
    {
        $listId = $this->findListId($userId, $listType);
        if ($listId === null) {
            return;
        }

        $statement = $this->pdo->prepare(
            'DELETE FROM list_items
            WHERE list_id = :list_id AND source = :source
                AND media_type = :media_type AND source_id = :source_id'
        );
        $statement->execute([
            'list_id' => $listId,
            'source' => 'tmdb',
            'media_type' => $mediaType,
            'source_id' => $sourceId,
        ]);
    }

    public function getHistory(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT media_type, source_id, last_viewed_at
            FROM user_media_history
            WHERE user_id = :user_id AND source = :source
            ORDER BY last_viewed_at DESC, id DESC
            LIMIT 100'
        );
        $statement->execute(['user_id' => $userId, 'source' => 'tmdb']);

        return array_map(
            static fn (array $item): array => [
                'media_type' => (string) $item['media_type'],
                'source_id' => (int) $item['source_id'],
                'last_viewed_at' => (string) $item['last_viewed_at'],
            ],
            $statement->fetchAll(),
        );
    }

    public function recordVisit(int $userId, string $mediaType, int $sourceId): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO user_media_history (user_id, media_type, source, source_id)
            VALUES (:user_id, :media_type, :source, :source_id)
            ON CONFLICT (user_id, media_type, source, source_id)
            DO UPDATE SET last_viewed_at = NOW()'
        );
        $statement->execute([
            'user_id' => $userId,
            'media_type' => $mediaType,
            'source' => 'tmdb',
            'source_id' => $sourceId,
        ]);
    }

    public function clearHistory(int $userId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM user_media_history WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);
    }

    private function findListId(int $userId, string $listType): ?int
    {
        $statement = $this->pdo->prepare(
            'SELECT id FROM user_lists WHERE user_id = :user_id AND list_type = :list_type'
        );
        $statement->execute(['user_id' => $userId, 'list_type' => $listType]);
        $id = $statement->fetchColumn();

        return $id === false ? null : (int) $id;
    }
}
