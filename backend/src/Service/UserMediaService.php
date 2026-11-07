<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ValidationException;
use App\Repository\UserMediaRepositoryInterface;

final class UserMediaService
{
    public function __construct(private UserMediaRepositoryInterface $media)
    {
    }

    /** @return list<array{media_type: string, source_id: int, added_at: string}> */
    public function getFavorites(int $userId): array
    {
        return $this->media->getList($userId, 'favorites');
    }

    /** @return list<array{media_type: string, source_id: int, added_at: string}> */
    public function getHistory(int $userId): array
    {
        return $this->media->getHistory($userId);
    }

    public function isFavorite(int $userId, string $mediaType, int $sourceId): bool
    {
        $this->validateMediaIdentity($mediaType, $sourceId);

        return $this->media->contains($userId, 'favorites', $mediaType, $sourceId);
    }

    public function recordVisit(int $userId, string $mediaType, int $sourceId): void
    {
        $this->validateMediaIdentity($mediaType, $sourceId);

        $this->media->recordVisit($userId, $mediaType, $sourceId);
    }

    public function addFavorite(int $userId, string $mediaType, int $sourceId): void
    {
        $this->validateMediaIdentity($mediaType, $sourceId);
        $this->media->add($userId, 'favorites', $mediaType, $sourceId);
    }

    public function removeFavorite(int $userId, string $mediaType, int $sourceId): void
    {
        $this->validateMediaIdentity($mediaType, $sourceId);
        $this->media->remove($userId, 'favorites', $mediaType, $sourceId);
    }

    public function clearHistory(int $userId): void
    {
        $this->media->clearHistory($userId);
    }

    private function validateMediaIdentity(string $mediaType, int $sourceId): void
    {
        if (!in_array($mediaType, ['movie', 'tv'], true) || $sourceId < 1) {
            throw new ValidationException('Указан некорректный тип или идентификатор контента');
        }
    }
}
