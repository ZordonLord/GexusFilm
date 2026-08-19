// Универсальная карточка медиа (фильм / сериал)

import { Link } from "react-router-dom";
import { useState } from "react";

import {
  getMediaId,
  getMediaTitle,
  getMediaYear,
  getMediaRoute,
  getMediaType,
  formatRating,
} from "../utils/media";

import "../styles/MediaCard.css";

export default function MediaCard({
  item,
  mediaType = "movie",
}) {
  const [loaded, setLoaded] = useState(false);

  if (!item) {
    return null;
  }

  const id = getMediaId(item);

  if (!id) {
    return null;
  }

  const type = getMediaType(item, mediaType);
  const title = getMediaTitle(item);
  const year = getMediaYear(item);
  const rating = formatRating(item.vote_average);
  const poster = item.poster_path
    ? `https://image.tmdb.org/t/p/w500${item.poster_path}`
    : null;

  return (
    <Link
      to={getMediaRoute(type, id)}
      className="media-card-link"
    >
      <article className="media-card">

        <div className="media-card__poster-wrapper">
          {poster && !loaded && (
            <div className="media-card__skeleton" />
          )}

          {poster ? (
            <img
              src={poster}
              alt={title}
              loading="lazy"
              className={`media-card__poster ${loaded ? "loaded" : ""}`}
              onLoad={() => setLoaded(true)}
              onError={() => setLoaded(true)}
            />
          ) : (
            <div className="media-card__poster-placeholder" aria-hidden="true" />
          )}
        </div>

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
