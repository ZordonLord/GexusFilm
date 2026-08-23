// Поиск и подборка контента с фильтрами, пагинацией и URL-состоянием.

import { useCallback, useEffect, useMemo, useState } from "react";
import { useSearchParams } from "react-router-dom";

import Header from "../components/Header";
import Sidebar from "../components/Sidebar";
import Footer from "../components/Footer";
import MediaBrowser from "../components/MediaBrowser";
import MediaFilterToolbar from "../components/MediaFilterToolbar";
import {
  getDiscoverMovies,
  getDiscoverTv,
  getMovieGenres,
  getSearch,
  getTvGenres,
} from "../services/api";
import {
  DEFAULT_SORT,
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
      if (!urlState.query && urlState.genreIds.length > 0) {
        commonParams.genre_ids = urlState.genreIds.join(",");
      }
      if (!urlState.query && urlState.excludedGenreIds.length > 0) {
        commonParams.exclude_genre_ids = urlState.excludedGenreIds.join(",");
      }

      try {
        const requests = types.map((type) => {
          const params = { ...commonParams, type };

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
    urlState.genreIds.length > 0,
    urlState.excludedGenreIds.length > 0,
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

  const handleGenresChange = ({ includedIds, excludedIds }) => {
    updateUrl({
      genre_ids: includedIds.join(","),
      exclude_genre_ids: excludedIds.join(","),
      genre_id: "",
      page: "",
    });
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

          </div>

          <SearchQueryForm
            key={urlState.query}
            query={urlState.query}
            searchParams={searchParams}
            setSearchParams={setSearchParams}
          />

          <MediaFilterToolbar
            className="search-page__filters"
            heading="Фильтры"
            activeFiltersCount={activeFiltersCount}
            onReset={clearFilters}
            type={urlState.type}
            onTypeChange={(value) => updateUrl({ type: value, page: "" })}
            sortBy={urlState.sortBy}
            onSortChange={(value) => updateUrl({ sort_by: value, page: "" })}
            genres={genres}
            includedIds={urlState.genreIds}
            excludedIds={urlState.excludedGenreIds}
            onGenresChange={handleGenresChange}
            genreDisabled={Boolean(urlState.query)}
            genreDisabledMessage={urlState.query ? "Жанр доступен для подборки без поискового запроса." : ""}
            year={urlState.year}
            onYearChange={(value) => updateUrl({ year: value, page: "" })}
            minRating={urlState.minRating}
            onMinRatingChange={(value) => updateUrl({ min_rating: value, page: "" })}
            region={urlState.region}
            onRegionChange={(value) => updateUrl({ region: value, page: "" })}
            idPrefix="search-filter"
          />

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