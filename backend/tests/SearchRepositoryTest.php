<?php

declare(strict_types=1);

namespace App\Tests;

use App\Repository\SearchRepository;
use App\Tests\Support\InMemoryMeilisearchGateway;
use PHPUnit\Framework\TestCase;

final class SearchRepositoryTest extends TestCase
{
    public function testMeilisearchResultsUseStableV2ResponseAndMediaFields(): void
    {
        $gateway = new InMemoryMeilisearchGateway();
        $gateway->upsertDocuments('media', [[
            'id' => 'tmdb:movie:603',
            'source_id' => 603,
            'media_type' => 'movie',
            'title' => 'The Matrix',
            'release_date' => '1999-03-30',
            'genres' => [28],
        ]]);

        $repository = new SearchRepository(null, $gateway);
        $result = $repository->search('matrix', [
            'type' => 'movie',
            'page' => 1,
            'per_page' => 20,
            'sort_by' => 'popularity.desc',
        ]);

        self::assertSame(['page', 'results', 'total_pages', 'total_results'], array_keys($result));
        self::assertSame(603, $result['results'][0]['id']);
        self::assertSame('The Matrix', $result['results'][0]['title']);
        self::assertSame('movie', $result['results'][0]['media_type']);
    }
}
