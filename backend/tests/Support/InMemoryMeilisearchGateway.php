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

    public function search(string $uid, ?string $query, array $parameters = []): array
    {
        $hits = array_values($this->documents);

        if ($query !== null && trim($query) !== '') {
            $needle = mb_strtolower(trim($query));
            $hits = array_values(array_filter($hits, static function (array $document) use ($needle): bool {
                return str_contains(mb_strtolower((string) ($document['title'] ?? '')), $needle)
                    || str_contains(mb_strtolower((string) ($document['original_title'] ?? '')), $needle)
                    || str_contains(mb_strtolower((string) ($document['overview'] ?? '')), $needle);
            }));
        }

        $offset = max(0, (int) ($parameters['offset'] ?? 0));
        $limit = max(1, (int) ($parameters['limit'] ?? 20));
        $total = count($hits);

        return [
            'hits' => array_slice($hits, $offset, $limit),
            'estimatedTotalHits' => $total,
        ];
    }
}
