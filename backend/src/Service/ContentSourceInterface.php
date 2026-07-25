<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Абстракция источника данных о фильмах.
 *
 * Позволяет MovieService работать с любым провайдером контента
 * (TMDB, Кинопоиск, мок для тестов и т.д.) через единый контракт.
 */
interface ContentSourceInterface
{
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
    public function search(string $query): array;
}
