import Sidebar from "../components/Sidebar";
import MovieCard from "../components/MovieCard";
import Header from "../components/Header";

const movies = [
  {
    id: 1,
    title: "Интерстеллар",
    year: 2014,
    rating: 8.7,
    poster:
      "https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg",
  },
  {
    id: 2,
    title: "Дюна",
    year: 2021,
    rating: 8.0,
    poster:
      "https://image.tmdb.org/t/p/w500/d5NXSklXo0qyIYkgV94XAgMIckC.jpg",
  },
  {
    id: 3,
    title: "Оппенгеймер",
    year: 2023,
    rating: 8.4,
    poster:
      "https://image.tmdb.org/t/p/w500/ptpr0kGAckfQkJeJIt8st5dglvd.jpg",
  },
];

export default function HomePage() {
  return (
    <div className="app-layout">
      <Sidebar />

      <main className="content">
        <Header />

        <h2>Сейчас смотрят</h2>

        <div className="row">
          <div className="row">
            {movies.map((movie) => (
              <MovieCard
                key={movie.id}
                {...movie}
              />
            ))}
          </div>
        </div>

        <h2>Последние добавления</h2>

        <div className="row">
          <div className="row">
            {movies.map((movie) => (
              <MovieCard
                key={movie.id}
                {...movie}
              />
            ))}
          </div>
        </div>

        <h2>🔥 Огонь</h2>

        <div className="row">
          <div className="row">
            {movies.map((movie) => (
              <MovieCard
                key={movie.id}
                {...movie}
              />
            ))}
          </div>
        </div>
      </main>
    </div>
  );
}