<?php

declare(strict_types=1);

namespace App\Tests;

use App\Exception\ForbiddenException;
use App\Exception\UnauthorizedException;
use App\Http\AuthSession;
use PHPUnit\Framework\TestCase;

final class AuthSessionTest extends TestCase
{
    private string $sessionDirectory;
    private string|false $previousEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousEnvironment = getenv('APP_ENV');
        putenv('APP_ENV=development');
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_abort();
        }
        session_id('');
        session_name('PHPSESSID');
        $this->sessionDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gexusfilm-test-session-' . bin2hex(random_bytes(6));
        mkdir($this->sessionDirectory, 0700, true);
        session_save_path($this->sessionDirectory);
        $_COOKIE['gexusfilm_session'] = 'test-cookie';
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_id('');
        unset($_COOKIE['gexusfilm_session']);
        if ($this->previousEnvironment === false) {
            putenv('APP_ENV');
        } else {
            putenv('APP_ENV=' . $this->previousEnvironment);
        }
        foreach (glob($this->sessionDirectory . DIRECTORY_SEPARATOR . 'sess_*') ?: [] as $path) {
            unlink($path);
        }
        rmdir($this->sessionDirectory);
        parent::tearDown();
    }

    public function testCsrfTokenIsRequiredAndComparedSafely(): void
    {
        $session = new AuthSession();
        $token = $session->csrfToken();
        $session->verifyCsrf($token);

        $this->expectException(ForbiddenException::class);
        $session->verifyCsrf('invalid-token');
    }

    public function testLoginRotatesSessionAndStoresOnlyUserIdentity(): void
    {
        $session = new AuthSession();
        $session->csrfToken();
        $previousSessionId = session_id();

        $session->authenticate(42);

        self::assertNotSame($previousSessionId, session_id());
        self::assertSame(42, $session->requireUserId());
        self::assertArrayNotHasKey('password', $_SESSION);
    }

    public function testAnonymousSessionCannotReadUserIdentity(): void
    {
        $session = new AuthSession();

        $this->expectException(UnauthorizedException::class);
        $session->requireUserId();
    }

    public function testExpiredSessionCannotReadUserIdentity(): void
    {
        $session = new AuthSession();
        $session->csrfToken();
        $_SESSION['user_id'] = 42;
        $_SESSION['expires_at'] = time() - 1;

        $this->expectException(UnauthorizedException::class);
        $session->requireUserId();
    }
}
