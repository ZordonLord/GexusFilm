import { useState } from "react";

import { MediaToolbar } from "./MediaBrowser";
import GenreFilter from "./GenreFilter";
import { SORT_OPTIONS, TYPE_OPTIONS, YEAR_MAX, YEAR_MIN } from "../utils/catalogFilters";

import "../styles/MediaFilterToolbar.css";

export default function MediaFilterToolbar({
  heading = "Фильтры",
  activeFiltersCount = 0,
  onReset,
  type = "all",
  onTypeChange,
  showType = true,
  sortBy,
  onSortChange,
  showSort = true,
  genres = [],
  includedIds = [],
  excludedIds = [],
  onGenresChange,
  genreDisabled = false,
  genreDisabledMessage = "",
  discoverOnlyDisabled = false,
  yearFrom = "",
  yearTo = "",
  onYearRangeChange,
  minRating = "",
  onMinRatingChange,
  releasedOnly = true,
  onReleasedOnlyChange,
  voteCountGte = "",
  onVoteCountGteChange,
  country = "",
  onCountryChange,
  id,
  idPrefix = "media-filter",
  className = "",
  mobileDrawer = false,
  compactHeader = false,
  onClose,
  onApply,
  activeFilterChips = [],
  onRemoveFilter,
}) {
  const headingId = `${idPrefix}-heading`;
  const [advancedOpen, setAdvancedOpen] = useState(false);
  const selectedYearFrom = Number.parseInt(yearFrom, 10) || YEAR_MIN;
  const selectedYearTo = Number.parseInt(yearTo, 10) || YEAR_MAX;
  const selectedRating = Number.parseFloat(minRating) || 0;
  const yearPresets = [
    { label: "Любой год", from: YEAR_MIN, to: YEAR_MAX },
    { label: "Последние 5 лет", from: Math.max(YEAR_MIN, YEAR_MAX - 4), to: YEAR_MAX },
    { label: "Последние 10 лет", from: Math.max(YEAR_MIN, YEAR_MAX - 9), to: YEAR_MAX },
    { label: "2000-е", from: 2000, to: Math.min(2009, YEAR_MAX) },
    { label: "1990-е", from: 1990, to: Math.min(1999, YEAR_MAX) },
  ];
  const ratingPresets = [6, 7, 8, 9];
  return (
    <MediaToolbar
      id={id}
      className={`media-filter-toolbar ${className}`.trim()}
      aria-label={compactHeader ? heading : undefined}
      aria-labelledby={compactHeader ? undefined : headingId}
    >
      {(!compactHeader || mobileDrawer) && (
        <div className={`media-filter-toolbar__head${compactHeader ? " is-compact" : ""}`}>
          {!compactHeader && (
            <div>
              <h2 id={headingId}>{heading}</h2>
              <p className="media-filter-toolbar__hint">Настройте выдачу под свой вечер</p>
            </div>
          )}
          <div className="media-filter-toolbar__status">
            {!compactHeader && (
              <span aria-live="polite">
                {activeFiltersCount > 0 ? `${activeFiltersCount} активно` : "Настройте выдачу"}
              </span>
            )}
            {!compactHeader && !mobileDrawer && activeFiltersCount > 0 && onReset && (
              <button type="button" className="media-filter-toolbar__reset" onClick={onReset}>
                Сбросить
              </button>
            )}
            {mobileDrawer && (
              <button type="button" className="media-filter-toolbar__close" onClick={onClose} aria-label="Закрыть фильтры">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" d="m6 6 12 12M18 6 6 18" /></svg>
              </button>
            )}
          </div>
        </div>
      )}

      {(activeFilterChips.length > 0 || (compactHeader && !mobileDrawer && activeFiltersCount > 0 && onReset)) && (
        <div className="media-filter-toolbar__chips" aria-label="Активные фильтры">
          {activeFilterChips.map((chip) => (
            <button
              type="button"
              className="media-filter-toolbar__chip"
              key={chip.key}
              onClick={() => onRemoveFilter?.(chip.key)}
              aria-label={`Убрать фильтр: ${chip.label}`}
            >
              {chip.label}<span aria-hidden="true">×</span>
            </button>
          ))}
          {compactHeader && !mobileDrawer && activeFiltersCount > 0 && onReset && (
            <button type="button" className="media-filter-toolbar__reset" onClick={onReset}>
              Сбросить
            </button>
          )}
        </div>
      )}

      <div className="media-filter-toolbar__grid">
        {showType && (
          <fieldset className="media-filter-toolbar__field media-filter-toolbar__field--type">
            <legend>Тип контента</legend>
            <div className="media-filter-toolbar__segmented">
              {TYPE_OPTIONS.map((option) => (
                <button
                  key={option.value}
                  type="button"
                  className={type === option.value ? "is-active" : ""}
                  onClick={() => onTypeChange?.(option.value)}
                  aria-pressed={type === option.value}
                >
                  {option.label}
                </button>
              ))}
            </div>
          </fieldset>
        )}

        {showSort && (
        <label className="media-filter-toolbar__field media-filter-toolbar__field--sort">
          <span>Сортировка</span>
          <select value={sortBy} onChange={(event) => onSortChange?.(event.target.value)}>
            {SORT_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>{option.label}</option>
            ))}
          </select>
        </label>
        )}

        <div className="media-filter-toolbar__field media-filter-toolbar__field--genre">
          <GenreFilter
            genres={genres}
            includedIds={includedIds}
            excludedIds={excludedIds}
            onChange={onGenresChange}
            disabled={genreDisabled}
            disabledMessage={genreDisabledMessage}
            idPrefix={`${idPrefix}-genre`}
          />
        </div>

        <fieldset className="media-filter-toolbar__field media-filter-toolbar__field--years" disabled={discoverOnlyDisabled}>
          <legend>Годы выпуска</legend>
          <div className="media-filter-toolbar__range" aria-label="Диапазон годов выпуска">
            <div className="media-filter-toolbar__range-track" />
            <input
              type="range"
              min={YEAR_MIN}
              max={YEAR_MAX}
              value={selectedYearFrom}
              aria-label="Минимальный год выпуска"
              onChange={(event) => onYearRangeChange?.({
                from: Math.min(Number(event.target.value), selectedYearTo),
                to: selectedYearTo,
              })}
            />
            <input
              type="range"
              min={YEAR_MIN}
              max={YEAR_MAX}
              value={selectedYearTo}
              aria-label="Максимальный год выпуска"
              onChange={(event) => onYearRangeChange?.({
                from: selectedYearFrom,
                to: Math.max(Number(event.target.value), selectedYearFrom),
              })}
            />
          </div>
          <div className="media-filter-toolbar__year-fields">
            <span>От <strong>{selectedYearFrom}</strong></span>
            <span>До <strong>{selectedYearTo}</strong></span>
          </div>
          <div className="media-filter-toolbar__presets" aria-label="Быстрые диапазоны годов">
            {yearPresets.map((preset) => {
              const isActive = selectedYearFrom === preset.from && selectedYearTo === preset.to;
              return (
                <button
                  type="button"
                  key={preset.label}
                  className={isActive ? "is-active" : ""}
                  aria-pressed={isActive}
                  onClick={() => onYearRangeChange?.({ from: preset.from, to: preset.to })}
                >
                  {preset.label}
                </button>
              );
            })}
          </div>
        </fieldset>

        <fieldset className="media-filter-toolbar__field media-filter-toolbar__field--rating" disabled={discoverOnlyDisabled}>
          <legend>Рейтинг от <strong>{selectedRating > 0 ? `${selectedRating.toFixed(1).replace(".0", "")}+` : "любой"}</strong></legend>
          <div className="media-filter-toolbar__rating-control">
            <input
              type="range"
              min="0"
              max="10"
              step="0.5"
              value={selectedRating}
              style={{ "--rating-progress": selectedRating * 10 }}
              aria-label="Минимальный рейтинг"
              onChange={(event) => onMinRatingChange?.(event.target.value === "0" ? "" : event.target.value)}
            />
            <div className="media-filter-toolbar__rating-scale" aria-hidden="true">
              <span>0</span>
              <span>5</span>
              <span>10</span>
            </div>
          </div>
          <div className="media-filter-toolbar__rating-presets" aria-label="Быстрый минимальный рейтинг">
            <button type="button" className={selectedRating === 0 ? "is-active" : ""} aria-pressed={selectedRating === 0} onClick={() => onMinRatingChange?.("")}>Любой</button>
            {ratingPresets.map((rating) => (
              <button type="button" key={rating} className={selectedRating === rating ? "is-active" : ""} aria-pressed={selectedRating === rating} onClick={() => onMinRatingChange?.(String(rating))}>{rating}+</button>
            ))}
          </div>
        </fieldset>

        <label className="media-filter-toolbar__released-toggle">
          <input
            type="checkbox"
            checked={releasedOnly}
            disabled={discoverOnlyDisabled}
            onChange={(event) => onReleasedOnlyChange?.(event.target.checked)}
          />
          <span className="media-filter-toolbar__checkbox-mark" aria-hidden="true" />
          <span>
            <strong>Только вышедшие</strong>
            <small>Без будущих релизов</small>
          </span>
        </label>

      </div>

      <div className="media-filter-toolbar__advanced-toggle">
        <button type="button" onClick={() => setAdvancedOpen((open) => !open)} aria-expanded={advancedOpen}>
          {advancedOpen ? "Скрыть дополнительные фильтры" : "Дополнительные фильтры"}
          <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d={advancedOpen ? "m6 15 6-6 6 6" : "m6 9 6 6 6-6"} /></svg>
        </button>
      </div>

      {advancedOpen && (
        <div className="media-filter-toolbar__advanced-grid">
          <label className="media-filter-toolbar__field">
            <span>Голосов от</span>
            <input
              type="number"
              disabled={discoverOnlyDisabled}
              min="0"
              max="1000000000"
              step="1"
              inputMode="numeric"
              placeholder="Например, 200"
              value={voteCountGte}
              onChange={(event) => onVoteCountGteChange?.(event.target.value)}
            />
          </label>

          <label className="media-filter-toolbar__field">
            <span>Страна происхождения</span>
            <input
              type="text"
              disabled={discoverOnlyDisabled}
              maxLength="2"
              inputMode="text"
              placeholder="US"
              value={country}
              onChange={(event) => onCountryChange?.(event.target.value.toUpperCase())}
            />
          </label>
        </div>
      )}

      {mobileDrawer && (
        <div className="media-filter-toolbar__mobile-actions">
          <button type="button" className="media-filter-toolbar__mobile-reset" onClick={onReset}>Сбросить</button>
          <button type="button" className="media-filter-toolbar__mobile-apply" onClick={onApply || onClose}>Применить</button>
        </div>
      )}
    </MediaToolbar>
  );
}