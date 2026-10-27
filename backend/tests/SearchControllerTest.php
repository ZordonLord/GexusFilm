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

    public function testSearchAcceptsAllAndPersonTypes(): void
    {
        foreach (['all', 'person'] as $type) {
            $service = $this->createMock(SearchServiceInterface::class);
            $service->expects(self::once())
                ->method('search')
                ->with('actor', self::callback(static fn (array $criteria): bool => $criteria['type'] === $type))
                ->willReturn(['page' => 1, 'results' => [], 'total_pages' => 0, 'total_results' => 0]);

            (new SearchController($service))->search(['q' => 'actor', 'type' => $type]);
        }
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

    public function testDiscoverAcceptsIncludedAndExcludedGenreIds(): void
    {
        $service = $this->createMock(SearchServiceInterface::class);
        $service->expects(self::once())
            ->method('discover')
            ->with([
                'type' => 'movie',
                'page' => 1,
                'per_page' => 20,
                'sort_by' => 'popularity.desc',
                'genre_ids' => [28, 12],
                'exclude_genre_ids' => [27],
            ])
            ->willReturn(['page' => 1, 'results' => [], 'total_pages' => 0, 'total_results' => 0]);

        (new SearchController($service))->discover([
            'type' => 'movie',
            'genre_ids' => '28,12',
            'exclude_genre_ids' => '27',
        ]);
    }

    public function testDiscoverAcceptsExtendedTmdbFilters(): void
    {
        $service = $this->createMock(SearchServiceInterface::class);
        $service->expects(self::once())
            ->method('discover')
            ->with([
                'type' => 'tv',
                'page' => 1,
                'per_page' => 20,
                'sort_by' => 'popularity.desc',
                'date_from' => '2026-01-01',
                'date_to' => '2026-08-23',
                'vote_count.gte' => 200,
                'language' => 'en',
                'country' => 'US',
                'watch_region' => 'RU',
                'with_watch_providers' => [8, 119],
                'with_watch_monetization_types' => ['flatrate', 'free'],
            ])
            ->willReturn(['page' => 1, 'results' => [], 'total_pages' => 0, 'total_results' => 0]);

        (new SearchController($service))->discover([
            'type' => 'tv',
            'date_from' => '2026-01-01',
            'date_to' => '2026-08-23',
            'vote_count_gte' => '200',
            'language' => 'EN',
            'country' => 'us',
            'watch_region' => 'ru',
            'with_watch_providers' => '8,119',
            'with_watch_monetization_types' => 'flatrate,free',
        ]);
    }

    public function testDiscoverAcceptsYearRangeAndNormalizesIt(): void
    {
        $service = $this->createMock(SearchServiceInterface::class);
        $service->expects(self::once())
            ->method('discover')
            ->with([
                'type' => 'movie',
                'page' => 1,
                'per_page' => 20,
                'sort_by' => 'popularity.desc',
                'year_from' => 2020,
                'year_to' => 2024,
            ])
            ->willReturn(['page' => 1, 'results' => [], 'total_pages' => 0, 'total_results' => 0]);

        (new SearchController($service))->discover([
            'year_from' => '2020',
            'year_to' => '2024',
        ]);
    }

    public function testDiscoverReleasedOnlyClampsDateToToday(): void
    {
        $service = $this->createMock(SearchServiceInterface::class);
        $service->expects(self::once())
            ->method('discover')
            ->with(self::callback(function (array $criteria): bool {
                return ($criteria['date_to'] ?? null) === (new \DateTimeImmutable('today'))->format('Y-m-d');
            }))
            ->willReturn(['page' => 1, 'results' => [], 'total_pages' => 0, 'total_results' => 0]);

        (new SearchController($service))->discover([
            'released_only' => '1',
            'year_to' => '2100',
        ]);
    }

    public function testDiscoverCanIncludeFutureReleasesWhenReleasedOnlyIsDisabled(): void
    {
        $service = $this->createMock(SearchServiceInterface::class);
        $service->expects(self::once())
            ->method('discover')
            ->with(self::callback(function (array $criteria): bool {
                return ($criteria['year_to'] ?? null) === 2100 && !array_key_exists('date_to', $criteria);
            }))
            ->willReturn(['page' => 1, 'results' => [], 'total_pages' => 0, 'total_results' => 0]);

        (new SearchController($service))->discover([
            'released_only' => '0',
            'year_to' => '2100',
        ]);
    }

    public function testDiscoverRejectsProviderFiltersWithoutWatchRegion(): void
    {
        $service = $this->createMock(SearchServiceInterface::class);

        $this->expectException(ValidationException::class);
        (new SearchController($service))->discover(['with_watch_providers' => '8']);
    }

    public function testDiscoverRejectsInvalidExtendedFilters(): void
    {
        $service = $this->createMock(SearchServiceInterface::class);
        $controller = new SearchController($service);

        $invalidFilters = [
            ['date_from' => '2026-02-30'],
            ['date_from' => '2026-08-24', 'date_to' => '2026-08-23'],
            ['vote_count.gte' => '-1'],
            ['language' => 'english'],
            ['country' => 'RUS'],
            ['watch_region' => 'RUS'],
            ['watch_region' => 'RU', 'with_watch_monetization_types' => 'unknown'],
        ];

        foreach ($invalidFilters as $params) {
            try {
                $controller->discover($params);
                self::fail('Expected invalid filter exception.');
            } catch (ValidationException) {
                self::assertTrue(true);
            }
        }
    }

    public function testGenreMatchIsRejected(): void
    {
        $service = $this->createMock(SearchServiceInterface::class);

        $this->expectException(ValidationException::class);
        (new SearchController($service))->discover([
            'genre_ids' => '28,12',
            'genre_match' => 'and',
        ]);
    }

    public function testDiscoverRejectsDuplicateGenreIds(): void
    {
        $service = $this->createMock(SearchServiceInterface::class);

        $this->expectException(ValidationException::class);
        (new SearchController($service))->discover(['genre_ids' => '28,28']);
    }

    public function testDiscoverRejectsGenreIncludedAndExcludedAtTheSameTime(): void
    {
        $service = $this->createMock(SearchServiceInterface::class);

        $this->expectException(ValidationException::class);
        (new SearchController($service))->discover([
            'genre_ids' => '28,12',
            'exclude_genre_ids' => '27,12',
        ]);
    }

    public function testUnsupportedTypeAndUnknownParameterAreRejected(): void
    {
        $service = $this->createMock(SearchServiceInterface::class);
        $controller = new SearchController($service);

        try {
            $controller->search(['q' => 'matrix', 'type' => 'unknown']);
            self::fail('Expected invalid type exception.');
        } catch (ValidationException $exception) {
            self::assertSame('type', $exception->details[0]['field']);
        }

        $this->expectException(ValidationException::class);
        $controller->search(['q' => 'matrix', 'unexpected' => 'value']);
    }
}
