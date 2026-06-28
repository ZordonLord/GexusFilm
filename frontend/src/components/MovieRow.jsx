// Компонент для отображения ряда фильмов

import { useRef, useState, useEffect } from "react";
import MovieCard from "./MovieCard";

import "../styles/MovieRow.css";

export default function MovieRow({
  title,
  movies = [],
  loading = false,
}) {
  const rowRef = useRef(null);

  const [canLeft, setCanLeft] = useState(false);
  const [canRight, setCanRight] = useState(false);

  const updateButtons = () => {
    const row = rowRef.current;

    if (!row) return;

    setCanLeft(row.scrollLeft > 5);

    setCanRight(
      row.scrollLeft + row.clientWidth < row.scrollWidth - 5
    );
  };

  useEffect(() => {
    updateButtons();

    const row = rowRef.current;

    if (!row) return;

    row.addEventListener("scroll", updateButtons);

    window.addEventListener("resize", updateButtons);

    return () => {
      row.removeEventListener("scroll", updateButtons);
      window.removeEventListener("resize", updateButtons);
    };
  }, [movies]);

  const scroll = (direction) => {
    const row = rowRef.current;

    if (!row) return;

    row.scrollBy({
      left: direction * 520,
      behavior: "smooth",
    });
  };

  return (
    <section className="movie-section">

      <div className="movie-section__header">

        <h2>{title}</h2>

        <div className="movie-row__controls">

          <button
            className={`row-btn ${!canLeft ? "disabled" : ""}`}
            onClick={() => scroll(-1)}
          >
            ❮
          </button>

          <button
            className={`row-btn ${!canRight ? "disabled" : ""}`}
            onClick={() => scroll(1)}
          >
            ❯
          </button>

        </div>

      </div>

      {loading ? (
        <p>Загрузка...</p>
      ) : (
        <div
          className={`row-wrapper
          ${!canLeft ? "hide-left" : ""}
          ${!canRight ? "hide-right" : ""}`}
        >
          <div
            className="row"
            ref={rowRef}
          >
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
        </div>
      )}

    </section>
  );
}