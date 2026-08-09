<?php

declare(strict_types=1);

namespace App\Service;

interface SearchServiceInterface
{
    /** @param array<string, mixed> $criteria */
    public function search(string $query, array $criteria): array;

    /** @param array<string, mixed> $criteria */
    public function discover(array $criteria): array;
}
