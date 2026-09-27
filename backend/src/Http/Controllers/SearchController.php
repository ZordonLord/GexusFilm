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
        $this->assertKnownParameters($params, [
            'type', 'genre_id', 'genre_ids', 'exclude_genre_ids', 'genre', 'year', 'year_from', 'year_to', 'min_rating', 'region',
            'date_from', 'date_to', 'vote_count.gte', 'vote_count_gte', 'language', 'country', 'watch_region',
            'with_watch_providers', 'with_watch_monetization_types', 'sort_by', 'page', 'per_page',
        ]);

        if (isset($params['genre'], $params['genre_id']) || isset($params['genre'], $params['genre_ids']) || isset($params['genre'], $params['exclude_genre_ids'])) {
            throw new ValidationException('Параметры запроса некорректны', [
                ['field' => 'genre', 'issue' => 'genre cannot be used with included or excluded genre IDs'],
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

        if (isset($params['year'], $params['year_from']) || isset($params['year'], $params['year_to'])) {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => 'year_from', 'issue' => 'year cannot be used with year_from or year_to',
            ]]);
        }

        $yearFrom = null;
        $yearTo = null;
        foreach (['year_from', 'year_to'] as $field) {
            if (!array_key_exists($field, $params)) {
                continue;
            }

            $year = $this->integerParameter($params, $field, null, 1870, 2100);
            if ($field === 'year_from') {
                $yearFrom = $year;
            } else {
                $yearTo = $year;
            }
        }
        if ($yearFrom !== null && $yearTo !== null && $yearFrom > $yearTo) {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => 'year_from', 'issue' => 'must not be later than year_to',
            ]]);
        }
        if ($yearFrom !== null) {
            $criteria['year_from'] = $yearFrom;
        }
        if ($yearTo !== null) {
            $criteria['year_to'] = $yearTo;
        }

        if (array_key_exists('genre_ids', $params)) {
            $criteria['genre_ids'] = $this->integerListParameter($params, 'genre_ids', 5);
        }

        if (array_key_exists('exclude_genre_ids', $params)) {
            $criteria['exclude_genre_ids'] = $this->integerListParameter($params, 'exclude_genre_ids', 5);
        }

        $includedGenreIds = $criteria['genre_ids'] ?? (isset($criteria['genre_id']) ? [$criteria['genre_id']] : []);
        $excludedGenreIds = $criteria['exclude_genre_ids'] ?? [];
        if (array_intersect($includedGenreIds, $excludedGenreIds) !== []) {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => 'exclude_genre_ids', 'issue' => 'a genre cannot be included and excluded at the same time',
            ]]);
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

        $dateFrom = null;
        $dateTo = null;
        foreach (['date_from', 'date_to'] as $field) {
            if (!array_key_exists($field, $params)) {
                continue;
            }

            $date = $this->stringParameter($params, $field, false, 10);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
                throw new ValidationException('Параметры запроса некорректны', [[
                    'field' => $field, 'issue' => 'must be an ISO 8601 date (YYYY-MM-DD)',
                ]]);
            }

            $dateObject = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if ($dateObject === false || $dateObject->format('Y-m-d') !== $date) {
                throw new ValidationException('Параметры запроса некорректны', [[
                    'field' => $field, 'issue' => 'must be a valid calendar date',
                ]]);
            }

            if ($field === 'date_from') {
                $dateFrom = $date;
            } else {
                $dateTo = $date;
            }
        }
        if ($dateFrom !== null && $dateTo !== null && $dateFrom > $dateTo) {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => 'date_from', 'issue' => 'must not be later than date_to',
            ]]);
        }
        if ($dateFrom !== null) {
            $criteria['date_from'] = $dateFrom;
        }
        if ($dateTo !== null) {
            $criteria['date_to'] = $dateTo;
        }

        if (isset($params['vote_count.gte'], $params['vote_count_gte'])) {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => 'vote_count_gte', 'issue' => 'vote_count.gte and vote_count_gte cannot be used together',
            ]]);
        }
        $voteCountField = array_key_exists('vote_count.gte', $params) ? 'vote_count.gte' : 'vote_count_gte';
        if (array_key_exists($voteCountField, $params)) {
            $criteria['vote_count.gte'] = $this->integerParameter($params, $voteCountField, null, 0, 1000000000);
        }

        if (array_key_exists('language', $params)) {
            $language = strtolower($this->stringParameter($params, 'language', false, 2));
            if (preg_match('/^[a-z]{2}$/', $language) !== 1) {
                throw new ValidationException('Параметры запроса некорректны', [[
                    'field' => 'language', 'issue' => 'must be an ISO 639-1 code',
                ]]);
            }
            $criteria['language'] = $language;
        }

        if (array_key_exists('country', $params)) {
            $criteria['country'] = $this->isoRegionParameter($params, 'country');
        }

        if (array_key_exists('watch_region', $params)) {
            $criteria['watch_region'] = $this->isoRegionParameter($params, 'watch_region');
        }

        if (array_key_exists('with_watch_providers', $params)) {
            $criteria['with_watch_providers'] = $this->positiveIntegerListParameter(
                $params,
                'with_watch_providers',
                5,
                'must contain 1 to 5 unique provider IDs',
            );
        }

        if (array_key_exists('with_watch_monetization_types', $params)) {
            $criteria['with_watch_monetization_types'] = $this->stringListParameter(
                $params,
                'with_watch_monetization_types',
                ['flatrate', 'free', 'ads', 'rent', 'buy'],
                5,
            );
        }

        $hasWatchFilters = isset($criteria['with_watch_providers'], $criteria['with_watch_monetization_types'])
            || array_key_exists('with_watch_providers', $criteria)
            || array_key_exists('with_watch_monetization_types', $criteria);
        if ($hasWatchFilters && !isset($criteria['watch_region'])) {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => 'watch_region', 'issue' => 'is required when watch provider filters are used',
            ]]);
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

    private function isoRegionParameter(array $params, string $field): string
    {
        $region = strtoupper($this->stringParameter($params, $field, false, 2));
        if (preg_match('/^[A-Z]{2}$/', $region) !== 1) {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => $field, 'issue' => 'must be an ISO 3166-1 alpha-2 code',
            ]]);
        }

        return $region;
    }

    /** @return list<int> */
    private function integerListParameter(array $params, string $field, int $maximumItems): array
    {
        return $this->positiveIntegerListParameter(
            $params,
            $field,
            $maximumItems,
            'must contain 1 to 5 unique genre IDs',
        );
    }

    /** @return list<int> */
    private function positiveIntegerListParameter(
        array $params,
        string $field,
        int $maximumItems,
        string $countIssue,
    ): array {
        $value = $params[$field] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => $field, 'issue' => 'must be a comma-separated list of positive integers',
            ]]);
        }

        $parts = array_map('trim', explode(',', $value));
        if (count($parts) < 1 || count($parts) > $maximumItems || count($parts) !== count(array_unique($parts))) {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => $field, 'issue' => $countIssue,
            ]]);
        }

        $ids = [];
        foreach ($parts as $part) {
            if (preg_match('/^[1-9]\d*$/', $part) !== 1) {
                throw new ValidationException('Параметры запроса некорректны', [[
                    'field' => $field, 'issue' => 'must contain positive integers only',
                ]]);
            }
            $ids[] = (int) $part;
        }

        return $ids;
    }

    /** @param list<string> $allowedValues @return list<string> */
    private function stringListParameter(array $params, string $field, array $allowedValues, int $maximumItems): array
    {
        $value = $params[$field] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => $field, 'issue' => 'must be a comma-separated list of supported values',
            ]]);
        }

        $parts = array_map(static fn (string $part): string => strtolower(trim($part)), explode(',', $value));
        if (count($parts) < 1 || count($parts) > $maximumItems || count($parts) !== count(array_unique($parts))) {
            throw new ValidationException('Параметры запроса некорректны', [[
                'field' => $field, 'issue' => 'must contain 1 to 5 unique values',
            ]]);
        }

        foreach ($parts as $part) {
            if (!in_array($part, $allowedValues, true)) {
                throw new ValidationException('Параметры запроса некорректны', [[
                    'field' => $field, 'issue' => 'contains an unsupported value',
                ]]);
            }
        }

        return $parts;
    }
}
