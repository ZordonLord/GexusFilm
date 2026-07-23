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

        $router->get('/api/test', static function () use (&$called): void {
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
}
