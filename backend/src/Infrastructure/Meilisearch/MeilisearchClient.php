<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch;

use Meilisearch\Client;
use Throwable;

final class MeilisearchClient implements MeilisearchGateway
{
    private Client $client;

    public function __construct(string $host, ?string $apiKey = null)
    {
        $this->client = new Client($host, $apiKey);
    }

    public function health(): bool
    {
        return $this->client->health() !== null;
    }

    public function ensureIndex(string $uid, string $primaryKey, array $settings): void
    {
        try {
            $index = $this->client->getIndex($uid);
        } catch (Throwable $exception) {
            $task = $this->client->createIndex($uid, ['primaryKey' => $primaryKey]);
            $this->waitForTask($task);
            $index = $this->client->getIndex($uid);
        }

        $task = $index->updateSettings($settings);
        $this->waitForTask($task);
    }

    /**
     * Передаёт документы в Meilisearch и при необходимости ждёт завершения задачи.
     *
     * HTTP-путь использует enqueue без ожидания, а CLI-переиндексация включает
     * ожидание, чтобы административная команда сообщала о фактическом результате.
     *
     * @param list<array<string, mixed>> $documents
     */
    public function upsertDocuments(string $uid, array $documents, bool $waitForCompletion = false): void
    {
        if ($documents === []) {
            return;
        }

        $task = $this->client->index($uid)->addDocuments($documents);

        if ($waitForCompletion) {
            $this->waitForTask($task);
        }
    }

    /**
     * Ждёт асинхронную операцию, чтобы initializer завершался только после применения настроек.
     *
     * @param array<string, mixed> $task
     */
    private function waitForTask(array $task): void
    {
        if (!isset($task['taskUid'])) {
            return;
        }

        $this->client->waitForTask((int) $task['taskUid']);
    }
}
