<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

try {
    meilisearch_index_manager()->initialize();
    fwrite(STDOUT, "Meilisearch indexes initialized.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, "Meilisearch initialization failed: {$exception->getMessage()}\n");
    exit(1);
}