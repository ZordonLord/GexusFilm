<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch;

interface MeilisearchGateway
{
    public function health(): bool;

    /**
     * Создаёт или обновляет настройки индекса без добавления документов.
     *
     * @param array<string, mixed> $settings
     */
    public function ensureIndex(string $uid, string $primaryKey, array $settings): void;

    /**
     * Добавляет или обновляет пакет документов в производном индексе.
     *
     * @param list<array<string, mixed>> $documents
     */
    public function upsertDocuments(string $uid, array $documents, bool $waitForCompletion = false): void;
}
