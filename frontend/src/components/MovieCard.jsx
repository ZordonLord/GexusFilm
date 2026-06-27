// Компонент для отображения карточки фильма

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
          className={`movie-card__poster ${loaded ? "loaded" : ""}`}
          src={poster}
          alt={title}
          loading="lazy"
          onLoad={() => setLoaded(true)}
        />

        <div className="movie-card__gradient" />

        <div className="movie-card__year">
          {year}
        </div>

        <div className="movie-card__rating">
          ⭐ {rating}
        </div>

        <div className="movie-card__title">
          {title}
        </div>

      </article>

    </Link>

  );

}