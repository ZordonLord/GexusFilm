// Компонент для отображения ряда фильмов

import MovieCard from "./MovieCard";
import "../styles/MovieRow.css";

export default function MovieRow({
  title,
  movies = [],
  loading = false,
}) {
  return (
    <section className="movie-section">
      <h2>{title}</h2>

      {loading ? (
        <p>Загрузка...</p>
      ) : (
        <div className="row">
          {movies.map((movie) => (
            <MovieCard
              key={movie.id}
              id={movie.id}
              title={movie.title || movie.name}
              year={
                movie.release_date?.slice(0, 4) ||
                movie.first_air_date?.slice(0, 4)
              }
              rating={
                movie.vote_average > 0
                  ? movie.vote_average.toFixed(1)
                  : "—"
              }
              poster={
                movie.poster_path
                  ? `https://image.tmdb.org/t/p/w500${movie.poster_path}`
                  : ""
              }
            />
          ))}
        </div>
      )}
    </section>
  );
}