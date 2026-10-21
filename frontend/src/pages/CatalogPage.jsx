// Страница каталога — все фильмы и сериалы с фильтрацией

import { useCallback, useEffect, useMemo, useState } from "react";
import { useSearchParams } from "react-router-dom";

import Header from "../components/Header";
import Sidebar from "../components/Sidebar";
import { useSidebar } from "../components/SidebarContext";
import Footer from "../components/Footer";
import MediaBrowser from "../components/MediaBrowser";
import MediaFilterToolbar from "../components/MediaFilterToolbar";

import {
  getDiscoverMovies,
  getDiscoverTv,
  getMovieGenres,
  getTvGenres,
} from "../services/api";
import {
  CATALOG_DEFAULT_SORT,
  DEFAULT_CATALOG_PER_PAGE,
  PAGE_SIZE_OPTIONS,
  SORT_OPTIONS,
  TYPE_OPTIONS,
  YEAR_MAX,
  YEAR_MIN,
  parseCatalogParams,
  updateCatalogParams,
} from "../utils/catalogFilters";

import "../styles/CatalogPage.css";

function getFilterDraft({ type, sortBy, genreIds, excludedGenreIds, year, yearFrom, yearTo, minRating, voteCountGte, country, releasedOnly = true }) {
  return {
    type,
    sortBy,
    genreIds: [...genreIds],
    excludedGenreIds: [...excludedGenreIds],
    yearFrom: Number.parseInt(yearFrom || year, 10) || YEAR_MIN,
    yearTo: Number.parseInt(yearTo || year, 10) || YEAR_MAX,
    minRating,
    voteCountGte,
    country,
    releasedOnly,
  };
}

