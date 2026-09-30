// Hero-секция главной страницы — большой баннер с трендовым контентом

import { useEffect } from "react";
import { Link } from "react-router-dom";
import {
  getMediaId,
  getMediaTitle,
  getMediaYear,
  getMediaRoute,
  formatRating,
} from "../utils/media";

import "../styles/Hero.css";

export default function Hero({
  items = [],
  mediaType = "movie",
  loading = false,
  error = false,
  onRetry,
  activeIndex: activeIndexProp = 0,
  onActiveIndexChange,
  compact = false,
}) {
  const visibleItems = items.slice(0, 5);
  const activeIndex = Math.min(activeIndexProp, Math.max(visibleItems.length - 1, 0));
  const item = visibleItems[activeIndex];

  useEffect(() => {
    if (visibleItems.length < 2) return undefined;
    const timer = setInterval(() => {
      onActiveIndexChange?.((activeIndex + 1) % visibleItems.length);
    }, 6000);
    return () => clearInterval(timer);
  }, [activeIndex, onActiveIndexChange, visibleItems.length]);

  if (loading) {
    return (
      <section className="home-hero home-hero--loading" aria-label="Загрузка главной подборки">
        <div className="home-hero__loading-content">
          <span className="home-hero__loading-line home-hero__loading-line--eyebrow" />
          <span className="home-hero__loading-line home-hero__loading-line--title" />
          <span className="home-hero__loading-line home-hero__loading-line--copy" />
          <span className="home-hero__loading-button" />
        </div>
      </section>
    );
  }

  if (error && !item) {
    return (
      <section className="home-hero home-hero--state" role="status">
        <div className="home-hero__state-content">
          <span className="home-hero__eyebrow">GexusFilm</span>
          <h1>Выберите фильм для сегодняшнего вечера</h1>
          <p>Подборка временно недоступна, но каталог и поиск помогут найти контент вручную.</p>
          <div className="home-hero__actions">
            <Link className="home-hero__button home-hero__button--primary" to="/search">Открыть поиск</Link>
            {onRetry && (
              <button className="home-hero__button home-hero__button--secondary" type="button" onClick={onRetry}>
                Повторить
              </button>
            )}
          </div>
        </div>
      </section>
    );
  }

  if (!item) {
    return (
      <section className="home-hero home-hero--state" role="status">
        <div className="home-hero__state-content">
          <span className="home-hero__eyebrow">GexusFilm</span>
          <h1>Найдите, что посмотреть сегодня</h1>
          <p>Откройте каталог и сузьте выбор по типу, жанру, году и рейтингу.</p>
          <Link className="home-hero__button home-hero__button--primary" to="/discover">Открыть каталог</Link>
        </div>
      </section>
    );
  }

  const id = getMediaId(item);

  if (!id) {
    return null;
  }

  const title = getMediaTitle(item);
  const year = getMediaYear(item);
  const type = item.media_type || mediaType;
  const poster = item.poster_path
    ? `https://image.tmdb.org/t/p/w500${item.poster_path}`
    : null;
  const rating = formatRating(item.vote_average);
  const overview = item.overview || "";
  const backdrop = item.backdrop_path
    ? `https://image.tmdb.org/t/p/original${item.backdrop_path}`
    : null;

  return (
    <section className={`home-hero ${compact ? "home-hero--compact" : ""}`} aria-labelledby="home-hero-title">
      <div className="home-hero__background" aria-hidden="true">
        <div className="home-hero__backdrop-placeholder" />
        {backdrop && (
          <img
            key={backdrop}
            src={backdrop}
            alt=""
            className="home-hero__backdrop"
            onLoad={(event) => event.currentTarget.classList.add("is-loaded")}
          />
        )}
      </div>
      <div className="home-hero__scrim" aria-hidden="true" />

      <div className="home-hero__content">
        <div className="home-hero__inner">
          <div className="home-hero__poster-wrap">
            <div className="home-hero__poster">
              {poster && (
                <img
                  src={poster}
                  alt={title}
                  className="home-hero__poster-image"
                  loading="eager"
                  onError={(event) => { event.currentTarget.remove(); }}
                />
              )}
            </div>
          </div>

          <div className="home-hero__info">
            <div className="home-hero__eyebrow">Сейчас в тренде</div>

            <div className="home-hero__meta">
              <span className="home-hero__rating">
                <svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                {rating}
              </span>
              <span>{year}</span>
              <span className="home-hero__type">
                {type === "movie" ? "Фильм" : "Сериал"}
              </span>
            </div>

            <h1 id="home-hero-title" className="home-hero__title">
              {title}
            </h1>

            <p className="home-hero__overview">{overview}</p>

            <div className="home-hero__actions">
              <Link
                to={getMediaRoute(type, id)}
                className="home-hero__button home-hero__button--primary"
              >
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                </svg>
                Подробнее
              </Link>

              <Link className="home-hero__button home-hero__button--secondary" to="/discover">
                Открыть каталог
              </Link>
            </div>
          </div>
        </div>
      </div>

      {visibleItems.length > 1 && (
        <div className="home-hero__dots" aria-label="Выбор трендовой подборки">
          {visibleItems.map((visibleItem, index) => (
            <button
              key={getMediaId(visibleItem) || index}
              type="button"
              className={`home-hero__dot ${index === activeIndex ? "is-active" : ""}`}
              onClick={() => onActiveIndexChange?.(index)}
              aria-label={`Показать ${getMediaTitle(visibleItem)}`}
              aria-pressed={index === activeIndex}
            />
          ))}
        </div>
      )}
    </section>
  );
}