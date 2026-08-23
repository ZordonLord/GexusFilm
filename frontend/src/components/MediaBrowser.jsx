import MediaCard from "./MediaCard";
import SkeletonCard from "./SkeletonCard";
import { getMediaKey } from "../utils/media";

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

  return (
    <nav className="media-browser__pagination" aria-label="Пагинация результатов">
      <button type="button" onClick={() => onPageChange(Math.max(1, page - 1))} disabled={page <= 1}>
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="m15 18-6-6 6-6" /></svg>
        Назад
      </button>
      <span>Страница {page} из {totalPages}</span>
      <button type="button" onClick={() => onPageChange(Math.min(totalPages, page + 1))} disabled={page >= totalPages}>
        Вперёд
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="m9 18 6-6-6-6" /></svg>
      </button>
    </nav>
  );
}

export default function MediaBrowser({
  items = [],
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
}) {
  return (
    <section className={`media-browser media-browser--${variant}`} aria-busy={loading}>
      {resultsTitle && (
        <div className="media-browser__results-head" aria-live="polite">
          <h2>{resultsTitle}</h2>
          {!loading && !error && totalResults !== undefined && <span>{totalResults} результатов</span>}
        </div>
      )}

      {error ? (
        <MediaState kind="error" title={errorTitle} message={error} onRetry={onRetry} />
      ) : (
        <div className="media-browser__grid">
          {loading
            ? Array.from({ length: skeletonCount }).map((_, index) => <SkeletonCard key={`media-browser-skeleton-${index}`} />)
            : items.length > 0
              ? items.map((item) => {
                  const itemType = item.media_type || (mediaType === "all" ? "movie" : mediaType);
                  const key = getMediaKey(item, itemType);

                  return key ? <MediaCard key={key} item={item} mediaType={itemType} /> : null;
                })
              : <MediaState kind="empty" title={emptyTitle} message={emptyMessage} />}
        </div>
      )}

      {!loading && !error && items.length > 0 && (
        <MediaPagination page={page} totalPages={totalPages} onPageChange={onPageChange} />
      )}
    </section>
  );
}