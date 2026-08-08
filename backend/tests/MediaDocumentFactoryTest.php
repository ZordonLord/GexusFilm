<?php

declare(strict_types=1);

namespace App\Tests;

use App\Infrastructure\Meilisearch\MediaDocumentFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MediaDocumentFactoryTest extends TestCase
{
    public function testCreatesStableTmdbMovieDocumentAndNormalizesGenres(): void
    {
        $document = (new MediaDocumentFactory())->create([
            'source' => 'TMDB',
            'source_id' => '603',
            'media_type' => 'movie',
            'title' => 'Матрица',
            'release_date' => '1999-03-31',
            'vote_average' => '8.2',
            'genre_ids' => ['28', 878],
            'genres' => [['id' => 28, 'name' => 'Action']],
        ]);

        self::assertSame('tmdb:movie:603', $document['id']);
        self::assertSame('tmdb', $document['source']);
        self::assertSame(603, $document['source_id']);
        self::assertSame(1999, $document['year']);
        self::assertSame(8.2, $document['vote_average']);
        self::assertSame([28, 878], $document['genres']);
    }

    public function testSameSourceIdForDifferentMediaTypesHasDifferentKeys(): void
    {
        $factory = new MediaDocumentFactory();
        $movie = $factory->create(['source' => 'tmdb', 'source_id' => 603, 'media_type' => 'movie']);
        $tv = $factory->create(['source' => 'tmdb', 'source_id' => 603, 'media_type' => 'tv']);

        self::assertSame('tmdb:movie:603', $movie['id']);
        self::assertSame('tmdb:tv:603', $tv['id']);
    }

    public function testFactorySupportsAnotherSourceWithoutSourceSpecificBranch(): void
    {
        $document = (new MediaDocumentFactory())->create([
            'source' => 'kinopoisk',
            'source_id' => '12345',
            'media_type' => 'movie',
        ]);

        self::assertSame('kinopoisk:movie:12345', $document['id']);
        self::assertSame(12345, $document['source_id']);
    }

    public function testFactoryRejectsInvalidIdentity(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MediaDocumentFactory())->create([
            'source' => 'tmdb',
            'source_id' => 603,
            'media_type' => 'person',
        ]);
    }

    public function testFactoryPreservesLargeAndAlphanumericSourceIds(): void
    {
        $factory = new MediaDocumentFactory();
        $large = $factory->create([
            'source' => 'tmdb',
            'source_id' => '9223372036854775808',
            'media_type' => 'movie',
        ]);
        $external = $factory->create([
            'source' => 'imdb',
            'source_id' => 'tt0133093',
            'media_type' => 'movie',
        ]);

        self::assertSame('9223372036854775808', $large['source_id']);
        self::assertSame('imdb:movie:tt0133093', $external['id']);
    }

    public function testFactoryRejectsCompositeKeyDelimiterInSourceId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MediaDocumentFactory())->create([
            'source' => 'tmdb',
            'source_id' => '603:extra',
            'media_type' => 'movie',
        ]);
    }
}
