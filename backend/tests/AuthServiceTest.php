<?php

declare(strict_types=1);

namespace App\Tests;

use App\Exception\UnauthorizedException;
use App\Exception\ValidationException;
use App\Repository\UserRepositoryInterface;
use App\Service\AuthService;
use App\Tests\Support\UserRepositoryStub;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    public function testRegistrationNormalizesEmailAndStoresPasswordHash(): void
    {
        $repository = new UserRepositoryStub();
        $service = new AuthService($repository);

        $user = $service->register([
            'email' => '  USER@Example.COM ',
            'password' => 'long-enough-password',
            'display_name' => '  User  ',
        ]);

        self::assertSame('user@example.com', $user['email']);
        self::assertSame('User', $user['display_name']);
        self::assertArrayNotHasKey('password_hash', $user);
        self::assertTrue(password_verify('long-enough-password', $repository->storedUser['password_hash']));
        self::assertNotSame('long-enough-password', $repository->storedUser['password_hash']);
    }

    public function testRegistrationRejectsInvalidEmailAndShortPassword(): void
    {
        $service = new AuthService(new UserRepositoryStub());

        try {
            $service->register(['email' => 'invalid', 'password' => 'short']);
            self::fail('Expected invalid registration to be rejected.');
        } catch (ValidationException $exception) {
            self::assertCount(2, $exception->details);
        }
    }

    public function testRegistrationRejectsExistingEmail(): void
    {
        $repository = new UserRepositoryStub();
        $repository->storedUser = [
            'id' => 1,
            'email' => 'user@example.com',
            'password_hash' => password_hash('not-the-password', PASSWORD_ARGON2ID),
            'display_name' => null,
        ];
        $service = new AuthService($repository);

        self::assertNull(
            $service->register(['email' => 'USER@example.com', 'password' => 'long-enough-password']),
        );
    }

    public function testLoginReturnsSafeProfileAndRejectsWrongPassword(): void
    {
        $repository = new UserRepositoryStub();
        $repository->storedUser = [
            'id' => 7,
            'email' => 'user@example.com',
            'password_hash' => password_hash('correct-password', PASSWORD_ARGON2ID),
            'display_name' => 'User',
        ];
        $service = new AuthService($repository);

        $user = $service->login(['email' => 'USER@example.com', 'password' => 'correct-password']);
        self::assertSame(7, $user['id']);
        self::assertArrayNotHasKey('password_hash', $user);

        $this->expectException(UnauthorizedException::class);
        $service->login(['email' => 'user@example.com', 'password' => 'wrong-password']);
    }

    public function testUnknownEmailAndWrongPasswordUseSamePublicError(): void
    {
        $repository = new UserRepositoryStub();
        $repository->storedUser = [
            'id' => 7,
            'email' => 'user@example.com',
            'password_hash' => password_hash('correct-password', PASSWORD_ARGON2ID),
            'display_name' => 'User',
        ];
        $service = new AuthService($repository);

        try {
            $service->login(['email' => 'user@example.com', 'password' => 'wrong-password']);
            self::fail('Expected invalid credentials to be rejected.');
        } catch (UnauthorizedException $exception) {
            $knownEmailMessage = $exception->getMessage();
        }

        try {
            $service->login(['email' => 'missing@example.com', 'password' => 'wrong-password']);
            self::fail('Expected invalid credentials to be rejected.');
        } catch (UnauthorizedException $exception) {
            self::assertSame($knownEmailMessage, $exception->getMessage());
        }
    }

    public function testLoginRejectsOversizedPasswordBeforeHashVerification(): void
    {
        $service = new AuthService(new UserRepositoryStub());

        $this->expectException(ValidationException::class);
        $service->login([
            'email' => 'person@example.invalid',
            'password' => str_repeat('a', 1025),
        ]);
    }
}
