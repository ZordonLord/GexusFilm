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
import { getMediaKey } from "../utils/media";

import "../styles/CatalogPage.css";

const MEDIA_TABS = [
  { key: "all", label: "Всё" },
  { key: "movie", label: "Фильмы" },
  { key: "tv", label: "Сериалы" },
];

export default function CatalogPage({ movieOnly = false, tvOnly = false }) {
  const catalogType = movieOnly ? "movie" : tvOnly ? "tv" : "all";
  const [mediaType, setMediaType] = useState(catalogType);
  const [items, setItems] = useState([]);
  const [genres, setGenres] = useState([]);
  const [selectedGenres, setSelectedGenres] = useState([]);
  const [sortBy, setSortBy] = useState("year.desc");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const selectedGenreKey = selectedGenres.join(",");

  // Загрузка жанров
  useEffect(() => {
    let cancelled = false;

    async function loadGenres() {
      try {
        const [movieGenres, tvGenres] = movieOnly
          ? [await getMovieGenres(), { genres: [] }]
          : tvOnly
            ? [{ genres: [] }, await getTvGenres()]
            : await Promise.all([getMovieGenres(), getTvGenres()]);

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
  }, [movieOnly, tvOnly]);

  // Загрузка контента
  useEffect(() => {
    let cancelled = false;

    async function loadContent() {
      setLoading(true);
      setError("");

      try {
        const params = { page, sort_by: sortBy };
        const genreIds = selectedGenres.filter(Boolean);
        if (genreIds.length > 0) {
          params.genre_ids = genreIds.join(",");
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
        if (!cancelled) {
          setItems([]);
          setTotalPages(1);
          setError("Не удалось загрузить каталог. Попробуйте ещё раз.");
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    }

    loadContent();
    return () => { cancelled = true; };
  }, [mediaType, selectedGenreKey, page, sortBy, selectedGenres]);

  // Сброс страницы при смене фильтров
  const handleMediaTypeChange = (type) => {
    setMediaType(type);
    setPage(1);
  };

  const handleGenreChange = (index, genreId) => {
    setSelectedGenres((current) => {
      if (!genreId) {
        return current.slice(0, index);
      }

      const next = current.slice(0, index + 1);
      next[index] = genreId;

      if (next.length < 5) {
        next.push("");
      }

      return next;
    });
    setPage(1);
  };

  const handleSortChange = (nextSort) => {
    setSortBy(nextSort);
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
      <div className="catalog-page-shell flex flex-col min-h-screen bg-[var(--bg)]">
      <Header />

      <div className="flex flex-1">
        <Sidebar />

        <main className="flex-1 min-w-0 flex flex-col">
          <div className="catalog-page">
            {/* Заголовок */}
            <div className="catalog-page__header">
              <h1 className="catalog-page__title">{movieOnly ? "Фильмы" : tvOnly ? "Сериалы" : "Каталог"}</h1>
              <p className="catalog-page__subtitle">
                {movieOnly
                  ? "Подберите фильм по жанрам и сортировке"
                  : tvOnly
                    ? "Подберите сериал по жанрам и сортировке"
                    : "Все фильмы и сериалы в одном месте"}
              </p>
            </div>

            {/* Фильтры — тип медиа */}
            <div className="catalog-page__filters">
              {!movieOnly && !tvOnly && <div className="catalog-page__tabs">
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
              </div>}

              <div className="catalog-page__sort" aria-label="Сортировка каталога">
                <span className="catalog-page__control-label">Сортировка</span>
                <button
                  type="button"
                  className={`catalog-page__sort-button ${sortBy === "year.desc" ? "catalog-page__sort-button--active" : ""}`}
                  onClick={() => handleSortChange("year.desc")}
                  aria-pressed={sortBy === "year.desc"}
                >
                  По новизне
                </button>
                <button
                  type="button"
                  className={`catalog-page__sort-button ${sortBy === "vote_average.desc" ? "catalog-page__sort-button--active" : ""}`}
                  onClick={() => handleSortChange("vote_average.desc")}
                  aria-pressed={sortBy === "vote_average.desc"}
                >
                  По рейтингу
                </button>
              </div>

              <div className="catalog-page__genres" aria-label="Фильтр по жанрам">
                {(selectedGenres.length > 0 ? selectedGenres : [""]).map((selectedGenre, index) => {
                  const selectedElsewhere = new Set(selectedGenres.filter((genreId, genreIndex) => genreId && genreIndex !== index));
                  const availableGenres = genres.filter((genre) => !selectedElsewhere.has(String(genre.id)) || String(genre.id) === selectedGenre);

                  return (
                    <label className="catalog-page__genre-field" key={`genre-field-${index}`}>
                      <span className="catalog-page__control-label">Жанр {index + 1}</span>
                      <select
                        className="catalog-page__genre-select"
                        value={selectedGenre}
                        onChange={(event) => handleGenreChange(index, event.target.value)}
                      >
                        <option value="">{index === 0 ? "Все жанры" : "—"}</option>
                        {availableGenres.map((genre) => (
                          <option key={genre.id} value={genre.id}>{genre.name}</option>
                        ))}
                      </select>
                    </label>
                  );
                })}
              </div>
            </div>

            {/* Сетка карточек */}
            {error && <div className="catalog-page__error" role="alert">{error}</div>}

            <div className="catalog-page__grid">
              {loading
                ? Array.from({ length: 12 }).map((_, i) => (
                    <SkeletonCard key={`skeleton-${i}`} />
                  ))
                : items.length > 0
                  ? items.map((item) => {
                      const key = getMediaKey(item, mediaType);

                      if (!key) {
                        return null;
                      }

                      return (
                        <MediaCard
                          key={key}
                          item={item}
                          mediaType={item.media_type || mediaType}
                        />
                      );
                    })
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