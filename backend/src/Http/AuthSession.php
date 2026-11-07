<?php

declare(strict_types=1);

namespace App\Http;

use App\Exception\UnauthorizedException;
use RuntimeException;

final class AuthSession
{
    private const COOKIE_NAME = 'gexusfilm_session';

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', '7200');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');

        session_name(self::COOKIE_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => session_cookie_secure(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        if (!session_start()) {
            throw new RuntimeException('Session could not be started.');
        }

        header('Cache-Control: no-store, private');
        header('Pragma: no-cache');
    }

    public function isAuthenticated(): bool
    {
        if (!isset($_COOKIE[self::COOKIE_NAME])) {
            return false;
        }

        $this->start();

        $userId = $_SESSION['user_id'] ?? null;
        $expiresAt = $_SESSION['expires_at'] ?? null;

        $isAuthenticated = is_int($userId) && $userId > 0 && is_int($expiresAt) && $expiresAt > time();
        if (!$isAuthenticated) {
            $_SESSION = [];
            session_destroy();
        }

        return $isAuthenticated;
    }

    public function csrfToken(): string
    {
        $this->start();
        if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public function verifyCsrf(string $token): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin !== '' && !is_allowed_request_origin($origin)) {
            throw new \App\Exception\ForbiddenException('Источник запроса не разрешён');
        }
        if ($origin === '' && app_environment() !== 'development') {
            throw new \App\Exception\ForbiddenException('Заголовок Origin обязателен');
        }

        $expected = $this->csrfToken();
        if ($token === '' || !hash_equals($expected, $token)) {
            throw new \App\Exception\ForbiddenException('Требуется действительный CSRF-токен');
        }
    }

    public function authenticate(int $userId): void
    {
        $this->start();
        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Session ID could not be rotated.');
        }

        $_SESSION = [
            'user_id' => $userId,
            'csrf_token' => bin2hex(random_bytes(32)),
            'expires_at' => time() + 7200,
        ];
    }

    public function requireUserId(): int
    {
        $this->start();
        $userId = $_SESSION['user_id'] ?? null;
        $expiresAt = $_SESSION['expires_at'] ?? null;
        if (!is_int($userId) || $userId < 1 || !is_int($expiresAt) || $expiresAt <= time()) {
            $_SESSION = [];
            session_destroy();
            throw new UnauthorizedException('Требуется вход в аккаунт');
        }

        return $userId;
    }

    public function logout(): void
    {
        $this->start();
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);
        session_destroy();
    }
}
