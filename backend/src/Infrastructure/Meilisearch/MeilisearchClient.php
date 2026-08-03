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
