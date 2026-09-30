// Скелетон-карточка для состояния загрузки — с анимацией блика

export default function SkeletonCard({ compact = false }) {
  return (
    <div className={`media-card-link ${compact ? "media-card-link--compact" : ""}`}>
      <article className="media-card">
        <div className="media-card__poster-wrapper">
          <div className="media-card__skeleton" />
        </div>

        <div className="media-card__content">
          <div className="skeleton-text skeleton-text--title" />
          <div className="skeleton-text skeleton-text--meta" />
          {!compact && <div className="skeleton-text skeleton-text--genres" />}
          {!compact && <div className="skeleton-text skeleton-text--overview" />}
          {!compact && <div className="skeleton-text skeleton-text--overview skeleton-text--overview-short" />}
          {!compact && <div className="skeleton-text skeleton-text--action" />}
        </div>
      </article>
    </div>
  );
}