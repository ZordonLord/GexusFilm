export default function MovieCard({
  title,
  year,
  rating,
  poster,
}) {
  const displayRating = rating === "0.0" ? "-" : rating;

  return (
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
  );
}