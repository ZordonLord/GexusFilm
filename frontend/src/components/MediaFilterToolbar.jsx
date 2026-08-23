import { MediaToolbar } from "./MediaBrowser";
import GenreFilter from "./GenreFilter";
import { SORT_OPTIONS, TYPE_OPTIONS } from "../utils/catalogFilters";

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
  genres = [],
  includedIds = [],
  excludedIds = [],
  onGenresChange,
  genreDisabled = false,
  genreDisabledMessage = "",
  year = "",
  onYearChange,
  minRating = "",
  onMinRatingChange,
  region = "",
  onRegionChange,
  idPrefix = "media-filter",
  className = "",
}) {
  const headingId = `${idPrefix}-heading`;

  return (
    <MediaToolbar
      className={`media-filter-toolbar ${className}`.trim()}
      aria-labelledby={headingId}
    >
      <div className="media-filter-toolbar__head">
        <h2 id={headingId}>{heading}</h2>
        <div className="media-filter-toolbar__status">
          <span aria-live="polite">
            {activeFiltersCount > 0 ? `${activeFiltersCount} активно` : "Настройте выдачу"}
          </span>
          {activeFiltersCount > 0 && onReset && (
            <button type="button" className="media-filter-toolbar__reset" onClick={onReset}>
              Сбросить фильтры
            </button>
          )}
        </div>
      </div>

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

        <label className="media-filter-toolbar__field">
          <span>Сортировка</span>
          <select value={sortBy} onChange={(event) => onSortChange?.(event.target.value)}>
            {SORT_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>{option.label}</option>
            ))}
          </select>
        </label>

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

        <label className="media-filter-toolbar__field">
          <span>Год выпуска</span>
          <input
            type="number"
            min="1870"
            max="2100"
            inputMode="numeric"
            placeholder="Например, 2024"
            value={year}
            onChange={(event) => onYearChange?.(event.target.value)}
          />
        </label>

        <label className="media-filter-toolbar__field">
          <span>Рейтинг от</span>
          <input
            type="number"
            min="0"
            max="10"
            step="0.5"
            inputMode="decimal"
            placeholder="0–10"
            value={minRating}
            onChange={(event) => onMinRatingChange?.(event.target.value)}
          />
        </label>

        <label className="media-filter-toolbar__field">
          <span>Регион</span>
          <input
            type="text"
            maxLength="2"
            inputMode="text"
            placeholder="RU"
            value={region}
            onChange={(event) => onRegionChange?.(event.target.value.toUpperCase())}
          />
        </label>
      </div>
    </MediaToolbar>
  );
}