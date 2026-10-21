import { useEffect, useMemo, useRef, useState } from "react";

import "../styles/GenreFilter.css";

const MAX_GENRES = 5;
const POPULAR_GENRES = ["Боевик", "Приключения", "Комедия", "Драма", "Фантастика", "Триллер", "Ужасы", "Детектив"];

export default function GenreFilter({
  genres = [],
  includedIds = [],
  excludedIds = [],
  onChange,
  disabled = false,
  disabledMessage = "",
  idPrefix = "genre",
}) {
  const includedSet = new Set(includedIds.map(String));
  const excludedSet = new Set(excludedIds.map(String));
  const includedGenres = genres.filter((genre) => includedSet.has(String(genre.id)));
  const excludedGenres = genres.filter((genre) => excludedSet.has(String(genre.id)));
  const [isOpen, setIsOpen] = useState(false);
  const [query, setQuery] = useState("");
  const [selectionMode, setSelectionMode] = useState("include");
  const containerRef = useRef(null);

  useEffect(() => {
    if (!isOpen) return undefined;

    const handleOutsideClick = (event) => {
      if (!containerRef.current?.contains(event.target)) setIsOpen(false);
    };

    document.addEventListener("mousedown", handleOutsideClick);
    return () => document.removeEventListener("mousedown", handleOutsideClick);
  }, [isOpen]);

  const filteredGenres = useMemo(() => {
    const normalizedQuery = query.trim().toLocaleLowerCase();
    if (!normalizedQuery) return genres;
    return genres.filter((genre) => genre.name.toLocaleLowerCase().includes(normalizedQuery));
  }, [genres, query]);

  const popularGenres = useMemo(() => {
    const popular = genres.filter((genre) => POPULAR_GENRES.includes(genre.name));
    return popular.length > 0 ? popular : genres.slice(0, 8);
  }, [genres]);
  const popularGenreIds = new Set(popularGenres.map((genre) => String(genre.id)));
  const remainingGenres = query.trim() === ""
    ? filteredGenres.filter((genre) => !popularGenreIds.has(String(genre.id)))
    : filteredGenres;

  const selectedCount = includedIds.length + excludedIds.length;

  const handleGenreToggle = (genreId, mode) => {
    const normalizedId = String(genreId);
    const isIncluded = includedSet.has(normalizedId);
    const isExcluded = excludedSet.has(normalizedId);
    let nextIncluded = includedIds;
    let nextExcluded = excludedIds;

    if (mode === "include") {
      if (isIncluded) {
        nextIncluded = includedIds.filter((id) => String(id) !== normalizedId);
      } else if (includedIds.length < MAX_GENRES) {
        nextIncluded = [...includedIds, normalizedId];
        nextExcluded = excludedIds.filter((id) => String(id) !== normalizedId);
      }
    } else if (isExcluded) {
      nextExcluded = excludedIds.filter((id) => String(id) !== normalizedId);
    } else if (excludedIds.length < MAX_GENRES) {
      nextExcluded = [...excludedIds, normalizedId];
      nextIncluded = includedIds.filter((id) => String(id) !== normalizedId);
    }

    if (nextIncluded.join(",") !== includedIds.join(",") || nextExcluded.join(",") !== excludedIds.join(",")) {
      onChange({ includedIds: nextIncluded, excludedIds: nextExcluded });
    }
  };

  const renderGenreOption = (genre) => {
    const normalizedId = String(genre.id);
    const included = includedSet.has(normalizedId);
    const excluded = excludedSet.has(normalizedId);
    const selected = selectionMode === "include" ? included : excluded;
    const limitReached = selectionMode === "include"
      ? includedIds.length >= MAX_GENRES && !included
      : excludedIds.length >= MAX_GENRES && !excluded;

    return (
      <button
        type="button"
        className={`genre-filter__option${included ? " is-included" : ""}${excluded ? " is-excluded" : ""}`}
        key={genre.id}
        aria-pressed={selected}
        disabled={limitReached}
        onClick={() => handleGenreToggle(genre.id, selectionMode)}
      >
        <span>{genre.name}</span>
        {selected && <span className="genre-filter__option-state">Выбрано</span>}
      </button>
    );
  };

  return (
    <fieldset className="genre-filter" disabled={disabled} ref={containerRef}>
      <legend className="genre-filter__legend">Жанры</legend>

      <button
        type="button"
        className={`genre-filter__trigger${selectedCount > 0 ? " has-selection" : ""}`}
        onClick={() => setIsOpen((open) => !open)}
        aria-expanded={isOpen}
        aria-controls={`${idPrefix}-popover`}
      >
        <span>{selectedCount > 0 ? `${selectedCount} выбрано` : "Все жанры"}</span>
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d={isOpen ? "m6 15 6-6 6 6" : "m6 9 6 6 6-6"} /></svg>
      </button>

      {isOpen && (
        <div className="genre-filter__popover" id={`${idPrefix}-popover`} role="dialog" aria-label="Выбор жанров">
          <div className="genre-filter__popover-head">
            <div>
              <strong>Выберите жанры</strong>
              <span id={`${idPrefix}-help`}>{includedIds.length} добавлено · {excludedIds.length} исключено</span>
            </div>
            <button type="button" className="genre-filter__popover-close" onClick={() => setIsOpen(false)} aria-label="Закрыть выбор жанров">×</button>
          </div>

          <label className="genre-filter__search">
            <span className="sr-only">Поиск жанра</span>
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="m21 21-4.35-4.35m2.1-5.15a7.25 7.25 0 1 1-14.5 0 7.25 7.25 0 0 1 14.5 0Z" /></svg>
            <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Найти жанр" />
          </label>

          <div className="genre-filter__mode" role="group" aria-label="Режим выбора жанров">
            <button
              type="button"
              className={selectionMode === "include" ? "is-active" : ""}
              aria-pressed={selectionMode === "include"}
              onClick={() => setSelectionMode("include")}
            >
              Включить
            </button>
            <button
              type="button"
              className={selectionMode === "exclude" ? "is-active" : ""}
              aria-pressed={selectionMode === "exclude"}
              onClick={() => setSelectionMode("exclude")}
            >
              Исключить
            </button>
          </div>

          {(includedGenres.length > 0 || excludedGenres.length > 0) && (
            <div className="genre-filter__active-groups">
              {includedGenres.length > 0 && (
                <div className="genre-filter__active-group">
                  <span className="genre-filter__group-title">Включены</span>
                  <div className="genre-filter__chips">
                    {includedGenres.map((genre) => (
                      <span className="genre-filter__chip genre-filter__chip--included" key={`popover-included-${genre.id}`}>
                        {genre.name}
                        <button type="button" aria-label={`Убрать жанр ${genre.name}`} onClick={() => handleGenreToggle(genre.id, "include")}>
                          <span aria-hidden="true">×</span>
                        </button>
                      </span>
                    ))}
                  </div>
                </div>
              )}
              {excludedGenres.length > 0 && (
                <div className="genre-filter__active-group">
                  <span className="genre-filter__group-title">Исключены</span>
                  <div className="genre-filter__chips">
                    {excludedGenres.map((genre) => (
                      <span className="genre-filter__chip genre-filter__chip--excluded" key={`popover-excluded-${genre.id}`}>
                        {genre.name}
                        <button type="button" aria-label={`Убрать исключение жанра ${genre.name}`} onClick={() => handleGenreToggle(genre.id, "exclude")}>
                          <span aria-hidden="true">×</span>
                        </button>
                      </span>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          {query.trim() === "" && (
            <div className="genre-filter__group">
              <span className="genre-filter__group-title">Популярные</span>
              <div className="genre-filter__quick-list">
                {popularGenres.map(renderGenreOption)}
              </div>
            </div>
          )}

          <div className="genre-filter__group">
            <span className="genre-filter__group-title">{query.trim() ? "Результаты поиска" : "Все жанры"}</span>
            <div className="genre-filter__options" aria-describedby={`${idPrefix}-help`}>
              {remainingGenres.length > 0 ? remainingGenres.map(renderGenreOption) : <p className="genre-filter__empty">{query.trim() ? "Ничего не найдено" : "Популярные жанры показаны выше"}</p>}
            </div>
          </div>
        </div>
      )}

      <div className="genre-filter__summary" aria-live="polite">
        <span id={`${idPrefix}-help`}>
          {includedIds.length} добавлено · {excludedIds.length} исключено
        </span>
        {(includedGenres.length > 0 || excludedGenres.length > 0) && (
          <div className="genre-filter__chips" aria-label="Активные жанры">
            {includedGenres.map((genre) => (
              <span className="genre-filter__chip genre-filter__chip--included" key={`included-${genre.id}`}>
                {genre.name}
                <button
                  type="button"
                  aria-label={`Убрать жанр ${genre.name} из добавленных`}
                  onClick={() => handleGenreToggle(genre.id, "include")}
                >
                  <span aria-hidden="true">×</span>
                </button>
              </span>
            ))}
            {excludedGenres.map((genre) => (
              <span className="genre-filter__chip genre-filter__chip--excluded" key={`excluded-${genre.id}`}>
                {genre.name}
                <button
                  type="button"
                  aria-label={`Убрать жанр ${genre.name} из исключенных`}
                  onClick={() => handleGenreToggle(genre.id, "exclude")}
                >
                  <span aria-hidden="true">×</span>
                </button>
              </span>
            ))}
          </div>
        )}
      </div>
      {disabled && disabledMessage && <p className="genre-filter__disabled-help">{disabledMessage}</p>}
    </fieldset>
  );
}