<?php

declare(strict_types=1);

namespace App\Service;

use App\Infrastructure\Meilisearch\MediaDocumentFactory;
use App\Infrastructure\Meilisearch\MeilisearchGateway;
use Throwable;

final class MediaIndexSyncService implements MediaIndexPort
{
    public function __construct(
        private MeilisearchGateway $gateway,
        private MediaDocumentFactory $documentFactory,
        private string $index,
    ) {
    }

    /**
     * Ставит документы в Meilisearch без ожидания асинхронной задачи.
     * Ошибка производного индекса не отменяет commit PostgreSQL.
     *
     * @param list<array<string, mixed>> $mediaRecords
     */
    public function scheduleSavedMedia(array $mediaRecords): void
    {
        try {
            $this->upsert($mediaRecords, false);
        } catch (Throwable $exception) {
            error_log('Media index enqueue failed: ' . $exception->getMessage());
        }
    }

    /**
     * Индексирует одну пачку и ждёт фактического завершения задачи.
     * Используется CLI-переиндексацией, где ошибка должна завершить команду.
     *
     * @param list<array<string, mixed>> $mediaRecords
     */
    public function reindexBatch(array $mediaRecords): void
    {
        $this->upsert($mediaRecords, true);
    }

    /** @param list<array<string, mixed>> $mediaRecords */
    private function upsert(array $mediaRecords, bool $waitForCompletion): void
    {
        if ($mediaRecords === []) {
            return;
        }

        $documents = array_map(
            fn (array $record): array => $this->documentFactory->create($record),
            $mediaRecords,
        );

        $this->gateway->upsertDocuments($this->index, array_values($documents), $waitForCompletion);
    }
}
