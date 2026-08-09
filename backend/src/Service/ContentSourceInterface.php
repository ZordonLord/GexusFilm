<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Абстракция источника данных о фильмах и сериалах.
 *
 * Позволяет сервисам работать с любым провайдером контента
 * (TMDB, Кинопоиск, мок для тестов и т.д.) через единый контракт.
 */
interface ContentSourceInterface
{
    // -- Movies --

    /**
     * Популярные фильмы.
     */
    public function getPopularMovies(): array;

    /**
     * Трендовые фильмы за день.
     */
    public function getTrendingMoviesDay(): array;

    /**
     * Фильмы, идущие сейчас в кинотеатрах.
     */
    public function getNowPlayingMovies(): array;

    /**
     * Ожидаемые премьеры.
     */
    public function getUpcomingMovies(): array;

    /**
     * Список жанров фильмов.
     */
    public function getMovieGenres(): array;

    /**
     * Поиск фильмов по параметрам (discover).
     */
    public function discoverMovies(array $params = []): array;

    /**
     * Детальная информация о фильме по его ID.
     */
    public function getMovie(int $id): array;

    /**
     * Поиск фильмов по текстовому запросу.
     */
    public function search(string $query, array $params = []): array;

    // -- TV Shows --

    /**
     * Трендовые сериалы за день.
     */
    public function getTrendingTvDay(): array;

    /**
     * Популярные сериалы.
     */
    public function getPopularTv(): array;

    /**
     * Сериалы, идущие в эфире.
     */
    public function getOnTheAirTv(): array;

    /**
     * Сериалы, выходящие сегодня.
     */
    public function getAiringTodayTv(): array;

    /**
     * Список жанров сериалов.
     */
    public function getTvGenres(): array;

    /**
     * Детальная информация о сериале по его ID.
     */
    public function getTv(int $id): array;

    /**
     * Информация о конкретном сезоне сериала.
     */
    public function getTvSeason(int $seriesId, int $seasonNumber): array;

    /**
     * Поиск сериалов по текстовому запросу.
     */
    public function searchTv(string $query, array $params = []): array;

    /**
     * Поиск сериалов по параметрам (discover).
     */
    public function discoverTv(array $params = []): array;
}
