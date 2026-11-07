<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ConflictException;
use App\Exception\UnauthorizedException;
use App\Exception\ValidationException;
use App\Repository\UserRepositoryInterface;

final class AuthService
{
    private const PASSWORD_MIN_LENGTH = 12;
    private const PASSWORD_MAX_LENGTH = 1024;
    private const DUMMY_PASSWORD_HASH = '$argon2id$v=19$m=65536,t=4,p=1$OGoxbXkycHBZRDMvR2ZWVA$0H5m8y4zW4UvuThZQv9ER/VPnJtmRmHn1jPlZlooVNI';

    public function __construct(private UserRepositoryInterface $users)
    {
    }

    /** @param array<string, mixed> $input */
    public function register(array $input): ?array
    {
        [$email, $password, $displayName] = $this->validateRegistration($input);
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID);

        if ($this->users->findByEmail($email) !== null) {
            return null;
        }

        try {
            return $this->users->create($email, $passwordHash, $displayName);
        } catch (ConflictException) {
            return null;
        }
    }

    /** @param array<string, mixed> $input
     *  @return array{id: int, email: string, display_name: ?string}
     */
    public function login(array $input): array
    {
        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;

        if (!is_string($email) || !is_string($password) || strlen($password) > self::PASSWORD_MAX_LENGTH) {
            throw new ValidationException('Укажите адрес электронной почты и пароль');
        }

        $email = strtolower(trim($email));
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            password_verify($password, self::dummyPasswordHash());
            throw new UnauthorizedException('Неверный адрес электронной почты или пароль');
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new UnauthorizedException('Неверный адрес электронной почты или пароль');
        }

        return $this->publicUser($user);
    }

    /** @return array{id: int, email: string, display_name: ?string} */
    public function getProfile(int $userId): array
    {
        $user = $this->users->findPublicById($userId);

        if ($user === null) {
            throw new UnauthorizedException('Требуется вход в аккаунт');
        }

        return $this->publicUser($user);
    }

    /** @param array<string, mixed> $input
     *  @return array{0: string, 1: string, 2: ?string}
     */
    private function validateRegistration(array $input): array
    {
        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;
        $displayName = $input['display_name'] ?? null;

        $details = [];
        if (!is_string($email) || strlen($email) > 254 || filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false) {
            $details[] = ['field' => 'email', 'issue' => 'invalid'];
        }
        if (
            !is_string($password)
            || strlen($password) < self::PASSWORD_MIN_LENGTH
            || strlen($password) > self::PASSWORD_MAX_LENGTH
        ) {
            $details[] = ['field' => 'password', 'issue' => 'length'];
        }
        if ($displayName !== null && (!is_string($displayName) || mb_strlen(trim($displayName)) > 80)) {
            $details[] = ['field' => 'display_name', 'issue' => 'invalid'];
        }
        if ($details !== []) {
            throw new ValidationException('Проверьте данные регистрации', $details);
        }

        return [
            strtolower(trim($email)),
            $password,
            is_string($displayName) && trim($displayName) !== '' ? trim($displayName) : null,
        ];
    }

    /** @param array<string, mixed> $user
     *  @return array{id: int, email: string, display_name: ?string}
     */
    private function publicUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'email' => (string) $user['email'],
            'display_name' => $user['display_name'] === null ? null : (string) $user['display_name'],
        ];
    }

    private static function dummyPasswordHash(): string
    {
        // Фиктивный хэш выравнивает стоимость проверки для несуществующего email.
        return self::DUMMY_PASSWORD_HASH;
    }
}
