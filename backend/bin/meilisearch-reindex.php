<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

try {
    $repository = movie_repository();

    if ($repository === null) {
        throw new RuntimeException('PostgreSQL repository is unavailable.');
    }

    $config = meilisearch_config();
    $sync = media_index_sync_service();
    $afterId = 0;
    $indexed = 0;

    do {
        $records = $repository->getMediaRecordsBatch($afterId, $config['reindex_batch_size']);

        if ($records === []) {
            break;
        }

        $sync->reindexBatch($records);
        $indexed += count($records);
        $lastRecord = $records[array_key_last($records)];
        $afterId = (int) ($lastRecord['id'] ?? 0);

        if ($afterId < 1) {
            throw new RuntimeException('Reindex batch did not contain a valid record id.');
        }
    } while (true);

    fwrite(STDOUT, sprintf("Media reindex completed: %d documents.\n", $indexed));
} catch (Throwable $exception) {
    fwrite(STDERR, "Media reindex failed: {$exception->getMessage()}\n");
    exit(1);
}