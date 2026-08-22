<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exception\ValidationException;
use App\Service\SearchServiceInterface;

final class SearchController
{
    private const ALLOWED_SORTS = [
        'popularity.desc', 'popularity.asc',
        'vote_average.desc', 'vote_average.asc',
        'year.desc', 'year.asc',
    ];

    public function __construct(private SearchServiceInterface $searchService)
    {
    }

    /** @param array<string, mixed> $params */
    public function search(array $params): array
    {
        $this->assertKnownParameters($params, ['q', 'type', 'page', 'per_page', 'year', 'min_rating', 'region', 'sort_by']);
        $query = $this->stringParameter($params, 'q', true, 200);

        return $this->searchService->search($query, $this->criteria($params));
    }

    /** @param array<string, mixed> $params */
    public function discover(array $params): array
    {
        $this->assertKnownParameters($params, ['type', 'genre_id', 'genre_ids', 'genre', 'year', 'min_rating', 'region', 'sort_by', 'page', 'per_page']);

        if (isset($params['genre'], $params['genre_id']) || isset($params['genre'], $params['genre_ids'])) {
            throw new ValidationException('Параметры запроса некорректны', [
                ['field' => 'genre', 'issue' => 'genre cannot be used with genre_id or genre_ids'],
            ]);
        }

        if (isset($params['genre']) && !isset($params['genre_id']) && !isset($params['genre_ids'])) {
            $params['genre_id'] = $params['genre'];
        }

        if (isset($params['genre_id'], $params['genre_ids'])) {
            throw new ValidationException('Параметры запроса некорректны', [
                ['field' => 'genre_ids', 'issue' => 'genre_id and genre_ids cannot be used together'],
            ]);
        }

        return $this->searchService->discover($this->criteria($params, false));
    }

    /** @param array<string, mixed> $params @param list<string> $allowed */
    private function assertKnownParameters(array $params, array $allowed): void
    {
        $unknown = array_diff(array_keys($params), $allowed);

        if ($unknown !== []) {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => (string) reset($unknown),
                'issue' => 'unknown parameter',
            ]]);
        }
    }

    /** @param array<string, mixed> $params @return array<string, mixed> */
    private function criteria(array $params, bool $search = true): array
    {
        $type = $this->stringParameter($params, 'type') ?: 'movie';

        if (!in_array($type, ['movie', 'tv'], true)) {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => 'type', 'issue' => 'must be movie or tv',
            ]]);
        }

        $criteria = [
            'type' => $type,
            'page' => $this->integerParameter($params, 'page', 1, 1),
            'per_page' => $this->integerParameter($params, 'per_page', 20, 1, 50),
            'sort_by' => $this->stringParameter($params, 'sort_by') ?: 'popularity.desc',
        ];

        if (!in_array($criteria['sort_by'], self::ALLOWED_SORTS, true)) {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => 'sort_by', 'issue' => 'unsupported sort field',
            ]]);
        }

        foreach (['genre_id' => [1, null], 'year' => [1870, 2100]] as $field => [$minimum, $maximum]) {
            if (array_key_exists($field, $params)) {
                $criteria[$field] = $this->integerParameter($params, $field, null, $minimum, $maximum);
            }
        }

        if (array_key_exists('genre_ids', $params)) {
            $criteria['genre_ids'] = $this->integerListParameter($params, 'genre_ids', 5);
        }

        if (array_key_exists('min_rating', $params)) {
            $criteria['min_rating'] = $this->numberParameter($params, 'min_rating', 0, 10);
        }

        if (array_key_exists('region', $params)) {
            $region = strtoupper($this->stringParameter($params, 'region'));
            if (preg_match('/^[A-Z]{2}$/', $region) !== 1) {
                throw new ValidationException('Параметры запроса некорректны', [[
                    'field' => 'region', 'issue' => 'must be an ISO 3166-1 alpha-2 code',
                ]]);
            }
            $criteria['region'] = $region;
        }

        if (!$search && isset($criteria['genre_id']) && $criteria['genre_id'] <= 0) {
            throw new ValidationException('Параметры запроса некорректны');
        }

        return $criteria;
    }

    private function stringParameter(array $params, string $field, bool $required = false, int $maxLength = 100): string
    {
        $value = $params[$field] ?? null;
        if (!is_string($value)) {
            if ($required || $value !== null) {
                throw new ValidationException('Параметры запроса некорректны', [[
                    'field' => $field, 'issue' => 'must be a string',
                ]]);
            }
            return '';
        }
        $value = trim($value);
        if (($required && $value === '') || strlen($value) > $maxLength) {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => $field, 'issue' => $required && $value === '' ? 'must not be empty' : 'is too long',
            ]]);
        }
        return $value;
    }

    private function integerParameter(array $params, string $field, ?int $default, ?int $minimum = null, ?int $maximum = null): int
    {
        if (!array_key_exists($field, $params)) {
            return $default ?? 0;
        }
        $value = $params[$field];
        if (!is_string($value) && !is_int($value)) {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => $field, 'issue' => 'must be an integer',
            ]]);
        }
        $text = (string) $value;
        if (preg_match('/^(?:0|[1-9]\d*)$/', $text) !== 1) {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => $field, 'issue' => 'must be a positive integer',
            ]]);
        }
        $number = (int) $text;
        if (($minimum !== null && $number < $minimum) || ($maximum !== null && $number > $maximum)) {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => $field, 'issue' => 'is outside the allowed range',
            ]]);
        }
        return $number;
    }

    private function numberParameter(array $params, string $field, float $minimum, float $maximum): float
    {
        $value = $params[$field] ?? null;
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => $field, 'issue' => 'must be a number',
            ]]);
        }
        if (!is_numeric((string) $value) || (float) $value < $minimum || (float) $value > $maximum) {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => $field, 'issue' => 'is outside the allowed range',
            ]]);
        }
        return (float) $value;
    }

    /** @return list<int> */
    private function integerListParameter(array $params, string $field, int $maximumItems): array
    {
        $value = $params[$field] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => $field, 'issue' => 'must be a comma-separated list of positive integers',
            ]]);
        }

        $parts = array_map('trim', explode(',', $value));
        if (count($parts) < 1 || count($parts) > $maximumItems || count($parts) !== count(array_unique($parts))) {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => $field, 'issue' => 'must contain 1 to 5 unique genre IDs',
            ]]);
        }

        $genreIds = [];
        foreach ($parts as $part) {
            if (preg_match('/^[1-9]\d*$/', $part) !== 1) {
                throw new ValidationException('Параметры запроса некорректны', [[
                    'field' => $field, 'issue' => 'must contain positive integers only',
                ]]);
            }
            $genreIds[] = (int) $part;
        }

        return $genreIds;
    }
}
