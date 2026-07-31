<?php

declare(strict_types=1);

namespace App\Tests;

use App\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testRegisteredRouteDispatches(): void
    {
        $router = new Router();
        $called = false;

        $router->get('/api/test', static function (array $params) use (&$called): void {
            $called = true;
        });

        $router->dispatch('GET', '/api/test');

        self::assertTrue($called);
    }

    public function testUnknownRouteReturns404(): void
    {
        $router = new Router();

        ob_start();
        $router->dispatch('GET', '/api/unknown');
        $output = (string) ob_get_clean();

        self::assertSame(404, http_response_code());
        self::assertStringContainsString('NOT_FOUND', $output);
    }

    public function testParameterizedRouteExtractsParams(): void
    {
        $router = new Router();
        $captured = [];

        $router->get('/api/movies/{id}', static function (array $params) use (&$captured): void {
            $captured = $params;
        });

        $router->dispatch('GET', '/api/movies/550');

        self::assertSame('550', $captured['id']);
    }

    public function testParameterizedRouteWithMultipleParams(): void
    {
        $router = new Router();
        $captured = [];

        $router->get('/api/tv-shows/{id}/season/{number}', static function (array $params) use (&$captured): void {
            $captured = $params;
        });

        $router->dispatch('GET', '/api/tv-shows/1399/season/1');

        self::assertSame('1399', $captured['id']);
        self::assertSame('1', $captured['number']);
    }

    public function testParameterizedRouteUnknownReturns404(): void
    {
        $router = new Router();

        $router->get('/api/movies/{id}', static function (array $params): void {
            // noop
        });

        ob_start();
        $router->dispatch('GET', '/api/movies/550/extra');
        $output = (string) ob_get_clean();

        self::assertSame(404, http_response_code());
        self::assertStringContainsString('NOT_FOUND', $output);
    }

    public function testExactRouteTakesPrecedenceOverParameterized(): void
    {
        $router = new Router();
        $captured = [];

        $router->get('/api/movies/popular', static function (array $params) use (&$captured): void {
            $captured = ['type' => 'exact'];
        });

        $router->get('/api/movies/{id}', static function (array $params) use (&$captured): void {
            $captured = ['type' => 'param', 'id' => $params['id']];
        });

        $router->dispatch('GET', '/api/movies/popular');

        self::assertSame('exact', $captured['type']);
    }

    public function testQueryParamsArePassedToHandler(): void
    {
        $router = new Router();
        $captured = [];

        // Simulate query string by setting $_GET manually
        $_GET = ['q' => 'dune', 'type' => 'movie'];

        $router->get('/api/search', static function (array $params) use (&$captured): void {
            $captured = $params;
        });

        $router->dispatch('GET', '/api/search');

        self::assertSame('dune', $captured['q']);
        self::assertSame('movie', $captured['type']);

        // Clean up
        $_GET = [];
    }
}
