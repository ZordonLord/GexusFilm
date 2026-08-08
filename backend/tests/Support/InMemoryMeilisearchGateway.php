<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Infrastructure\Meilisearch\MeilisearchGateway;

final class InMemoryMeilisearchGateway implements MeilisearchGateway
{
    /** @var array<string, array<string, mixed>> */
    public array $documents = [];

    /** @var list<bool> */
    public array $waitFlags = [];

    public function health(): bool
    {
        return true;
    }

    public function ensureIndex(string $uid, string $primaryKey, array $settings): void
    {
    }

    public function upsertDocuments(string $uid, array $documents, bool $waitForCompletion = false): void
    {
        $this->waitFlags[] = $waitForCompletion;

        foreach ($documents as $document) {
            $this->documents[(string) $document['id']] = $document;
        }
    }
}
