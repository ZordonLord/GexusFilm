// Поиск и подборка контента с фильтрами, пагинацией и URL-состоянием.

import { useCallback, useEffect, useMemo, useState } from "react";
import { useSearchParams } from "react-router-dom";

import Header from "../components/Header";
import Sidebar from "../components/Sidebar";
import { useSidebar } from "../components/SidebarContext";
import Footer from "../components/Footer";
import MediaBrowser from "../components/MediaBrowser";
import MediaFilterToolbar from "../components/MediaFilterToolbar";
import {
  getMovieGenres,
  getSearch,
  getMultiSearch,
  getPeopleSearch,
  getTvGenres,
} from "../services/api";
import {
  DEFAULT_SORT,
  SORT_OPTIONS,
  TYPE_OPTIONS,
  YEAR_MAX,
  YEAR_MIN,
  parseCatalogParams,
  updateCatalogParams,
} from "../utils/catalogFilters";
import SearchQueryForm from "../components/SearchQueryForm";

import "../styles/SearchPage.css";

function getErrorMessage(error) {
  return error?.response?.data?.error?.message
    || "Не удалось загрузить результаты. Попробуйте ещё раз.";
}

function groupSearchResults(items) {
  return [
    { key: "movie", title: "Фильмы", items: items.filter((item) => item.media_type === "movie") },
    { key: "tv", title: "Сериалы", items: items.filter((item) => item.media_type === "tv") },
    { key: "person", title: "Актёры", items: items.filter((item) => item.media_type === "person") },
  ];
}

