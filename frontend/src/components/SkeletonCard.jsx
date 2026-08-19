// Скелетон-карточка для состояния загрузки — с анимацией блика

export default function SkeletonCard() {
  return (
    <div className="media-card-link">
      <article className="media-card">
        <div className="media-card__poster-wrapper">
          <div className="media-card__skeleton" />
        </div>

        <div className="media-card__content">
          <div className="skeleton-text skeleton-text--title" />
          <div className="skeleton-text skeleton-text--meta" />
          <div className="skeleton-text skeleton-text--genres" />
          <div className="skeleton-text skeleton-text--overview" />
          <div className="skeleton-text skeleton-text--overview skeleton-text--overview-short" />
          <div className="skeleton-text skeleton-text--action" />
        </div>
      </article>
    </div>
  );
}