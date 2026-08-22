<?php

declare(strict_types=1);

namespace App\Tests;

use App\Exception\ValidationException;
use App\Http\Controllers\SearchController;
use App\Service\SearchServiceInterface;
use PHPUnit\Framework\TestCase;

final class SearchControllerTest extends TestCase
{
    public function testSearchAcceptsFiltersAndPassesV2CompatibleRequestToService(): void
    {
        $service = $this->createMock(SearchServiceInterface::class);
        $service->expects(self::once())
            ->method('search')
            ->with('matrix', [
                'type' => 'movie',
                'page' => 2,
                'per_page' => 10,
                'sort_by' => 'vote_average.desc',
                'year' => 1999,
                'min_rating' => 8.5,
                'region' => 'RU',
            ])
            ->willReturn(['page' => 2, 'results' => [], 'total_pages' => 0, 'total_results' => 0]);

        $result = (new SearchController($service))->search([
            'q' => ' matrix ',
            'page' => '2',
            'per_page' => '10',
            'sort_by' => 'vote_average.desc',
            'year' => '1999',
            'min_rating' => '8.5',
            'region' => 'ru',
        ]);

        self::assertSame(2, $result['page']);
    }

    public function testInvalidPaginationIsRejected(): void
    {
        $service = $this->createMock(SearchServiceInterface::class);
        $controller = new SearchController($service);

        $this->expectException(ValidationException::class);
        $controller->discover(['page' => '0']);
    }

    public function testDiscoverAcceptsUpToFiveUniqueGenreIds(): void
    {
        $service = $this->createMock(SearchServiceInterface::class);
        $service->expects(self::once())
            ->method('discover')
            ->with([
                'type' => 'movie',
                'page' => 1,
                'per_page' => 20,
                'sort_by' => 'popularity.desc',
                'genre_ids' => [28, 12, 878],
            ])
            ->willReturn(['page' => 1, 'results' => [], 'total_pages' => 0, 'total_results' => 0]);

        (new SearchController($service))->discover([
            'type' => 'movie',
            'genre_ids' => '28,12,878',
        ]);
    }

    public function testDiscoverRejectsDuplicateGenreIds(): void
    {
        $service = $this->createMock(SearchServiceInterface::class);

        $this->expectException(ValidationException::class);
        (new SearchController($service))->discover(['genre_ids' => '28,28']);
    }

    public function testPersonTypeAndUnknownParameterAreRejected(): void
    {
        $service = $this->createMock(SearchServiceInterface::class);
        $controller = new SearchController($service);

        try {
            $controller->search(['q' => 'matrix', 'type' => 'person']);
            self::fail('Expected invalid type exception.');
        } catch (ValidationException $exception) {
            self::assertSame('type', $exception->details[0]['field']);
        }

        $this->expectException(ValidationException::class);
        $controller->search(['q' => 'matrix', 'unexpected' => 'value']);
    }
}
