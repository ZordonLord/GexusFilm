// Универсальный hero-блок медиа (фильм / сериал)

import { useState } from "react";
import { Link } from "react-router-dom";

import {
  getMediaTitle,
  getMediaYear,
  getMediaGenres,
  getMediaTypeLabel,
  formatRating,
} from "../utils/media";
import { getTmdbImageUrl } from "../utils/tmdbImage";

import "../styles/MediaHero.css";

function Icon({ name }) {
  const paths = {
    star: <path d="m12 3 2.7 5.47 6.03.88-4.36 4.25 1.03 6.01L12 16.77l-5.4 2.84 1.03-6.01-4.36-4.25 6.03-.88L12 3Z" />,
    calendar: <><rect x="3" y="5" width="18" height="16" rx="2" /><path d="M16 3v4M8 3v4M3 11h18" /></>,
    clock: <><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></>,
    catalog: <><rect x="3" y="3" width="8" height="8" rx="1.5" /><rect x="13" y="3" width="8" height="8" rx="1.5" /><rect x="3" y="13" width="8" height="8" rx="1.5" /><rect x="13" y="13" width="8" height="8" rx="1.5" /></>,
    arrow: <><path d="M7 17 17 7M8 7h9v9" /></>,
    image: <><rect x="3" y="3" width="18" height="18" rx="2" /><circle cx="8.5" cy="8.5" r="1.5" /><path d="m21 15-5-5L5 21" /></>,
  };

  return (
    <svg
      className="media-hero__icon"
      viewBox="0 0 24 24"
      fill={name === "star" ? "currentColor" : "none"}
      aria-hidden="true"
      focusable="false"
    >
      {paths[name]}
    </svg>
  );
}

function formatReleaseDate(value) {
  if (typeof value !== "string" || !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    return null;
  }

  const date = new Date(`${value}T00:00:00Z`);
  return new Intl.DateTimeFormat("ru-RU", { dateStyle: "long", timeZone: "UTC" }).format(date);
}

function getStatusLabel(status) {
  const labels = {
    Rumored: "Анонсирован",
    Planned: "Запланирован",
    "In Production": "В производстве",
    "Post Production": "Постпродакшн",
    Released: "Вышел",
    Canceled: "Отменён",
  };

  return labels[status] || status;
}

export default function MediaHero({ media, type = "movie" }) {
  const [posterFailed, setPosterFailed] = useState(false);
  const [shareMessage, setShareMessage] = useState("");

  const poster = posterFailed
    ? null
    : getTmdbImageUrl(media.poster_path, "w500");
  const title = getMediaTitle(media);
  const year = getMediaYear(media);
  const rating = formatRating(media.vote_average);
  const genres = getMediaGenres(media);
  const mediaTypeLabel = getMediaTypeLabel(type);
  const runtime = media.runtime || media.episode_run_time?.[0];
  const runtimeLabel = runtime > 0 ? `${runtime} мин` : null;
  const hasRating = rating !== "—";
  const hasYear = year !== "—";
  const overview = media.overview?.trim();
  const originalTitle = media.original_title?.trim();
  const tagline = media.tagline?.trim();
  const releaseDate = formatReleaseDate(media.release_date);
  const status = media.status?.trim() ? getStatusLabel(media.status.trim()) : null;

  async function shareMedia() {
    const shareData = {
      title,
      text: `${title} — ${mediaTypeLabel} на GexusFilm`,
      url: window.location.href,
    };

    try {
      if (navigator.share) {
        await navigator.share(shareData);
      } else if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(shareData.url);
        setShareMessage("Ссылка скопирована");
      } else {
        setShareMessage("Поделиться можно через адресную строку браузера");
      }
    } catch (error) {
      if (error?.name !== "AbortError") {
        setShareMessage("Не удалось поделиться ссылкой");
      }
    }
  }

  return (
    <section className="media-hero" aria-labelledby="media-title">
      <div className="media-hero__poster" aria-label={`Постер: ${title}`}>
        {poster ? (
          <img
            src={poster}
            alt={`Постер: ${title}`}
            width="500"
            height="750"
            fetchPriority="high"
            onError={() => setPosterFailed(true)}
          />
        ) : (
          <div className="media-hero__poster-placeholder" role="img" aria-label="Постер недоступен">
            <Icon name="image" />
            <span>Постер недоступен</span>
          </div>
        )}
      </div>

      <div className="media-hero__content">
        <p className="media-hero__eyebrow">{mediaTypeLabel}</p>
        <h1 className="media-hero__title" id="media-title">{title}</h1>

        {type !== "movie" && tagline && <p className="media-hero__tagline">{tagline}</p>}

        {originalTitle && originalTitle !== title && (
          <p className="media-hero__original-title">
            <span>Оригинальное название</span>
            <strong>{originalTitle}</strong>
          </p>
        )}

        <ul className="media-hero__meta" aria-label="Информация о медиа">
          {hasYear && (
            <li className="media-badge">
              <Icon name="calendar" />
              <span>{year}</span>
            </li>
          )}
          {releaseDate && (
            <li className="media-badge">
              <Icon name="calendar" />
              <span>Премьера: {releaseDate}</span>
            </li>
          )}
          {status && (
            <li className="media-badge media-badge--status">
              <span>{status}</span>
            </li>
          )}
          {hasRating && (
            <li className="media-badge media-badge--rating">
              <Icon name="star" />
              <span>{rating}</span>
              <span className="media-hero__meta-label">TMDB</span>
            </li>
          )}
          {runtimeLabel && (
            <li className="media-badge">
              <Icon name="clock" />
              <span>{runtimeLabel}</span>
            </li>
          )}
          {type === "tv" && media.number_of_seasons > 0 && (
            <li className="media-badge">
              {media.number_of_seasons} {media.number_of_seasons === 1 ? "сезон" : "сезонов"}
            </li>
          )}
        </ul>

        {genres.length > 0 && (
          <div className="media-hero__genres" aria-label="Жанры">
            {genres.map((genre, index) => (
              <span key={genre.id ?? `${genre}-${index}`} className="genre-chip">
                {typeof genre === "string" ? genre : genre.name}
              </span>
            ))}
          </div>
        )}

        <div className="media-hero__summary">
          <h2 className="media-hero__section-title">Кратко</h2>
          {overview ? (
            <p className="media-hero__overview">{overview}</p>
          ) : (
            <p className="media-hero__overview media-hero__overview--muted">
              Описание пока недоступно.
            </p>
          )}
        </div>

        <div className="media-hero__actions">
          <Link className="media-hero__button media-hero__button--primary" to="/discover">
            <Icon name="catalog" />
            <span>Перейти в каталог</span>
          </Link>
          <button
            className="media-hero__button media-hero__button--secondary"
            type="button"
            onClick={shareMedia}
          >
            <Icon name="arrow" />
            <span>Поделиться</span>
          </button>
        </div>
        <p className="media-hero__share-status" role="status" aria-live="polite">
          {shareMessage}
        </p>
      </div>
    </section>
  );
}