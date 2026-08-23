// Страница каталога — все фильмы и сериалы с фильтрацией

import { useCallback, useEffect, useMemo, useState } from "react";
import { useSearchParams } from "react-router-dom";

import Header from "../components/Header";
import Sidebar from "../components/Sidebar";
import Footer from "../components/Footer";
import MediaBrowser, { MediaToolbar } from "../components/MediaBrowser";

import {
  getDiscoverMovies,
  getDiscoverTv,
  getMovieGenres,
  getTvGenres,
} from "../services/api";
import {
  CATALOG_DEFAULT_SORT,
  parseCatalogParams,
  updateCatalogParams,
} from "../utils/catalogFilters";

import "../styles/CatalogPage.css";

const MEDIA_TABS = [
  { key: "all", label: "Всё" },
  { key: "movie", label: "Фильмы" },
  { key: "tv", label: "Сериалы" },
];

export default function CatalogPage({ movieOnly = false, tvOnly = false }) {
  const catalogType = movieOnly ? "movie" : tvOnly ? "tv" : "all";
  const [searchParams, setSearchParams] = useSearchParams();
  const urlState = useMemo(() => parseCatalogParams(searchParams, {
    fallbackType: catalogType,
    defaultSort: CATALOG_DEFAULT_SORT,
  }), [catalogType, searchParams]);
  const { genreIds: selectedGenres, page, sortBy, perPage } = urlState;
  const mediaType = urlState.type;
  const [items, setItems] = useState([]);
  const [genres, setGenres] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [totalPages, setTotalPages] = useState(1);
  const [reloadKey, setReloadKey] = useState(0);

  const updateUrl = useCallback((changes) => {
    const nextParams = updateCatalogParams(searchParams, changes, {
      defaultSort: CATALOG_DEFAULT_SORT,
      defaultType: catalogType,
    });
    setSearchParams(nextParams, { replace: true });
  }, [catalogType, searchParams, setSearchParams]);

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
        const params = { page, per_page: perPage, sort_by: sortBy };
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
  }, [mediaType, page, perPage, selectedGenres, sortBy, urlState, reloadKey]);

  // Сброс страницы при смене фильтров
  const handleMediaTypeChange = (type) => {
    updateUrl({ type, page: "" });
  };

  const handleGenreChange = (index, genreId) => {
    const nextGenres = selectedGenres.slice(0, index);
    if (genreId) nextGenres.push(genreId);

    updateUrl({ genre_ids: nextGenres.join(","), genre_id: "", page: "" });
  };

  const handleSortChange = (nextSort) => {
    updateUrl({ sort_by: nextSort, page: "" });
  };

  const handlePageChange = (nextPage) => {
    updateUrl({ page: nextPage });
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
            <MediaToolbar className="catalog-page__filters" aria-label="Фильтры каталога">
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
                {(selectedGenres.length > 0 ? [...selectedGenres, ""] : [""]).map((selectedGenre, index) => {
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
            </MediaToolbar>

            <MediaBrowser
              items={items}
              loading={loading}
              error={error}
              onRetry={() => setReloadKey((key) => key + 1)}
              mediaType={mediaType}
              page={page}
              totalPages={totalPages}
              onPageChange={handlePageChange}
              emptyTitle="Ничего не найдено"
              emptyMessage="Попробуйте изменить фильтры."
              errorTitle="Не получилось загрузить каталог"
              variant="catalog"
            />
          </div>

          <Footer />
        </main>
      </div>
    </div>
  );
}