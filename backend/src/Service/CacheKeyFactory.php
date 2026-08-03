<?php

declare(strict_types=1);

namespace App\Service;

final class CacheKeyFactory
{
    public static function catalog(string $mediaType, string $operation): string
    {
        return "cache:catalog:$mediaType:$operation";
    }

    public static function details(string $mediaType, int $id): string
    {
        return "cache:details:$mediaType:$id";
    }

    public static function season(int $seriesId, int $seasonNumber): string
    {
        return "cache:season:tv:$seriesId:$seasonNumber";
    }

    public static function search(string $mediaType, string $query): string
    {
        $normalizedQuery = mb_strtolower(trim($query));

        return 'cache:search:' . $mediaType . ':' . hash('sha256', $normalizedQuery);
    }

    public static function discover(string $mediaType, int $genreId, int $page): string
    {
        return "cache:discover:$mediaType:genre:$genreId:page:$page";
    }

    public static function genres(string $mediaType): string
    {
        return "cache:genres:$mediaType";
    }

    public static function rateLimit(string $clientId, string $bucket, int $windowSeconds): string
    {
        return 'rate-limit:' . $clientId . ":$bucket:" . intdiv(time(), $windowSeconds);
    }
}
