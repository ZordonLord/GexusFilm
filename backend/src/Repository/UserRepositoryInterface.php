<?php

declare(strict_types=1);

namespace App\Repository;

interface UserRepositoryInterface
{
    /** @return array{id: int, email: string, password_hash: string, display_name: ?string}|null */
    public function findByEmail(string $email): ?array;

    /** @return array{id: int, email: string, display_name: ?string} */
    public function create(string $email, string $passwordHash, ?string $displayName): array;

    /** @return array{id: int, email: string, display_name: ?string}|null */
    public function findPublicById(int $id): ?array;
}
