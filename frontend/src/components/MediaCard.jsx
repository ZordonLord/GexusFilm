// Универсальная карточка медиа (фильм / сериал)

import { Link } from "react-router-dom";
import { useState } from "react";

import {
  getMediaId,
  getMediaTitle,
  getMediaYear,
  getMediaRoute,
  getMediaType,
  getMediaTypeLabel,
  getMediaGenres,
  formatRating,
} from "../utils/media";

import "../styles/MediaCard.css";

export default function MediaCard({
  item,
  mediaType = "movie",
}) {
  const [loaded, setLoaded] = useState(false);
  const [imageFailed, setImageFailed] = useState(false);

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
  const genres = getMediaGenres(item).slice(0, 3);
  const overview = item.overview?.trim();
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
          {poster && !loaded && !imageFailed && (
            <div className="media-card__skeleton" />
          )}

          {poster && !imageFailed ? (
            <img
              src={poster}
              alt={title}
              loading="lazy"
              className={`media-card__poster ${loaded ? "is-loaded" : ""}`}
              onLoad={() => setLoaded(true)}
              onError={() => setImageFailed(true)}
            />
          ) : (
            <div className="media-card__poster-placeholder" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                <path strokeLinecap="round" strokeLinejoin="round" d="m4 16 4.5-4.5a2 2 0 0 1 2.83 0L16 16m-2-2 1.5-1.5a2 2 0 0 1 2.83 0L20 12M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" />
              </svg>
            </div>
          )}
        </div>

        <div className="media-card__content">
          <div className="media-card__heading">
            <h3 className="media-card__title">{title}</h3>

            <div className="media-card__meta">
              <span>{getMediaTypeLabel(type)}</span>
              <span aria-hidden="true">•</span>
              <span>{year}</span>
              {rating !== "—" && (
                <>
                  <span aria-hidden="true">•</span>
                  <span className="media-card__rating">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                      <path d="m12 3 2.78 5.63 6.22.9-4.5 4.39 1.06 6.2L12 17.2l-5.56 2.92 1.06-6.2L3 9.53l6.22-.9L12 3Z" />
                    </svg>
                    {rating}
                  </span>
                </>
              )}
            </div>
          </div>

          {genres.length > 0 && (
            <div className="media-card__genres">
              {genres.map((genre) => (
                <span key={genre} className="media-card__genre">{genre}</span>
              ))}
            </div>
          )}

          {overview && <p className="media-card__overview">{overview}</p>}

          <span className="media-card__action">
            Подробнее
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path strokeLinecap="round" strokeLinejoin="round" d="M5 12h14m-6-6 6 6-6 6" />
            </svg>
          </span>
        </div>

      </article>
    </Link>
  );
}
