<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\AuthSession;
use App\Service\AuthService;

final class AuthController
{
    public function __construct(private AuthService $auth, private AuthSession $session)
    {
    }

    public function csrf(): array
    {
        return ['csrf_token' => $this->session->csrfToken()];
    }

    public function currentUser(): array
    {
        if (!$this->session->isAuthenticated()) {
            return ['user' => null];
        }

        return $this->profile();
    }

    /** @param array<string, mixed> $input */
    public function register(array $input, string $csrfToken): array
    {
        $this->session->verifyCsrf($csrfToken);
        $this->auth->register($input);

        return [
            'status' => 'accepted',
            'message' => 'Если аккаунт создан, войдите с указанными данными.',
        ];
    }

    /** @param array<string, mixed> $input */
    public function login(array $input, string $csrfToken): array
    {
        $this->session->verifyCsrf($csrfToken);
        $user = $this->auth->login($input);
        $this->session->authenticate($user['id']);

        return ['user' => $user, 'csrf_token' => $this->session->csrfToken()];
    }

    public function profile(): array
    {
        return ['user' => $this->auth->getProfile($this->session->requireUserId())];
    }

    public function logout(string $csrfToken): array
    {
        $this->session->verifyCsrf($csrfToken);
        $this->session->logout();

        return ['status' => 'ok'];
    }
}
