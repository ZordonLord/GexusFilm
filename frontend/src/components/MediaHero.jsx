// Универсальный hero-блок медиа (фильм / сериал)

import {
  getMediaTitle,
  getMediaYear,
  formatRuntime,
  formatRating,
} from "../utils/media";

import "../styles/MediaHero.css";

export default function MediaHero({ media }) {
  const poster = media.poster_path
    ? `https://image.tmdb.org/t/p/w500${media.poster_path}`
    : null;

  const title = getMediaTitle(media);
  const year = getMediaYear(media);
  const runtime = formatRuntime(media.runtime || media.episode_run_time?.[0]);
  const rating = formatRating(media.vote_average);

  return (
    <section className="media-hero">

      <div className="media-hero__poster">
        {poster && <img src={poster} alt={title} />}
      </div>

      <div className="media-hero__content">

        <h1 className="media-hero__title">
          {title}
        </h1>

        <div className="media-hero__meta">
          <div className="media-badge rating">
            ⭐ {rating}
          </div>
          <div className="media-badge">
            📅 {year}
          </div>
          <div className="media-badge">
            ⏱ {runtime}
          </div>
        </div>

        <div className="media-hero__genres">
          {media.genres?.map((genre) => (
            <span
              key={genre.id}
              className="genre-chip"
            >
              {genre.name}
            </span>
          ))}
        </div>

        <p className="media-hero__overview">
          {media.overview}
        </p>

        <div className="media-hero__buttons">
          <button className="btn btn-primary">
            ▶ Смотреть
          </button>
          <button className="btn btn-secondary">
            ❤ В избранное
          </button>
          <button className="btn btn-secondary">
            ↗ Поделиться
          </button>
        </div>

      </div>

    </section>
  );
}
