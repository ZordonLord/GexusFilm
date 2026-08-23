// Поиск и подборка контента с фильтрами, пагинацией и URL-состоянием.

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
  getSearch,
  getTvGenres,
} from "../services/api";
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

            <MediaToolbar className="search-page__filters" aria-labelledby="filters-heading">
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
            </MediaToolbar>

          <MediaBrowser
            items={items}
            loading={loading}
            error={error}
            onRetry={() => setReloadKey((key) => key + 1)}
            mediaType={urlState.type}
            resultsTitle={urlState.query ? "Найдено" : "Подборка"}
            totalResults={totalResults}
            page={urlState.page}
            totalPages={totalPages}
            onPageChange={handlePageChange}
            emptyTitle={urlState.query ? "Ничего не нашли" : "Подборка пока пуста"}
            emptyMessage={urlState.query ? "Проверьте написание или попробуйте более общий запрос." : "Измените фильтры — и здесь появятся подходящие фильмы и сериалы."}
            errorTitle="Не получилось загрузить результаты"
            variant="search"
          />

          <Footer />
        </main>
      </div>
    </div>
  );
}