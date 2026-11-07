<?php

declare(strict_types=1);

namespace App\Tests;

use App\Exception\ValidationException;
use App\Service\UserMediaService;
use App\Tests\Support\UserMediaRepositoryStub;
use PHPUnit\Framework\TestCase;

final class UserMediaServiceTest extends TestCase
{
    public function testFavoriteIdentityIncludesUserAndMediaType(): void
    {
        $repository = new UserMediaRepositoryStub();
        $service = new UserMediaService($repository);

        $service->addFavorite(10, 'movie', 550);
        $service->addFavorite(10, 'movie', 550);
        $service->addFavorite(10, 'tv', 550);

        self::assertTrue($service->isFavorite(10, 'movie', 550));
        self::assertTrue($service->isFavorite(10, 'tv', 550));
        self::assertFalse($service->isFavorite(11, 'movie', 550));
        self::assertCount(2, $service->getFavorites(10));

        $service->removeFavorite(10, 'movie', 550);
        self::assertFalse($service->isFavorite(10, 'movie', 550));
        self::assertTrue($service->isFavorite(10, 'tv', 550));
    }

    public function testVisitHistoryUsesUserContextAndSupportsClear(): void
    {
        $repository = new UserMediaRepositoryStub();
        $service = new UserMediaService($repository);

        $service->recordVisit(25, 'tv', 550);
        self::assertSame([['user_id' => 25, 'media_type' => 'tv', 'source_id' => 550]], $repository->visits);

        $service->clearHistory(25);
        self::assertSame(25, $repository->clearedUserId);
    }

    public function testInvalidMediaIdentityIsRejected(): void
    {
        $service = new UserMediaService(new UserMediaRepositoryStub());

        $this->expectException(ValidationException::class);
        $service->addFavorite(10, 'person', 550);
    }
}
