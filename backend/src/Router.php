<?php

declare(strict_types=1);

namespace App;

use function json_encode;
use Throwable;

final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    public function get(string $path, callable $handler): self
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): self
    {
        return $this->add('POST', $path, $handler);
    }

    public function options(string $path, callable $handler): self
    {
        return $this->add('OPTIONS', $path, $handler);
    }

    private function add(string $method, string $path, callable $handler): self
    {
        $this->routes[$method][$path] = $handler;

        return $this;
    }

    public function dispatch(string $method, string $path): void
    {
        $path = $this->normalizePath($path);

        if ($method === 'OPTIONS') {
            $this->sendCorsHeaders();
            http_response_code(204);

            return;
        }

        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            $this->sendJsonHeaders();
            http_response_code(404);
            Response::json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => "Route {$method} {$path} not found",
                ],
            ]);

            return;
        }

        $this->sendJsonHeaders();
        
        try {
            $handler();
        } catch (Throwable $exception) {
            \App\Http\ExceptionHandler::handle($exception);
        }
    }

    private function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: $path;
        $path = rtrim($path, '/');

        if ($path === '') {
            return '/';
        }

        return $path;
    }

    private function sendJsonHeaders(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->sendCorsHeaders();
    }

    private function sendCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }
}
