// Поиск и подборка контента с фильтрами, пагинацией и URL-состоянием.

import { useCallback, useEffect, useMemo, useState } from "react";
import { useSearchParams } from "react-router-dom";

import Header from "../components/Header";
import Sidebar from "../components/Sidebar";
import Footer from "../components/Footer";
import MediaCard from "../components/MediaCard";
import SkeletonCard from "../components/SkeletonCard";
import {
  getDiscoverMovies,
  getDiscoverTv,
  getMovieGenres,
  getSearch,
  getTvGenres,
} from "../services/api";
import { getMediaKey } from "../utils/media";
import {
  DEFAULT_SORT,
  SORT_OPTIONS,
  TYPE_OPTIONS,
  parseCatalogParams,
  updateCatalogParams,
} from "../utils/catalogFilters";
import SearchQueryForm from "../components/SearchQueryForm";

import "../styles/SearchPage.css";

function getErrorMessage(error) {
  return error?.response?.data?.error?.message
    || "Не удалось загрузить результаты. Попробуйте ещё раз.";
}

function mergeResponses(responses, types) {
  const results = [];

  responses.forEach((response, index) => {
    const mediaType = types[index];
    (response.results || []).forEach((item) => {
      results.push({ ...item, media_type: item.media_type || mediaType });
    });
  });

  return {
    results,
    totalPages: Math.max(...responses.map((response) => response.total_pages || 0), 0),
    totalResults: responses.reduce((total, response) => total + (response.total_results || 0), 0),
  };
}

