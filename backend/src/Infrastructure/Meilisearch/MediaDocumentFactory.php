<?php

declare(strict_types=1);

namespace App\Infrastructure\Meilisearch;

use InvalidArgumentException;

final class MediaDocumentFactory
{
    /**
     * Создаёт source-agnostic документ из нормализованной записи хранилища.
     *
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    public function create(array $record): array
    {
        $source = $this->requiredSource($record);
        $sourceId = $this->requiredSourceId($record);
        $mediaType = $this->requiredMediaType($record);

        return [
            'id' => $source . ':' . $mediaType . ':' . $sourceId,
            'source' => $source,
            'source_id' => $sourceId,
            'media_type' => $mediaType,
            'title' => $this->nullableString($record['title'] ?? null),
            'original_title' => $this->nullableString($record['original_title'] ?? null),
            'overview' => $this->nullableString($record['overview'] ?? null),
            'year' => $this->year($record['release_date'] ?? null),
            'vote_average' => $this->nullableNumber($record['vote_average'] ?? null),
            'popularity' => $this->nullableNumber($record['popularity'] ?? null),
            'genres' => $this->genres($record),
        ];
    }

    /** @param array<string, mixed> $record */
    private function requiredSource(array $record): string
    {
        $source = strtolower(trim((string) ($record['source'] ?? '')));

        if ($source === '' || preg_match('/^[a-z0-9_-]+$/', $source) !== 1) {
            throw new InvalidArgumentException('Media source is invalid.');
        }

        return $source;
    }

    /** @param array<string, mixed> $record */
    private function requiredSourceId(array $record): int|string
    {
        $sourceId = $record['source_id'] ?? null;

        if (!is_int($sourceId) && !is_string($sourceId)) {
            throw new InvalidArgumentException('Media source_id is invalid.');
        }

        $sourceId = trim((string) $sourceId);

        if ($sourceId === '' || strlen($sourceId) > 128) {
            throw new InvalidArgumentException('Media source_id is invalid.');
        }

        if (preg_match('/^[a-zA-Z0-9._-]+$/', $sourceId) !== 1) {
            throw new InvalidArgumentException('Media source_id is invalid.');
        }

        if (!ctype_digit($sourceId)) {
            return $sourceId;
        }

        $normalized = ltrim($sourceId, '0');
        $normalized = $normalized === '' ? '0' : $normalized;
        $maximum = (string) PHP_INT_MAX;

        return strlen($normalized) < strlen($maximum)
            || (strlen($normalized) === strlen($maximum) && strcmp($normalized, $maximum) <= 0)
            ? (int) $normalized
            : $sourceId;
    }

    /** @param array<string, mixed> $record */
    private function requiredMediaType(array $record): string
    {
        $mediaType = strtolower(trim((string) ($record['media_type'] ?? '')));

        if (!in_array($mediaType, ['movie', 'tv'], true)) {
            throw new InvalidArgumentException('Media type is invalid.');
        }

        return $mediaType;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableNumber(mixed $value): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function year(mixed $releaseDate): ?int
    {
        if (!is_string($releaseDate) || preg_match('/^(\d{4})-\d{2}-\d{2}$/', $releaseDate, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    /** @param array<string, mixed> $record @return list<int|string> */
    private function genres(array $record): array
    {
        $genres = $record['genres'] ?? [];
        $genreIds = $record['genre_ids'] ?? [];
        $values = [];

        if (is_array($genreIds)) {
            foreach ($genreIds as $genreId) {
                if (is_int($genreId) || (is_string($genreId) && ctype_digit($genreId))) {
                    $values[] = (int) $genreId;
                }
            }
        }

        if (is_array($genres)) {
            foreach ($genres as $genre) {
                if (is_array($genre) && isset($genre['id']) && (is_int($genre['id']) || ctype_digit((string) $genre['id']))) {
                    $values[] = (int) $genre['id'];
                }
            }
        }

        return array_values(array_unique($values));
    }
}
