export default function MovieCard({
  title,
  year,
  rating,
  poster,
}) {
  return (
    <div className="movie-card">
      <img src={poster} alt={title} />

      <div className="movie-info">
        <div className="movie-rating">
          ⭐ {rating}
        </div>

        <h4>{title}</h4>

        <span>{year}</span>
      </div>
    </div>
  );
}