export default function SearchPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const urlState = useMemo(() => parseCatalogParams(searchParams), [searchParams]);
  const [items, setItems] = useState([]);
  const [genres, setGenres] = useState([]);
  const [totalPages, setTotalPages] = useState(0);
  const [totalResults, setTotalResults] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [reloadKey, setReloadKey] = useState(0);

  const updateUrl = useCallback((changes) => {
    const nextParams = updateCatalogParams(searchParams, changes, { defaultSort: DEFAULT_SORT });
    setSearchParams(nextParams, { replace: true });
  }, [searchParams, setSearchParams]);

  useEffect(() => {
    let cancelled = false;

    async function loadGenres() {
      try {
        const [movieGenres, tvGenres] = await Promise.all([
          getMovieGenres(),
          getTvGenres(),
        ]);
        if (cancelled) return;

        const uniqueGenres = new Map();
        [...(movieGenres.genres || []), ...(tvGenres.genres || [])]
          .forEach((genre) => uniqueGenres.set(genre.id, genre));
        setGenres([...uniqueGenres.values()]);
      } catch (genreError) {
        // Фильтры не должны блокировать сам поиск, если справочник жанров временно недоступен.
        console.error("Ошибка загрузки жанров:", genreError);
      }
    }

    loadGenres();
    return () => { cancelled = true; };
  }, []);

  useEffect(() => {
    let cancelled = false;

    async function loadResults() {
      setLoading(true);
      setError("");

      const types = urlState.type === "all" ? ["movie", "tv"] : [urlState.type];
      const commonParams = {
        page: urlState.page,
        per_page: urlState.perPage,
        sort_by: urlState.sortBy,
      };

      if (urlState.year) commonParams.year = urlState.year;
      if (urlState.minRating) commonParams.min_rating = urlState.minRating;
      if (urlState.region) commonParams.region = urlState.region;

      try {
        const requests = types.map((type) => {
          const params = { ...commonParams, type };
          if (!urlState.query && urlState.genreId) params.genre_id = urlState.genreId;

          return urlState.query
            ? getSearch(urlState.query, params)
            : type === "tv"
              ? getDiscoverTv(params)
              : getDiscoverMovies(params);
        });
        const responses = await Promise.all(requests);

        if (cancelled) return;
        const merged = mergeResponses(responses, types);
        setItems(merged.results);
        setTotalPages(merged.totalPages);
        setTotalResults(merged.totalResults);
      } catch (loadError) {
        if (cancelled) return;
        setItems([]);
        setTotalPages(0);
        setTotalResults(0);
        setError(getErrorMessage(loadError));
      } finally {
        if (!cancelled) setLoading(false);
      }
    }

    loadResults();
    return () => { cancelled = true; };
  }, [reloadKey, urlState]);

  const activeFiltersCount = [
    urlState.type !== "all",
    urlState.genreId,
    urlState.year,
    urlState.minRating,
    urlState.region,
    urlState.sortBy !== DEFAULT_SORT,
  ].filter(Boolean).length;

  const handlePageChange = (page) => {
    updateUrl({ page });
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const clearFilters = () => {
    setSearchParams(new URLSearchParams(), { replace: true });
  };

  return (
    <div className="search-page-shell">
      <Header />

      <div className="search-page__body">
        <Sidebar />

        <main className="search-page" id="main-content" aria-busy={loading}>
          <div className="search-page__header">
            <div>
              <p className="search-page__eyebrow">GX-14 · Поиск и подборка</p>
              <h1 className="search-page__title">
                {urlState.query ? "Результаты поиска" : "Найти свой следующий фильм"}
              </h1>
              <p className="search-page__subtitle">
                {urlState.query
                  ? <>По запросу <strong>«{urlState.query}»</strong>{totalResults > 0 ? ` найдено ${totalResults}` : ""}</>
                  : "Ищите по названию или соберите подборку с помощью фильтров."}
              </p>
            </div>

            {activeFiltersCount > 0 && (
              <button type="button" className="search-page__reset" onClick={clearFilters}>
                Сбросить фильтры
              </button>
            )}
          </div>

          <SearchQueryForm
            key={urlState.query}
            query={urlState.query}
            searchParams={searchParams}
            setSearchParams={setSearchParams}
          />

          <section className="search-page__filters" aria-labelledby="filters-heading">
            <div className="search-page__filters-head">
              <h2 id="filters-heading">Фильтры</h2>
              <span>{activeFiltersCount ? `${activeFiltersCount} активно` : "Настройте выдачу"}</span>
            </div>

            <div className="search-page__filter-grid">
              <fieldset className="search-page__field search-page__field--type">
                <legend>Тип контента</legend>
                <div className="search-page__segmented">
                  {TYPE_OPTIONS.map((option) => (
                    <button
                      key={option.value}
                      type="button"
                      className={urlState.type === option.value ? "is-active" : ""}
                      onClick={() => updateUrl({ type: option.value, page: "" })}
                      aria-pressed={urlState.type === option.value}
                    >
                      {option.label}
                    </button>
                  ))}
                </div>
              </fieldset>

              <label className="search-page__field">
                <span>Сортировка</span>
                <select value={urlState.sortBy} onChange={(event) => updateUrl({ sort_by: event.target.value, page: "" })}>
                  {SORT_OPTIONS.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                </select>
              </label>

              <label className="search-page__field">
                <span>Жанр</span>
                <select
                  value={urlState.genreId}
                  onChange={(event) => updateUrl({ genre_id: event.target.value, page: "" })}
                  disabled={Boolean(urlState.query)}
                  aria-describedby={urlState.query ? "genre-help" : undefined}
                >
                  <option value="">Все жанры</option>
                  {genres.map((genre) => <option key={genre.id} value={genre.id}>{genre.name}</option>)}
                </select>
                {urlState.query && <small id="genre-help">Жанр доступен для подбора без поискового запроса.</small>}
              </label>

              <label className="search-page__field">
                <span>Год выпуска</span>
                <input type="number" min="1870" max="2100" inputMode="numeric" placeholder="Например, 2024" value={urlState.year} onChange={(event) => updateUrl({ year: event.target.value, page: "" })} />
              </label>

              <label className="search-page__field">
                <span>Рейтинг от</span>
                <input type="number" min="0" max="10" step="0.5" inputMode="decimal" placeholder="0–10" value={urlState.minRating} onChange={(event) => updateUrl({ min_rating: event.target.value, page: "" })} />
              </label>

              <label className="search-page__field">
                <span>Регион</span>
                <input type="text" maxLength="2" inputMode="text" placeholder="RU" value={urlState.region} onChange={(event) => updateUrl({ region: event.target.value.toUpperCase(), page: "" })} />
              </label>
            </div>
          </section>

          <div className="search-page__results-head" aria-live="polite">
            <h2>{urlState.query ? "Найдено" : "Подборка"}</h2>
            {!loading && !error && items.length > 0 && <span>{totalResults} результатов</span>}
          </div>

          {error ? (
            <div className="search-page__state search-page__state--error" role="alert">
              <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v4m0 4h.01M10.3 3.85 2.6 17a2 2 0 0 0 1.74 3h15.32a2 2 0 0 0 1.74-3l-7.7-13.15a2 2 0 0 0-3.4 0Z" />
              </svg>
              <h2>Не получилось загрузить результаты</h2>
              <p>{error}</p>
              <button type="button" onClick={() => setReloadKey((key) => key + 1)}>Повторить</button>
            </div>
          ) : (
            <div className="search-page__grid">
              {loading
                ? Array.from({ length: 12 }).map((_, index) => <SkeletonCard key={`search-skeleton-${index}`} />)
                : items.length > 0
                  ? items.map((item) => {
                      const mediaType = item.media_type || "movie";
                      const key = getMediaKey(item, mediaType);

                      if (!key) {
                        return null;
                      }

                      return <MediaCard key={key} item={item} mediaType={mediaType} />;
                    })
                  : (
                    <div className="search-page__state search-page__state--empty">
                      <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                        <path strokeLinecap="round" strokeLinejoin="round" d="m21 21-4.35-4.35m2.1-5.15a7.25 7.25 0 1 1-14.5 0 7.25 7.25 0 0 1 14.5 0Z" />
                      </svg>
                      <h2>{urlState.query ? "Ничего не нашли" : "Подборка пока пуста"}</h2>
                      <p>{urlState.query ? "Проверьте написание или попробуйте более общий запрос." : "Измените фильтры — и здесь появятся подходящие фильмы и сериалы."}</p>
                    </div>
                  )}
            </div>
          )}

          {!loading && !error && items.length > 0 && totalPages > 1 && (
            <nav className="search-page__pagination" aria-label="Пагинация результатов">
              <button type="button" onClick={() => handlePageChange(Math.max(1, urlState.page - 1))} disabled={urlState.page <= 1}>
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="m15 18-6-6 6-6" /></svg>
                Назад
              </button>
              <span>Страница {urlState.page} из {totalPages}</span>
              <button type="button" onClick={() => handlePageChange(Math.min(totalPages, urlState.page + 1))} disabled={urlState.page >= totalPages}>
                Вперёд
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="m9 18 6-6-6-6" /></svg>
              </button>
            </nav>
          )}

          <Footer />
        </main>
      </div>
    </div>
  );
}