<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\AuthSession;
use App\Service\UserMediaService;

final class UserMediaController
{
    public function __construct(
        private UserMediaService $media,
        private AuthSession $session,
    ) {
    }

    public function getFavorites(): array
    {
        return ['results' => $this->media->getFavorites($this->session->requireUserId())];
    }

    public function getHistory(): array
    {
        return ['results' => $this->media->getHistory($this->session->requireUserId())];
    }

    public function isFavorite(string $mediaType, int $sourceId): array
    {
        return ['is_favorite' => $this->media->isFavorite($this->session->requireUserId(), $mediaType, $sourceId)];
    }

    public function addFavorite(string $csrfToken, string $mediaType, int $sourceId): array
    {
        $this->session->verifyCsrf($csrfToken);
        $this->media->addFavorite($this->session->requireUserId(), $mediaType, $sourceId);

        return ['is_favorite' => true];
    }

    public function removeFavorite(string $csrfToken, string $mediaType, int $sourceId): array
    {
        $this->session->verifyCsrf($csrfToken);
        $this->media->removeFavorite($this->session->requireUserId(), $mediaType, $sourceId);

        return ['is_favorite' => false];
    }

    public function recordVisit(string $csrfToken, string $mediaType, int $sourceId): array
    {
        $this->session->verifyCsrf($csrfToken);
        $this->media->recordVisit($this->session->requireUserId(), $mediaType, $sourceId);

        return ['recorded' => true];
    }

    public function clearHistory(string $csrfToken): array
    {
        $this->session->verifyCsrf($csrfToken);
        $this->media->clearHistory($this->session->requireUserId());

        return ['cleared' => true];
    }
}
