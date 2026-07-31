<?php

declare(strict_types=1);

namespace App;

use Throwable;

final class Router
{
    /** @var array<string, array<string, callable>> Точные маршруты */
    private array $routes = [];

    /** @var array<string, list<array{pattern: string, regex: string, handler: callable}>> Параметризованные маршруты */
    private array $paramRoutes = [];

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
        if (str_contains($path, '{')) {
            // Преобразуем /api/movies/{id} в regex с именованными группами
            $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path);
            $regex = '#^' . $regex . '$#';

            $this->paramRoutes[$method][] = [
                'pattern' => $path,
                'regex'   => $regex,
                'handler' => $handler,
            ];
        } else {
            $this->routes[$method][$path] = $handler;
        }

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

        // 1. Сначала точное совпадение
        $handler = $this->routes[$method][$path] ?? null;

        if ($handler !== null) {
            $this->sendJsonHeaders();
            $this->callHandler($handler, $_GET);

            return;
        }

        // 2. Параметризованные маршруты
        $pathParams = [];
        $matched = $this->matchParamRoute($method, $path, $pathParams);

        if ($matched !== null) {
            $this->sendJsonHeaders();
            $params = array_merge($_GET, $pathParams);
            $this->callHandler($matched, $params);

            return;
        }

        // 3. 404
        $this->sendJsonHeaders();
        http_response_code(404);
        Response::json([
            'error' => [
                'code'    => 'NOT_FOUND',
                'message' => "Route {$method} {$path} not found",
            ],
        ]);
    }

    /**
     * Попытка сопоставить путь с параметризованными маршрутами.
     *
     * @param  string   $method HTTP-метод
     * @param  string   $path   Нормализованный путь запроса
     * @param  array    $params Выход: извлечённые path-параметры
     * @return callable|null     Найденный обработчик или null
     */
    private function matchParamRoute(string $method, string $path, ?array &$params): ?callable
    {
        $params = [];

        if (!isset($this->paramRoutes[$method])) {
            return null;
        }

        foreach ($this->paramRoutes[$method] as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                // Извлекаем только именованные группы (строковые ключи)
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return $route['handler'];
            }
        }

        return null;
    }

    /**
     * Безопасный вызов обработчика с параметрами.
     * PHP игнорирует лишние аргументы, если они не объявлены в обработчике.
     */
    private function callHandler(callable $handler, array $params): void
    {
        try {
            $handler($params);
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
