<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch;

final class MeilisearchIndexManager
{
    public function __construct(
        private MeilisearchGateway $gateway,
        private string $mediaIndex,
        private string $peopleIndex,
    ) {
    }

    public function initialize(): void
    {
        $this->gateway->ensureIndex(
            $this->mediaIndex,
            'id',
            [
                'searchableAttributes' => ['title', 'original_title', 'overview'],
                'filterableAttributes' => ['media_type', 'genres', 'year', 'vote_average'],
                'sortableAttributes' => ['popularity', 'vote_average', 'year'],
            ],
        );

        $this->gateway->ensureIndex(
            $this->peopleIndex,
            'id',
            [
                'searchableAttributes' => ['name', 'known_for'],
                'filterableAttributes' => ['department'],
            ],
        );
    }
}