export default function CatalogPage({ movieOnly = false, tvOnly = false }) {
  const catalogType = movieOnly ? "movie" : tvOnly ? "tv" : "all";
  const { isMobile } = useSidebar();
  const [searchParams, setSearchParams] = useSearchParams();
  const urlState = useMemo(() => parseCatalogParams(searchParams, {
    fallbackType: catalogType,
    defaultSort: CATALOG_DEFAULT_SORT,
    defaultPerPage: DEFAULT_CATALOG_PER_PAGE,
  }), [catalogType, searchParams]);
  const {
    genreIds: selectedGenres,
    excludedGenreIds,
    year,
    yearFrom,
    yearTo,
    minRating,
    voteCountGte,
    country,
    releasedOnly,
    page,
    sortBy,
    perPage,
  } = urlState;
  const mediaType = urlState.type;
  const viewMode = searchParams.get("view") === "list" ? "list" : "grid";
  const [items, setItems] = useState([]);
  const [genres, setGenres] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [totalPages, setTotalPages] = useState(1);
  const [reloadKey, setReloadKey] = useState(0);
  const [filtersOpen, setFiltersOpen] = useState(false);
  const [draftFilters, setDraftFilters] = useState(() => getFilterDraft({
    type: catalogType,
    sortBy: CATALOG_DEFAULT_SORT,
    genreIds: [],
    excludedGenreIds: [],
    year: "",
    yearFrom: "",
    yearTo: "",
    minRating: "",
    voteCountGte: "",
    country: "",
    releasedOnly: true,
  }));

  const committedFilters = getFilterDraft({
    type: mediaType,
    sortBy,
    genreIds: selectedGenres,
    excludedGenreIds,
    year,
    yearFrom,
    yearTo,
    minRating,
    voteCountGte,
    country,
    releasedOnly,
  });
  const visibleFilters = isMobile ? draftFilters : committedFilters;

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
        if (genreIds.length > 0) params.genre_ids = genreIds.join(",");
        if (excludedGenreIds.length > 0) {
          params.exclude_genre_ids = excludedGenreIds.join(",");
        }
        if (year) params.year = year;
        if (minRating) params.min_rating = minRating;
        if (yearFrom) params.year_from = yearFrom;
        if (yearTo) params.year_to = yearTo;
        if (voteCountGte) params.vote_count_gte = voteCountGte;
        if (country) params.country = country;
        params.released_only = urlState.releasedOnly ? 1 : 0;

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
  }, [mediaType, page, perPage, selectedGenres, excludedGenreIds, year, yearFrom, yearTo, minRating, voteCountGte, country, sortBy, urlState.releasedOnly, reloadKey]);

  // Сброс страницы при смене фильтров
  const handleMediaTypeChange = (type) => {
    if (isMobile) {
      setDraftFilters((draft) => ({ ...draft, type }));
      return;
    }
    updateUrl({ type, page: "" });
  };

  const handleGenresChange = ({ includedIds, excludedIds }) => {
    if (isMobile) {
      setDraftFilters((draft) => ({ ...draft, genreIds: includedIds, excludedGenreIds: excludedIds }));
      return;
    }
    updateUrl({
      genre_ids: includedIds.join(","),
      exclude_genre_ids: excludedIds.join(","),
      genre_id: "",
      page: "",
    });
  };

  const activeFiltersCount = [
    !movieOnly && !tvOnly && mediaType !== "all",
    selectedGenres.length > 0,
    excludedGenreIds.length > 0,
    minRating,
    voteCountGte,
    country,
    yearFrom || yearTo || year,
    sortBy !== CATALOG_DEFAULT_SORT,
    !releasedOnly,
  ].filter(Boolean).length;

  const activeFilterChips = [
    !movieOnly && !tvOnly && committedFilters.type !== "all"
      ? { key: "type", label: TYPE_OPTIONS.find((option) => option.value === committedFilters.type)?.label || committedFilters.type }
      : null,
    committedFilters.genreIds.length > 0 || committedFilters.excludedGenreIds.length > 0
      ? { key: "genres", label: `Жанры · ${committedFilters.genreIds.length + committedFilters.excludedGenreIds.length}` }
      : null,
    committedFilters.yearFrom !== YEAR_MIN || committedFilters.yearTo !== YEAR_MAX
      ? { key: "years", label: `${committedFilters.yearFrom}–${committedFilters.yearTo}` }
      : null,
    committedFilters.minRating ? { key: "rating", label: `Рейтинг от ${committedFilters.minRating}` } : null,
    committedFilters.voteCountGte ? { key: "votes", label: `Голосов от ${committedFilters.voteCountGte}` } : null,
    committedFilters.country ? { key: "country", label: committedFilters.country } : null,
    committedFilters.sortBy !== CATALOG_DEFAULT_SORT
      ? { key: "sort", label: SORT_OPTIONS.find((option) => option.value === committedFilters.sortBy)?.label || "Сортировка" }
      : null,
    !committedFilters.releasedOnly ? { key: "released", label: "Включая будущие" } : null,
  ].filter(Boolean);

  const clearFilters = () => {
    setSearchParams(new URLSearchParams(), { replace: true });
    setDraftFilters(getFilterDraft({
      type: catalogType,
      sortBy: CATALOG_DEFAULT_SORT,
      genreIds: [],
      excludedGenreIds: [],
      year: "",
      yearFrom: "",
      yearTo: "",
      minRating: "",
      voteCountGte: "",
      country: "",
    }));
  };

  const resetFilters = () => {
    if (isMobile) {
      setDraftFilters(getFilterDraft({
        type: catalogType,
        sortBy: CATALOG_DEFAULT_SORT,
        genreIds: [],
        excludedGenreIds: [],
        year: "",
        yearFrom: "",
        yearTo: "",
        minRating: "",
        voteCountGte: "",
        country: "",
      }));
      return;
    }
    clearFilters();
  };

  const handleSortChange = (nextSort) => {
    if (isMobile) {
      setDraftFilters((draft) => ({ ...draft, sortBy: nextSort }));
      return;
    }
    updateUrl({ sort_by: nextSort, page: "" });
  };

  const handlePerPageChange = (value) => {
    updateUrl({ per_page: value, page: "" });
  };

  const handleYearRangeChange = ({ from, to }) => {
    if (isMobile) {
      setDraftFilters((draft) => ({ ...draft, yearFrom: from, yearTo: to }));
      return;
    }
    updateUrl({
      year: "",
      year_from: from === YEAR_MIN ? "" : from,
      year_to: to === YEAR_MAX ? "" : to,
      page: "",
    });
  };

  const handleMinRatingChange = (value) => {
    if (isMobile) {
      setDraftFilters((draft) => ({ ...draft, minRating: value }));
      return;
    }
    updateUrl({ min_rating: value, page: "" });
  };

  const handleVoteCountChange = (value) => {
    if (isMobile) {
      setDraftFilters((draft) => ({ ...draft, voteCountGte: value }));
      return;
    }
    updateUrl({ vote_count_gte: value, "vote_count.gte": "", page: "" });
  };

  const handleCountryChange = (value) => {
    if (isMobile) {
      setDraftFilters((draft) => ({ ...draft, country: value }));
      return;
    }
    updateUrl({ country: value, page: "" });
  };

  const handleReleasedOnlyChange = (value) => {
    if (isMobile) {
      setDraftFilters((draft) => ({ ...draft, releasedOnly: value }));
      return;
    }
    updateUrl({ released_only: value ? "" : "0", page: "" });
  };

  const applyDraftFilters = () => {
    updateUrl({
      type: draftFilters.type,
      sort_by: draftFilters.sortBy,
      genre_ids: draftFilters.genreIds.join(","),
      exclude_genre_ids: draftFilters.excludedGenreIds.join(","),
      genre_id: "",
      year: "",
      year_from: draftFilters.yearFrom === YEAR_MIN ? "" : draftFilters.yearFrom,
      year_to: draftFilters.yearTo === YEAR_MAX ? "" : draftFilters.yearTo,
      min_rating: draftFilters.minRating,
      vote_count_gte: draftFilters.voteCountGte,
      "vote_count.gte": "",
      country: draftFilters.country,
      released_only: draftFilters.releasedOnly ? "" : "0",
      page: "",
    });
    setFiltersOpen(false);
  };

  const removeFilter = (key) => {
    const resetMap = {
      type: { type: catalogType },
      sort: { sort_by: CATALOG_DEFAULT_SORT },
      genres: { genre_ids: "", exclude_genre_ids: "", genre_id: "" },
      years: { year: "", year_from: "", year_to: "" },
      rating: { min_rating: "" },
      votes: { vote_count_gte: "", "vote_count.gte": "" },
      country: { country: "" },
      released: { released_only: "" },
    };
    if (isMobile) {
      const next = { ...draftFilters };
      if (key === "type") next.type = catalogType;
      if (key === "sort") next.sortBy = CATALOG_DEFAULT_SORT;
      if (key === "genres") { next.genreIds = []; next.excludedGenreIds = []; }
      if (key === "years") { next.yearFrom = YEAR_MIN; next.yearTo = YEAR_MAX; }
      if (key === "rating") next.minRating = "";
      if (key === "votes") next.voteCountGte = "";
      if (key === "country") next.country = "";
      if (key === "released") next.releasedOnly = true;
      setDraftFilters(next);
      return;
    }
    updateUrl({ ...(resetMap[key] || {}), page: "" });
  };

  const handlePageChange = (nextPage) => {
    updateUrl({ page: nextPage });
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const handleViewChange = (nextView) => {
    updateUrl({ view: nextView === "grid" ? "" : nextView });
  };

  return (
      <div className="catalog-page-shell">
      <Header />

      <div className="catalog-page-shell__body">
        <Sidebar />

        <main className="catalog-page-shell__main" id="main-content" tabIndex="-1">
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

            <div className={`catalog-page__filter-shell ${filtersOpen ? "is-open" : ""}`}>
              <button
                type="button"
                className="catalog-page__mobile-filter-trigger"
                onClick={() => {
                  setDraftFilters(committedFilters);
                  setFiltersOpen(true);
                }}
                aria-expanded={filtersOpen}
                aria-controls="catalog-filter-drawer"
              >
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M7 12h10m-7 6h4" /></svg>
                Фильтры{activeFiltersCount > 0 ? ` · ${activeFiltersCount}` : ""}
              </button>
              {filtersOpen && <button type="button" className="catalog-page__filter-scrim" onClick={() => setFiltersOpen(false)} aria-label="Закрыть фильтры" />}
              <MediaFilterToolbar
              mobileDrawer
              compactHeader
              onClose={() => setFiltersOpen(false)}
              onApply={applyDraftFilters}
              id="catalog-filter-drawer"
              className="catalog-page__filters"
              heading="Фильтры каталога"
              activeFiltersCount={activeFiltersCount}
              onReset={resetFilters}
              type={visibleFilters.type}
              onTypeChange={handleMediaTypeChange}
              showType={!movieOnly && !tvOnly}
              showSort={isMobile}
              sortBy={visibleFilters.sortBy}
              onSortChange={handleSortChange}
              genres={genres}
              includedIds={visibleFilters.genreIds}
              excludedIds={visibleFilters.excludedGenreIds}
              onGenresChange={handleGenresChange}
              yearFrom={visibleFilters.yearFrom}
              yearTo={visibleFilters.yearTo}
              onYearRangeChange={handleYearRangeChange}
              minRating={visibleFilters.minRating}
              onMinRatingChange={handleMinRatingChange}
              releasedOnly={visibleFilters.releasedOnly}
              onReleasedOnlyChange={handleReleasedOnlyChange}
              voteCountGte={visibleFilters.voteCountGte}
              onVoteCountGteChange={handleVoteCountChange}
              country={visibleFilters.country}
              onCountryChange={handleCountryChange}
              activeFilterChips={activeFilterChips}
              onRemoveFilter={removeFilter}
              idPrefix="catalog-filter"
              />
            </div>

            <div className="catalog-page__results-toolbar" aria-label="Настройки отображения результатов">
              <label className="catalog-page__sort-control">
                <span>Сортировка</span>
                <select value={sortBy} onChange={(event) => handleSortChange(event.target.value)}>
                  {SORT_OPTIONS.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                </select>
              </label>
              <label className="catalog-page__page-size-control">
                <span>Показывать</span>
                <select value={perPage} onChange={(event) => handlePerPageChange(event.target.value)} aria-label="Количество карточек на странице">
                  {PAGE_SIZE_OPTIONS.map((size) => <option key={size} value={size}>{size}</option>)}
                </select>
              </label>
              <div className="catalog-page__view-toolbar" aria-label="Режим отображения каталога">
                <span className="catalog-page__view-label">Вид каталога</span>
              <div className="catalog-page__view-switch" role="group">
                <button type="button" className={viewMode === "grid" ? "is-active" : ""} onClick={() => handleViewChange("grid")} aria-pressed={viewMode === "grid"}>
                  <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z" /></svg>
                  Сетка
                </button>
                <button type="button" className={viewMode === "list" ? "is-active" : ""} onClick={() => handleViewChange("list")} aria-pressed={viewMode === "list"}>
                  <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01" /></svg>
                  Список
                </button>
              </div>
              </div>
            </div>

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
              viewMode={viewMode}
              compactCards={viewMode === "grid"}
              skeletonCount={24}
            />
          </div>

          <Footer />
        </main>
      </div>
    </div>
  );
}