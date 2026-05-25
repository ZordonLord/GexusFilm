import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'

type Genre = {
  id: number
  name: string
}

type Movie = {
  id: number
  title: string
  overview: string
  backdrop_path: string
  poster_path: string
  vote_average: number
  release_date: string
  runtime: number
  genres: Genre[]
}

export default function MoviePage() {
  const { id } = useParams()

  const [movie, setMovie] = useState<Movie | null>(null)

  useEffect(() => {

    fetch(`http://localhost:8000/api/movie.php?id=${id}`)
      .then(res => res.json())
      .then(data => {
        setMovie(data)
      })

  }, [id])

  if (!movie) {
    return (
      <div className="bg-black min-h-screen text-white p-10">
        Загрузка...
      </div>
    )
  }

  return (
    <div className="bg-black min-h-screen text-white">

      <div
        className="bg-cover bg-center p-10"
        style={{
          backgroundImage: `
            linear-gradient(to top, #000, transparent),
            url(https://image.tmdb.org/t/p/original${movie.backdrop_path})
          `
        }}
      >

        <Link
          to="/"
          className="inline-block mb-10 text-white"
        >
          ← Назад
        </Link>

        <div className="flex flex-col md:flex-row gap-10">

          <img
            src={`https://image.tmdb.org/t/p/w500${movie.poster_path}`}
            className="w-72 rounded-2xl"
          />

          <div className="max-w-2xl">

            <h1 className="text-5xl font-bold mb-4">
              {movie.title}
            </h1>

            <div className="text-zinc-300 mb-4">
              {movie.release_date?.slice(0, 4)}
              {' • '}
              {movie.runtime} мин
            </div>

            <div className="text-yellow-400 text-2xl mb-6">
              ⭐ {movie.vote_average.toFixed(1)}
            </div>

            <div className="flex gap-2 flex-wrap mb-6">
              {movie.genres?.map(genre => (
                <div
                  key={genre.id}
                  className="bg-zinc-800 px-4 py-2 rounded-full"
                >
                  {genre.name}
                </div>
              ))}
            </div>

            <p className="text-lg leading-8 text-zinc-200">
              {movie.overview}
            </p>

          </div>

        </div>

      </div>

    </div>
  )
}