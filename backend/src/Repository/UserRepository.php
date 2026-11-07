<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\ConflictException;
use PDO;
use PDOException;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, email, password_hash, display_name FROM users WHERE email = :email'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user === false ? null : $this->normalizeUser($user, true);
    }

    public function create(string $email, string $passwordHash, ?string $displayName): array
    {
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO users (email, password_hash, display_name)
                VALUES (:email, :password_hash, :display_name)
                RETURNING id, email, display_name'
            );
            $statement->execute([
                'email' => $email,
                'password_hash' => $passwordHash,
                'display_name' => $displayName,
            ]);

            return $this->normalizeUser($statement->fetch(), false);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23505') {
                throw new ConflictException('Аккаунт с таким адресом уже существует', 0, $exception);
            }

            throw $exception;
        }
    }

    public function findPublicById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, email, display_name FROM users WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user === false ? null : $this->normalizeUser($user, false);
    }

    /** @param array<string, mixed> $user
     *  @return array{id: int, email: string, password_hash?: string, display_name: ?string}
     */
    private function normalizeUser(array $user, bool $includePasswordHash): array
    {
        $result = [
            'id' => (int) $user['id'],
            'email' => (string) $user['email'],
            'display_name' => $user['display_name'] === null ? null : (string) $user['display_name'],
        ];

        if ($includePasswordHash) {
            $result['password_hash'] = (string) $user['password_hash'];
        }

        return $result;
    }
}
