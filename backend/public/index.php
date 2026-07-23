<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/routes.php';

$requestUri = $_SERVER['REQUEST_URI'];
$parsed = parse_url($requestUri);
$path = $parsed['path'] ?? '/';
$query = $parsed['query'] ?? '';

$path = preg_replace('#\.php$#', '', $path) ?? $path;

if ($query !== '') {
    $_SERVER['REQUEST_URI'] = $path . '?' . $query;
}

$router = createRouter();
$router->dispatch($_SERVER['REQUEST_METHOD'], $path);
