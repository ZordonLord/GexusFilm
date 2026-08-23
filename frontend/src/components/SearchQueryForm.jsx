import { useEffect, useState } from "react";

import { DEFAULT_SORT, updateCatalogParams } from "../utils/catalogFilters";
import { normalizeSearchQuery } from "../utils/search";

export default function SearchQueryForm({ query, searchParams, setSearchParams }) {
  const [queryInput, setQueryInput] = useState(query);

  useEffect(() => {
    if (queryInput.trim() === query) return undefined;

    const timer = window.setTimeout(() => {
      const nextQuery = normalizeSearchQuery(queryInput);
      const nextParams = updateCatalogParams(searchParams, { q: nextQuery, page: "" }, { defaultSort: DEFAULT_SORT });
      setSearchParams(nextParams, { replace: true });
    }, 350);

    return () => window.clearTimeout(timer);
  }, [query, queryInput, searchParams, setSearchParams]);

  const handleSubmit = (event) => {
    event.preventDefault();
    const nextQuery = normalizeSearchQuery(queryInput);

    if (nextQuery) {
      const nextParams = updateCatalogParams(searchParams, { q: nextQuery, page: "" }, { defaultSort: DEFAULT_SORT });
      setSearchParams(nextParams, { replace: true });
      return;
    }

    // Пустой запрос переключает экран на подборку, а не отправляет q в API.
    setSearchParams(new URLSearchParams(), { replace: true });
  };

  return (
    <form className="search-page__search-form" onSubmit={handleSubmit} role="search">
      <label className="search-page__search-label" htmlFor="catalog-search">Поиск по названию</label>
      <div className="search-page__search-row">
        <div className="search-page__input-wrap">
          <svg aria-hidden="true" className="search-page__search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path strokeLinecap="round" strokeLinejoin="round" d="m21 21-4.35-4.35m2.1-5.15a7.25 7.25 0 1 1-14.5 0 7.25 7.25 0 0 1 14.5 0Z" />
          </svg>
          <input
            id="catalog-search"
            type="search"
            value={queryInput}
            onChange={(event) => setQueryInput(event.target.value)}
            placeholder="Например, Интерстеллар или Dark..."
            maxLength={200}
            autoComplete="off"
            aria-describedby="search-help"
          />
          {queryInput && (
            <button type="button" className="search-page__clear" onClick={() => setQueryInput("")} aria-label="Очистить поиск">
              <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" d="M6 6l12 12M18 6 6 18" />
              </svg>
            </button>
          )}
        </div>
        <button type="submit" className="search-page__submit">Найти</button>
      </div>
      <p className="search-page__help" id="search-help">Результаты обновляются автоматически после короткой паузы.</p>
    </form>
  );
}