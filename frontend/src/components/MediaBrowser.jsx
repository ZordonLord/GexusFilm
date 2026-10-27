import MediaCard from "./MediaCard";
import SkeletonCard from "./SkeletonCard";
import { getMediaId, getMediaKey, getMediaTitle } from "../utils/media";
import { getTmdbImageUrl } from "../utils/tmdbImage";
import { getPaginationItems } from "../utils/pagination";

import "../styles/MediaBrowser.css";

export function MediaToolbar({ children, className = "", ...props }) {
  return <section className={`media-toolbar ${className}`.trim()} {...props}>{children}</section>;
}

function StateIcon({ kind }) {
  if (kind === "error") {
    return (
      <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v4m0 4h.01M10.3 3.85 2.6 17a2 2 0 0 0 1.74 3h15.32a2 2 0 0 0 1.74-3l-7.7-13.15a2 2 0 0 0-3.4 0Z" />
      </svg>
    );
  }

  return (
    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
      <path strokeLinecap="round" strokeLinejoin="round" d="m21 21-4.35-4.35m2.1-5.15a7.25 7.25 0 1 1-14.5 0 7.25 7.25 0 0 1 14.5 0Z" />
    </svg>
  );
}

function MediaState({ kind, title, message, onRetry }) {
  return (
    <div className={`media-browser__state media-browser__state--${kind}`} role={kind === "error" ? "alert" : undefined}>
      <StateIcon kind={kind} />
      <h2>{title}</h2>
      <p>{message}</p>
      {kind === "error" && onRetry && (
        <button type="button" onClick={onRetry}>Повторить</button>
      )}
    </div>
  );
}

function MediaPagination({ page, totalPages, onPageChange }) {
  if (!onPageChange || totalPages <= 1) return null;

  const paginationItems = getPaginationItems(page, totalPages);

  return (
    <nav className="media-browser__pagination" aria-label="Пагинация результатов">
      <button type="button" onClick={() => onPageChange(Math.max(1, page - 1))} disabled={page <= 1}>
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="m15 18-6-6 6-6" /></svg>
        Назад
      </button>
      <span className="media-browser__page-list" role="group" aria-label={`Страница ${page} из ${totalPages}`}>
        {paginationItems.map((item, index) => item === "ellipsis" ? (
          <span className="media-browser__page-ellipsis" key={`ellipsis-${index}`} aria-hidden="true">…</span>
        ) : (
          <button
            type="button"
            key={item}
            className={`media-browser__page-number${item === page ? " is-current" : ""}`}
            onClick={() => onPageChange(item)}
            aria-label={`Страница ${item}`}
            aria-current={item === page ? "page" : undefined}
          >
            {item}
          </button>
        ))}
      </span>
      <button type="button" onClick={() => onPageChange(Math.min(totalPages, page + 1))} disabled={page >= totalPages}>
        Вперёд
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="m9 18 6-6-6-6" /></svg>
      </button>
    </nav>
  );
}

function PersonCard({ person }) {
  const id = getMediaId(person);
  if (!id) return null;

  const name = getMediaTitle(person);
  const profile = getTmdbImageUrl(person.profile_path, "w500");
  const knownFor = Array.isArray(person.known_for)
    ? person.known_for.map((item) => item.title || item.name).filter(Boolean).slice(0, 3)
    : [];

  return (
              <a
                className="media-card-link media-card-link--person"
      href={`https://www.themoviedb.org/person/${encodeURIComponent(id)}`}
      target="_blank"
      rel="noreferrer"
      key={`person-${id}`}
    >
      <article className="media-card media-card--person">
        <div className="media-card__poster-wrapper media-card__poster-wrapper--person">
          {profile ? <img className="media-card__poster is-loaded" src={profile} alt="" loading="lazy" /> : (
            <div className="media-card__poster-placeholder" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                <path strokeLinecap="round" strokeLinejoin="round" d="M20 21a8 8 0 0 0-16 0m8-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
              </svg>
            </div>
          )}
        </div>
        <div className="media-card__content">
          <div className="media-card__heading">
            <h3 className="media-card__title">{name}</h3>
            <div className="media-card__meta"><span>Актёр</span></div>
          </div>
          {knownFor.length > 0 && <p className="media-card__overview">Известен по: {knownFor.join(", ")}</p>}
          <span className="media-card__action">Профиль TMDB ↗</span>
        </div>
      </article>
    </a>
  );
}

function ResultItem({ item, mediaType, compactCards }) {
  if (item.media_type === "person") return <PersonCard key={`person-${getMediaId(item)}`} person={item} />;

  const itemType = item.media_type || (mediaType === "all" ? "movie" : mediaType);
  const key = getMediaKey(item, itemType);

  return key ? <MediaCard key={key} item={item} mediaType={itemType} compact={compactCards} /> : null;
}

export default function MediaBrowser({
  items = [],
  resultGroups,
  loading = false,
  error = "",
  onRetry,
  mediaType = "movie",
  resultsTitle,
  totalResults,
  emptyTitle = "Ничего не найдено",
  emptyMessage = "Попробуйте изменить фильтры.",
  errorTitle = "Не получилось загрузить результаты",
  skeletonCount = 12,
  page = 1,
  totalPages = 0,
  onPageChange,
  variant = "default",
  viewMode = "list",
  compactCards = false,
}) {
  return (
    <section className={`media-browser media-browser--${variant} media-browser--${viewMode}`} aria-busy={loading}>
      {resultsTitle && (
        <div className="media-browser__results-head" aria-live="polite">
          <h2>{resultsTitle}</h2>
          {!loading && !error && totalResults !== undefined && <span>{totalResults} результатов</span>}
        </div>
      )}

      {error ? (
        <MediaState kind="error" title={errorTitle} message={error} onRetry={onRetry} />
      ) : (
        loading ? (
          <div className="media-browser__grid">
            {Array.from({ length: skeletonCount }).map((_, index) => <SkeletonCard key={`media-browser-skeleton-${index}`} compact={compactCards} />)}
          </div>
        ) : resultGroups ? (
          <div className="media-browser__groups">
            {resultGroups.filter((group) => group.items.length > 0).map((group) => (
              <section className="media-browser__group" key={group.key} aria-labelledby={`search-results-${group.key}`}>
                <h3 className="media-browser__group-title" id={`search-results-${group.key}`}>{group.title}</h3>
                <div className="media-browser__grid media-browser__group-grid">
                  {group.items.map((item) => <ResultItem key={`${group.key}-${getMediaId(item)}`} item={item} mediaType={group.key} compactCards={compactCards} />)}
                </div>
              </section>
            ))}
            {items.length === 0 && <MediaState kind="empty" title={emptyTitle} message={emptyMessage} />}
          </div>
        ) : (
          <div className="media-browser__grid">
            {items.length > 0
              ? items.map((item) => <ResultItem key={getMediaKey(item, mediaType)} item={item} mediaType={mediaType} compactCards={compactCards} />)
              : <MediaState kind="empty" title={emptyTitle} message={emptyMessage} />}
          </div>
        )
      )}

      {!loading && !error && items.length > 0 && (
        <MediaPagination page={page} totalPages={totalPages} onPageChange={onPageChange} />
      )}
    </section>
  );
}