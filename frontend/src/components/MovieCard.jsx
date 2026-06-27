// Компонент карточки фильма

import { Link } from "react-router-dom";
import { useState } from "react";

import "../styles/MovieCard.css";

export default function MovieCard({
  id,
  title,
  year,
  rating,
  poster
}) {

  const [loaded, setLoaded] = useState(false);

  return (

    <Link
      to={`/movie/${id}`}
      className="movie-card-link"
    >

      <article className="movie-card">

        {!loaded && (
          <div className="movie-card__skeleton" />
        )}

        <img
          src={poster}
          alt={title}
          loading="lazy"
          className={`movie-card__poster ${loaded ? "loaded" : ""}`}
          onLoad={() => setLoaded(true)}
        />

        <div className="movie-card__overlay">

          <button
            className="movie-card__play"
            aria-label={`Открыть ${title}`}
          >
            ▶
          </button>

        </div>

        <div className="movie-card__rating">
          ⭐ {rating}
        </div>

        <div className="movie-card__info">

          <span className="movie-card__year">
            {year}
          </span>

          <h3 className="movie-card__title">
            {title}
          </h3>

        </div>

      </article>

    </Link>

  );

}