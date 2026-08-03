<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch;

use Throwable;

final class MeilisearchHealthChecker
{
    public function __construct(private MeilisearchGateway $gateway)
    {
    }

    public function isHealthy(): bool
    {
        try {
            return $this->gateway->health();
        } catch (Throwable) {
            return false;
        }
    }
}
