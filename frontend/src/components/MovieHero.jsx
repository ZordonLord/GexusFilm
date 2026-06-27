import "../styles/MovieHero.css";

export default function MovieHero({ movie }) {

    const poster =
        `https://image.tmdb.org/t/p/w500${movie.poster_path}`;

    const year = movie.release_date
        ? movie.release_date.slice(0, 4)
        : "—";

    const runtime = movie.runtime
        ? `${movie.runtime} мин`
        : "—";

    return (

        <section className="movie-hero">

            <div className="movie-hero__poster">

                <img
                    src={poster}
                    alt={movie.title}
                />

            </div>

            <div className="movie-hero__content">

                <h1 className="movie-hero__title">
                    {movie.title}
                </h1>

                <div className="movie-hero__meta">

                    <div className="movie-badge rating">
                        ⭐ {movie.vote_average.toFixed(1)}
                    </div>

                    <div className="movie-badge">
                        📅 {year}
                    </div>

                    <div className="movie-badge">
                        ⏱ {runtime}
                    </div>

                </div>

                <div className="movie-hero__genres">

                    {movie.genres?.map((genre) => (

                        <span
                            key={genre.id}
                            className="genre-chip"
                        >
                            {genre.name}
                        </span>

                    ))}

                </div>

                <p className="movie-hero__overview">

                    {movie.overview}

                </p>

                <div className="movie-hero__buttons">

                    <button className="btn btn-primary">
                        ▶ Смотреть
                    </button>

                    <button className="btn btn-secondary">
                        ❤ В избранное
                    </button>

                    <button className="btn btn-secondary">
                        ↗ Поделиться
                    </button>

                </div>

            </div>

        </section>

    );

}