export default function SearchPage() {
  const { isMobile } = useSidebar();
  const [searchParams, setSearchParams] = useSearchParams();
  const urlState = useMemo(() => parseCatalogParams(searchParams), [searchParams]);
  const [items, setItems] = useState([]);
  const [genres, setGenres] = useState([]);
  const [totalPages, setTotalPages] = useState(0);
  const [totalResults, setTotalResults] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [reloadKey, setReloadKey] = useState(0);
  const [filtersOpen, setFiltersOpen] = useState(false);

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
      if (!urlState.query) {
        setItems([]);
        setTotalPages(0);
        setTotalResults(0);
        setError("");
        setLoading(false);
        return;
      }

      setLoading(true);
      setError("");
      const commonParams = {
        page: urlState.page,
        per_page: urlState.perPage,
      };

      if (urlState.year && urlState.type !== "person") commonParams.year = urlState.year;
      if (urlState.minRating && urlState.type !== "person") commonParams.min_rating = urlState.minRating;

      try {
        const response = urlState.type === "all"
          ? await getMultiSearch(urlState.query, commonParams)
          : urlState.type === "person"
            ? await getPeopleSearch(urlState.query, commonParams)
            : await getSearch(urlState.query, { ...commonParams, type: urlState.type });

        if (cancelled) return;
        setItems((response.results || []).map((item) => ({
          ...item,
          media_type: item.media_type || (urlState.type === "person" ? "person" : urlState.type),
        })));
        setTotalPages(response.total_pages || 0);
        setTotalResults(response.total_results || 0);
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

  const groupedResults = useMemo(
    () => urlState.type === "all" ? groupSearchResults(items) : undefined,
    [items, urlState.type],
  );

  const activeFiltersCount = [
    urlState.genreIds.length > 0,
    urlState.excludedGenreIds.length > 0,
    urlState.year,
    urlState.minRating,
    urlState.yearFrom || urlState.yearTo,
    urlState.voteCountGte,
    urlState.country,
  ].filter(Boolean).length;

  const handlePageChange = (page) => {
    updateUrl({ page });
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const clearFilters = () => {
    setSearchParams(new URLSearchParams(), { replace: true });
  };

  const handleGenresChange = ({ includedIds, excludedIds }) => {
    updateUrl({
      genre_ids: includedIds.join(","),
      exclude_genre_ids: excludedIds.join(","),
      genre_id: "",
      page: "",
    });
  };

  const handleYearRangeChange = ({ from, to }) => {
    updateUrl({
      year: "",
      year_from: from === YEAR_MIN ? "" : from,
      year_to: to === YEAR_MAX ? "" : to,
      page: "",
    });
  };

  return (
    <div className="search-page-shell">
      <Header />

      <div className="search-page__body">
        <Sidebar />

        <main className="search-page" id="main-content" tabIndex="-1" aria-busy={loading}>
          <div className="search-page__header">
            <div>
              <h1 className="search-page__title">
                {urlState.query ? "Результаты поиска" : "Найти свой следующий фильм"}
              </h1>
              <p className="search-page__subtitle">
                {urlState.query
                  ? <>По запросу <strong>«{urlState.query}»</strong>{totalResults > 0 ? ` найдено ${totalResults}` : ""}</>
                  : "Ищите по названию или соберите подборку с помощью фильтров."}
              </p>
            </div>

          </div>

          <div className="search-page__type-switch" role="group" aria-label="Тип контента">
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

          <SearchQueryForm
            query={urlState.query}
            searchParams={searchParams}
            setSearchParams={setSearchParams}
          />

          <div className="search-page__controls">
            <div className="search-page__toolbar-actions" aria-label="Сортировка и фильтры поиска">
              <label className="search-page__sort-control">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M4 7h16M7 12h10m-7 5h4" /></svg>
                <span className="search-page__sort-label">{SORT_OPTIONS.find((option) => option.value === urlState.sortBy)?.label || "Сортировка"}</span>
                <select value={urlState.sortBy} onChange={(event) => updateUrl({ sort_by: event.target.value, page: "" })} aria-label="Сортировка">
                  {SORT_OPTIONS.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                </select>
                <svg className="search-page__control-chevron" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="m7 10 5 5 5-5" /></svg>
              </label>

              <button
                type="button"
                className={`search-page__filter-trigger${filtersOpen ? " is-active" : ""}`}
                onClick={() => setFiltersOpen((open) => !open)}
                aria-expanded={filtersOpen}
                aria-controls="search-filter-panel"
              >
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M4 7h16M7 12h10m-7 5h4" /></svg>
                Фильтры
                {activeFiltersCount > 0 && <span className="search-page__filter-count">{activeFiltersCount}</span>}
              </button>
            </div>

            {filtersOpen && isMobile && <button type="button" className="search-page__filter-scrim" onClick={() => setFiltersOpen(false)} aria-label="Закрыть фильтры" />}

            <div className={`search-page__filter-shell${filtersOpen ? " is-open" : ""}`}>
              <MediaFilterToolbar
                id="search-filter-panel"
                className="search-page__filters"
                heading="Фильтры"
                activeFiltersCount={activeFiltersCount}
                onReset={clearFilters}
                showType={false}
                showSort={false}
                mobileDrawer={isMobile}
                compactHeader={isMobile}
                onClose={() => setFiltersOpen(false)}
                genres={genres}
                includedIds={urlState.genreIds}
                excludedIds={urlState.excludedGenreIds}
                onGenresChange={handleGenresChange}
                genreDisabled={Boolean(urlState.query) || urlState.type === "person"}
                genreDisabledMessage={urlState.type === "person" ? "Жанры не применяются к поиску актёров." : urlState.query ? "Жанр доступен для подборки без поискового запроса." : ""}
                discoverOnlyDisabled={Boolean(urlState.query) || urlState.type === "person"}
                yearFrom={urlState.yearFrom || urlState.year}
                yearTo={urlState.yearTo || urlState.year}
                onYearRangeChange={handleYearRangeChange}
                yearRangeDisabled={urlState.type === "person"}
                minRating={urlState.type === "person" ? "" : urlState.minRating}
                onMinRatingChange={(value) => updateUrl({ min_rating: value, page: "" })}
                voteCountGte={urlState.voteCountGte}
                onVoteCountGteChange={(value) => updateUrl({ vote_count_gte: value, "vote_count.gte": "", page: "" })}
                country={urlState.country}
                onCountryChange={(value) => updateUrl({ country: value, page: "" })}
                idPrefix="search-filter"
                minRatingDisabled={urlState.type === "person"}
              />
            </div>
          </div>

          <MediaBrowser
            items={items}
              resultGroups={groupedResults}
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
            emptyMessage={urlState.query ? "Проверьте написание или попробуйте более общий запрос." : "Введите запрос, чтобы увидеть подходящие результаты."}
            errorTitle="Не получилось загрузить результаты"
            variant="search"
          />

          <Footer />
        </main>
      </div>
    </div>
  );
}