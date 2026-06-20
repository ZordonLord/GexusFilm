import { useEffect, useState } from "react";
import Sidebar from "../components/Sidebar";
import MovieCard from "../components/MovieCard";
import { getTrendingMovies } from "../services/api";

export default function HomePage() {
  const [movies, setMovies] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadMovies();
  }, []);

  async function loadMovies() {
    try {
      const data = await getTrendingMovies();

      setMovies(data.results || []);
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="app-layout">
      <Sidebar />

      <main className="content">
        <h2>Сейчас смотрят</h2>

        {loading ? (
          <p>Загрузка...</p>
        ) : (
          <div className="row">
            {movies.map((movie) => (
              <MovieCard
                key={movie.id}
                title={movie.title}
                year={movie.release_date?.slice(0, 4)}
                rating={movie.vote_average?.toFixed(1)}
                poster={`https://image.tmdb.org/t/p/w500${movie.poster_path}`}
              />
            ))}
          </div>
        )}
      </main>
    </div>
  );
}