// Компонент для отображения карточки фильма

import { Link } from "react-router-dom";

export default function MovieCard({
  id,
  title,
  year,
  rating,
  poster,
}) {
  const displayRating = rating === "0.0" ? "-" : rating;
  
  return (
    <Link
      to={`/movie/${id}`}
      className="movie-link"
    >
      <div className="movie-card">
        <img
          src={poster}
          alt={title}
        />

        <div className="movie-info">
          <div className="movie-rating">
            ⭐ {displayRating}
          </div>

          <h4>{title}</h4>

          <span>{year}</span>
        </div>
      </div>
    </Link>
  );
}