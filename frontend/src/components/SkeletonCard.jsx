// Скелетон-карточка для состояния загрузки — с анимацией блика

export default function SkeletonCard() {
  return (
    <div className="media-card-link">
      <article className="media-card">
        <div className="media-card__poster-wrapper">
          <div className="media-card__skeleton" />
        </div>

        <div className="media-card__info">
          <div className="skeleton-text skeleton-text--short" />
          <div className="skeleton-text skeleton-text--long" />
        </div>
      </article>
    </div>
  );
}