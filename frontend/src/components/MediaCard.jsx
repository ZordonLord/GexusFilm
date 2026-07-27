// Универсальная карточка медиа (фильм / сериал)

import { Link } from "react-router-dom";
import { useState } from "react";

import { getMediaTitle, getMediaYear, getMediaRoute } from "../utils/media";

import "../styles/MediaCard.css";

export default function MediaCard({
  item,
  mediaType = "movie",
}) {
  const [loaded, setLoaded] = useState(false);

  if (!item) {
    return null;
  }

  const type = item.media_type || mediaType;
  const title = getMediaTitle(item);
  const year = getMediaYear(item);
  const rating = item.vote_average > 0
    ? item.vote_average.toFixed(1)
    : "—";
  const poster = item.poster_path
    ? `https://image.tmdb.org/t/p/w500${item.poster_path}`
    : "";

  return (
    <Link
      to={getMediaRoute(type, item.id)}
      className="media-card-link"
    >
      <article className="media-card">

        {!loaded && (
          <div className="media-card__skeleton" />
        )}

        <img
          src={poster}
          alt={title}
          loading="lazy"
          className={`media-card__poster ${loaded ? "loaded" : ""}`}
          onLoad={() => setLoaded(true)}
        />

        <div className="media-card__overlay">
          <button
            className="media-card__play"
            aria-label={`Открыть ${title}`}
          >
            ▶
          </button>
        </div>

        <div className="media-card__rating">
          ⭐ {rating}
        </div>

        <div className="media-card__info">
          <span className="media-card__year">
            {year}
          </span>
          <h3 className="media-card__title">
            {title}
          </h3>
        </div>

      </article>
    </Link>
  );
}
