// Страница каталога — все фильмы и сериалы с фильтрацией

import { useEffect, useState } from "react";

import Header from "../components/Header";
import Sidebar from "../components/Sidebar";
import Footer from "../components/Footer";
import MediaCard from "../components/MediaCard";
import SkeletonCard from "../components/SkeletonCard";

import {
  getDiscoverMovies,
  getDiscoverTv,
  getMovieGenres,
  getTvGenres,
} from "../services/api";

import "../styles/CatalogPage.css";

const MEDIA_TABS = [
  { key: "all", label: "Всё" },
  { key: "movie", label: "Фильмы" },
  { key: "tv", label: "Сериалы" },
];

export default function CatalogPage() {
  const [mediaType, setMediaType] = useState("all");
  const [items, setItems] = useState([]);
  const [genres, setGenres] = useState([]);
  const [selectedGenre, setSelectedGenre] = useState(null);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  // Загрузка жанров
  useEffect(() => {
    let cancelled = false;

    async function loadGenres() {
      try {
        const [movieGenres, tvGenres] = await Promise.all([
          getMovieGenres(),
          getTvGenres(),
        ]);

        if (cancelled) return;

        // Объединяем жанры, убираем дубликаты по id
        const allGenres = [...(movieGenres.genres || []), ...(tvGenres.genres || [])];
        const unique = new Map();
        allGenres.forEach((g) => unique.set(g.id, g));
        setGenres([...unique.values()]);
      } catch (error) {
        console.error("Ошибка загрузки жанров:", error);
      }
    }

    loadGenres();
    return () => { cancelled = true; };
  }, []);

  // Загрузка контента
  useEffect(() => {
    let cancelled = false;

    async function loadContent() {
      setLoading(true);

      try {
        const params = { page };
        if (selectedGenre) {
          params.genre_id = selectedGenre;
        }

        let data;

        if (mediaType === "all") {
          // Загружаем и фильмы, и сериалы, объединяем
          const [moviesData, tvData] = await Promise.all([
            getDiscoverMovies(params),
            getDiscoverTv(params),
          ]);

          if (cancelled) return;

          const movies = (moviesData.results || []).map((item) => ({
            ...item,
            media_type: "movie",
          }));
          const tv = (tvData.results || []).map((item) => ({
            ...item,
            media_type: "tv",
          }));

          // Чередуем фильмы и сериалы для разнообразия
          const merged = [];
          const maxLen = Math.max(movies.length, tv.length);
          for (let i = 0; i < maxLen; i++) {
            if (i < movies.length) merged.push(movies[i]);
            if (i < tv.length) merged.push(tv[i]);
          }

          data = { results: merged, total_pages: Math.max(moviesData.total_pages || 1, tvData.total_pages || 1) };
        } else if (mediaType === "movie") {
          data = await getDiscoverMovies(params);
        } else {
          data = await getDiscoverTv(params);
        }

        if (cancelled) return;

        setItems(data.results || []);
        setTotalPages(data.total_pages || 1);
      } catch (error) {
        console.error("Ошибка загрузки каталога:", error);
        if (!cancelled) setItems([]);
      } finally {
        if (!cancelled) setLoading(false);
      }
    }

    loadContent();
    return () => { cancelled = true; };
  }, [mediaType, selectedGenre, page]);

  // Сброс страницы при смене фильтров
  const handleMediaTypeChange = (type) => {
    setMediaType(type);
    setPage(1);
  };

  const handleGenreChange = (genreId) => {
    setSelectedGenre(genreId === selectedGenre ? null : genreId);
    setPage(1);
  };

  const handlePrevPage = () => {
    setPage((p) => Math.max(1, p - 1));
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const handleNextPage = () => {
    setPage((p) => Math.min(totalPages, p + 1));
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  return (
    <div className="flex flex-col min-h-screen bg-[var(--bg)]">
      <Header />

      <div className="flex flex-1">
        <Sidebar />

        <main className="flex-1 min-w-0 flex flex-col">
          <div className="catalog-page">
            {/* Заголовок */}
            <div className="catalog-page__header">
              <h1 className="catalog-page__title">Каталог</h1>
              <p className="catalog-page__subtitle">
                Все фильмы и сериалы в одном месте
              </p>
            </div>

            {/* Фильтры — тип медиа */}
            <div className="catalog-page__filters">
              <div className="catalog-page__tabs">
                {MEDIA_TABS.map((tab) => (
                  <button
                    key={tab.key}
                    onClick={() => handleMediaTypeChange(tab.key)}
                    className={`catalog-page__tab ${
                      mediaType === tab.key ? "catalog-page__tab--active" : ""
                    }`}
                  >
                    {tab.label}
                  </button>
                ))}
              </div>

              {/* Фильтр по жанрам */}
              <div className="catalog-page__genres">
                <button
                  onClick={() => handleGenreChange(null)}
                  className={`catalog-page__genre ${
                    selectedGenre === null ? "catalog-page__genre--active" : ""
                  }`}
                >
                  Все жанры
                </button>
                {genres.map((genre) => (
                  <button
                    key={genre.id}
                    onClick={() => handleGenreChange(genre.id)}
                    className={`catalog-page__genre ${
                      selectedGenre === genre.id ? "catalog-page__genre--active" : ""
                    }`}
                  >
                    {genre.name}
                  </button>
                ))}
              </div>
            </div>

            {/* Сетка карточек */}
            <div className="catalog-page__grid">
              {loading
                ? Array.from({ length: 12 }).map((_, i) => (
                    <SkeletonCard key={`skeleton-${i}`} />
                  ))
                : items.length > 0
                  ? items.map((item) => (
                      <MediaCard
                        key={`${item.media_type || mediaType}-${item.id}`}
                        item={item}
                        mediaType={item.media_type || mediaType}
                      />
                    ))
                  : (
                    <div className="catalog-page__empty">
                      <svg className="catalog-page__empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={1.5}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                      </svg>
                      <p className="catalog-page__empty-text">Ничего не найдено</p>
                      <p className="catalog-page__empty-hint">Попробуйте изменить фильтры</p>
                    </div>
                  )}
            </div>

            {/* Пагинация */}
            {!loading && items.length > 0 && (
              <div className="catalog-page__pagination">
                <button
                  onClick={handlePrevPage}
                  disabled={page <= 1}
                  className="catalog-page__page-btn"
                  aria-label="Предыдущая страница"
                >
                  <svg className="catalog-page__page-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                  </svg>
                  Назад
                </button>

                <span className="catalog-page__page-info">
                  Страница {page} из {totalPages}
                </span>

                <button
                  onClick={handleNextPage}
                  disabled={page >= totalPages}
                  className="catalog-page__page-btn"
                  aria-label="Следующая страница"
                >
                  Вперёд
                  <svg className="catalog-page__page-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </div>
            )}
          </div>

          <Footer />
        </main>
      </div>
    </div>
  );
}