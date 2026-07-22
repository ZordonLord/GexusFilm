<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/routes.php';

$router = createRouter();
$router->dispatch('GET', '/api/search');
