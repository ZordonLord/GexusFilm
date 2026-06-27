import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";

import { getMovie } from "../services/api";

import MovieHero from "../components/MovieHero";

import "../styles/MoviePage.css";

export default function MoviePage() {

    const { id } = useParams();

    const [movie, setMovie] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {

        async function loadMovie() {

            setLoading(true);

            try {

                const data = await getMovie(id);
                setMovie(data);

            } finally {

                setLoading(false);

            }

        }

        loadMovie();

    }, [id]);

    if (loading) {

        return (
            <div className="movie-loading">
                Загрузка...
            </div>
        );

    }

    if (!movie) {

        return (
            <div className="movie-loading">
                Фильм не найден
            </div>
        );

    }

    return (

        <div
            className="movie-page"
            style={{
                backgroundImage:
                    `url(https://image.tmdb.org/t/p/original${movie.backdrop_path})`
            }}
        >

            <MovieHero movie={movie} />

            {/* Здесь будут следующие блоки */}

            {/* <MovieCast movieId={id} /> */}

            {/* <MovieTrailer movieId={id} /> */}

            {/* <MovieRecommendations movieId={id} /> */}

            {/* <MovieComments movieId={id} /> */}

        </div>

    );

}