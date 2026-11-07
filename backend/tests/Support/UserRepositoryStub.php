<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Repository\UserRepositoryInterface;

final class UserRepositoryStub implements UserRepositoryInterface
{
    /** @var array<string, mixed>|null */
    public ?array $storedUser = null;

    public function findByEmail(string $email): ?array
    {
        return $this->storedUser !== null && $this->storedUser['email'] === $email
            ? $this->storedUser
            : null;
    }

    public function create(string $email, string $passwordHash, ?string $displayName): array
    {
        $this->storedUser = [
            'id' => 1,
            'email' => $email,
            'password_hash' => $passwordHash,
            'display_name' => $displayName,
        ];

        return ['id' => 1, 'email' => $email, 'display_name' => $displayName];
    }

    public function findPublicById(int $id): ?array
    {
        if ($this->storedUser === null || $this->storedUser['id'] !== $id) {
            return null;
        }

        return [
            'id' => $id,
            'email' => $this->storedUser['email'],
            'display_name' => $this->storedUser['display_name'],
        ];
    }
}
