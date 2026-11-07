<?php

declare(strict_types=1);

namespace App\Tests;

use App\Database;
use App\Repository\UserMediaRepository;
use PHPUnit\Framework\TestCase;
use Throwable;

final class UserMediaRepositoryTest extends TestCase
{
    private ?\PDO $pdo = null;
    private int $firstUserId = 0;
    private int $secondUserId = 0;

    protected function setUp(): void
    {
        parent::setUp();
        try {
            $this->pdo = Database::connect();
            $suffix = bin2hex(random_bytes(8));
            $insert = $this->pdo->prepare(
                'INSERT INTO users (email, password_hash) VALUES (:email, :hash) RETURNING id'
            );
            foreach (["media-a-$suffix@example.invalid", "media-b-$suffix@example.invalid"] as $index => $email) {
                $insert->execute(['email' => $email, 'hash' => password_hash('fixture-only', PASSWORD_ARGON2ID)]);
                $userId = (int) $insert->fetchColumn();
                if ($index === 0) {
                    $this->firstUserId = $userId;
                } else {
                    $this->secondUserId = $userId;
                }
            }
        } catch (Throwable $exception) {
            self::markTestSkipped('PostgreSQL integration database is not available.');
        }
    }

    protected function tearDown(): void
    {
        if ($this->pdo !== null && $this->firstUserId > 0 && $this->secondUserId > 0) {
            $statement = $this->pdo->prepare('DELETE FROM users WHERE id = :first OR id = :second');
            $statement->execute([
                'first' => $this->firstUserId,
                'second' => $this->secondUserId,
            ]);
        }
        parent::tearDown();
    }

    public function testFavoritesAreIdempotentAndIsolatedByOwnerAndMediaType(): void
    {
        $repository = new UserMediaRepository($this->pdo);
        $repository->add($this->firstUserId, 'favorites', 'movie', 550);
        $repository->add($this->firstUserId, 'favorites', 'movie', 550);
        $repository->add($this->firstUserId, 'favorites', 'tv', 550);

        self::assertTrue($repository->contains($this->firstUserId, 'favorites', 'movie', 550));
        self::assertTrue($repository->contains($this->firstUserId, 'favorites', 'tv', 550));
        self::assertFalse($repository->contains($this->secondUserId, 'favorites', 'movie', 550));
        self::assertCount(2, $repository->getList($this->firstUserId, 'favorites'));

        $repository->remove($this->firstUserId, 'favorites', 'movie', 550);
        self::assertFalse($repository->contains($this->firstUserId, 'favorites', 'movie', 550));
        self::assertTrue($repository->contains($this->firstUserId, 'favorites', 'tv', 550));
    }

    public function testHistoryDeduplicatesPerUserAndUpdatesLastVisit(): void
    {
        $repository = new UserMediaRepository($this->pdo);
        $repository->recordVisit($this->firstUserId, 'movie', 550);
        $firstViewedAt = $repository->getHistory($this->firstUserId)[0]['last_viewed_at'];
        sleep(1);
        $repository->recordVisit($this->firstUserId, 'movie', 550);

        $history = $repository->getHistory($this->firstUserId);
        self::assertCount(1, $history);
        self::assertGreaterThan(strtotime($firstViewedAt), strtotime($history[0]['last_viewed_at']));
        self::assertCount(0, $repository->getHistory($this->secondUserId));

        $repository->clearHistory($this->firstUserId);
        self::assertCount(0, $repository->getHistory($this->firstUserId));
    }
}
