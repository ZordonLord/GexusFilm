import "../styles/GenreFilter.css";

const MAX_GENRES = 5;

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

  return (
    <fieldset className="genre-filter" disabled={disabled}>
      <legend className="genre-filter__legend">Жанры</legend>

      <div className="genre-filter__options" aria-describedby={`${idPrefix}-help`}>
        {genres.map((genre) => {
          const id = `${idPrefix}-${genre.id}`;
          const normalizedId = String(genre.id);
          const included = includedSet.has(normalizedId);
          const excluded = excludedSet.has(normalizedId);
          const includeLimitReached = includedIds.length >= MAX_GENRES && !included;
          const excludeLimitReached = excludedIds.length >= MAX_GENRES && !excluded;

          return (
            <div className={`genre-filter__option${included ? " is-included" : ""}${excluded ? " is-excluded" : ""}`} key={genre.id}>
              <span>{genre.name}</span>
              <div className="genre-filter__actions">
                <button
                  id={`${id}-include`}
                  type="button"
                  className={included ? "is-active" : ""}
                  aria-label={`Добавить жанр ${genre.name}`}
                  aria-pressed={included}
                  disabled={includeLimitReached}
                  onClick={() => handleGenreToggle(genre.id, "include")}
                >
                  +
                </button>
                <button
                  id={`${id}-exclude`}
                  type="button"
                  className={excluded ? "is-active" : ""}
                  aria-label={`Исключить жанр ${genre.name}`}
                  aria-pressed={excluded}
                  disabled={excludeLimitReached}
                  onClick={() => handleGenreToggle(genre.id, "exclude")}
                >
                  −
                </button>
              </div>
            </div>
          );
        })}
      </div>

      <div className="genre-filter__summary" aria-live="polite">
        <span id={`${idPrefix}-help`}>
          {includedIds.length} добавлено · {excludedIds.length} исключено
        </span>
        {(includedGenres.length > 0 || excludedGenres.length > 0) && (
          <div className="genre-filter__chips" aria-label="Активные жанры">
            {includedGenres.map((genre) => (
              <span className="genre-filter__chip genre-filter__chip--included" key={`included-${genre.id}`}>
                <span aria-hidden="true">+</span> {genre.name}
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
                <span aria-hidden="true">−</span> {genre.name}
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