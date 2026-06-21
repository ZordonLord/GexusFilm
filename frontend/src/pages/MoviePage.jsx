import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import { getMovie } from "../services/api";

export default function MoviePage() {
    const { id } = useParams();

    const [movie, setMovie] = useState(null);

    useEffect(() => {
        loadMovie();
    }, [id]);

    async function loadMovie() {
        const data = await getMovie(id);
        setMovie(data);
    }

    if (!movie) {
        return <div style={{ padding: "30px" }}>Загрузка...</div>;
    }

    const poster = movie.poster_path
        ? `https://image.tmdb.org/t/p/w500${movie.poster_path}`
        : "";

    const backdrop = movie.backdrop_path
        ? `https://image.tmdb.org/t/p/original${movie.backdrop_path}`
        : "";

    return (
        <div
            className="movie-page"
            style={{
                backgroundImage: `linear-gradient(
          rgba(0,0,0,.85),
          rgba(0,0,0,.95)
        ), url(${backdrop})`,
            }}
        >
            <div className="movie-container">
                <img
                    className="movie-poster"
                    src={poster}
                    alt={movie.title}
                />

                <div className="movie-details">
                    <h1>{movie.title}</h1>

                    <div className="movie-meta">
                        ⭐ {movie.vote_average?.toFixed(1)}

                        {" • "}

                        {movie.release_date?.slice(0, 4)}

                        {" • "}

                        {movie.runtime} мин
                    </div>

                    <p className="movie-overview">
                        {movie.overview}
                    </p>

                    <div className="movie-actions">
                        <button className="watch-btn">
                            ▶ Смотреть
                        </button>
                    </div>

                    <div className="genres">
                        {movie.genres?.map((genre) => (
                            <span
                                key={genre.id}
                                className="genre"
                            >
                                {genre.name}
                            </span>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}