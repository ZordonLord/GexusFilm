<?php

declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Meilisearch\MediaDocumentFactory;
use App\Service\MediaIndexSyncService;
use App\Tests\Support\InMemoryMeilisearchGateway;
use PHPUnit\Framework\TestCase;

final class MediaIndexSyncServiceTest extends TestCase
{
    public function testRepeatedUpsertReplacesDocumentWithoutDuplicates(): void
    {
        $gateway = new InMemoryMeilisearchGateway();
        $sync = new MediaIndexSyncService($gateway, new MediaDocumentFactory(), 'media');
        $record = ['source' => 'tmdb', 'source_id' => 603, 'media_type' => 'movie', 'title' => 'Old title'];

        $sync->reindexBatch([$record]);
        $record['title'] = 'New title';
        $sync->reindexBatch([$record]);

        self::assertCount(1, $gateway->documents);
        self::assertSame('New title', $gateway->documents['tmdb:movie:603']['title']);
        self::assertSame([true, true], $gateway->waitFlags);
    }

    public function testScheduleUsesEnqueueModeAndSeparatesMovieAndTvIds(): void
    {
        $gateway = new InMemoryMeilisearchGateway();
        $sync = new MediaIndexSyncService($gateway, new MediaDocumentFactory(), 'media');

        $sync->scheduleSavedMedia([
            ['source' => 'tmdb', 'source_id' => 603, 'media_type' => 'movie'],
            ['source' => 'tmdb', 'source_id' => 603, 'media_type' => 'tv'],
        ]);

        self::assertCount(2, $gateway->documents);
        self::assertSame([false], $gateway->waitFlags);
    }
}
