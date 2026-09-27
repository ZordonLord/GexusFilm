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
  voteCountGte = "",
  onVoteCountGteChange,
  country = "",
  onCountryChange,
  idPrefix = "media-filter",
  className = "",
}) {
  const headingId = `${idPrefix}-heading`;
  const selectedYearFrom = Number.parseInt(yearFrom, 10) || YEAR_MIN;
  const selectedYearTo = Number.parseInt(yearTo, 10) || YEAR_MAX;
  const yearOptions = Array.from(
    { length: YEAR_MAX - YEAR_MIN + 1 },
    (_, index) => YEAR_MIN + index,
  );

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

        <fieldset className="media-filter-toolbar__field media-filter-toolbar__field--years" disabled={discoverOnlyDisabled}>
          <legend>Годы выпуска</legend>
          <div className="media-filter-toolbar__year-fields">
            <label htmlFor={`${idPrefix}-year-from`}>
              <span>Год от</span>
              <select
                id={`${idPrefix}-year-from`}
                name={`${idPrefix}-year-from`}
                value={selectedYearFrom}
                onChange={(event) => onYearRangeChange?.({
                  from: Number(event.target.value),
                  to: selectedYearTo,
                })}
              >
                {yearOptions
                  .filter((year) => year <= selectedYearTo)
                  .map((year) => <option key={year} value={year}>{year}</option>)}
              </select>
            </label>
            <label htmlFor={`${idPrefix}-year-to`}>
              <span>Год до</span>
              <select
                id={`${idPrefix}-year-to`}
                name={`${idPrefix}-year-to`}
                value={selectedYearTo}
                onChange={(event) => onYearRangeChange?.({
                  from: selectedYearFrom,
                  to: Number(event.target.value),
                })}
              >
                {yearOptions
                  .filter((year) => year >= selectedYearFrom)
                  .map((year) => <option key={year} value={year}>{year}</option>)}
              </select>
            </label>
          </div>
        </fieldset>

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
    </MediaToolbar>
  );
}