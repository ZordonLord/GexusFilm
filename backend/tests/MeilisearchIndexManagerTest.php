<?php

declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Meilisearch\MeilisearchGateway;
use App\Infrastructure\Meilisearch\MeilisearchIndexManager;
use PHPUnit\Framework\TestCase;

final class MeilisearchIndexManagerTest extends TestCase
{
    public function testInitializeConfiguresMediaAndPeopleIndexes(): void
    {
        $gateway = new class implements MeilisearchGateway {
            /** @var list<array{uid: string, primaryKey: string, settings: array<string, mixed>}> */
            public array $indexes = [];

            public function health(): bool
            {
                return true;
            }

            public function ensureIndex(string $uid, string $primaryKey, array $settings): void
            {
                $this->indexes[] = [
                    'uid' => $uid,
                    'primaryKey' => $primaryKey,
                    'settings' => $settings,
                ];
            }

            public function upsertDocuments(string $uid, array $documents, bool $waitForCompletion = false): void
            {
            }

            public function search(string $uid, ?string $query, array $parameters = []): array
            {
                return [];
            }
        };
        $manager = new MeilisearchIndexManager($gateway, 'media', 'people');

        $manager->initialize();

        self::assertCount(2, $gateway->indexes);
        self::assertSame('media', $gateway->indexes[0]['uid']);
        self::assertSame('people', $gateway->indexes[1]['uid']);
        self::assertSame(['title', 'original_title', 'overview'], $gateway->indexes[0]['settings']['searchableAttributes']);
        self::assertSame(['name', 'known_for'], $gateway->indexes[1]['settings']['searchableAttributes']);
    }
}
