import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'

type Movie = {
  id: number
  title: string
  poster_path: string
  vote_average: number
}

export default function HomePage() {
  const [movies, setMovies] = useState<Movie[]>([])
  const [query, setQuery] = useState('')
  const [debouncedQuery, setDebouncedQuery] = useState('')

  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedQuery(query)
    }, 500)

    return () => clearTimeout(timer)
  }, [query])

  useEffect(() => {

    const url = debouncedQuery
      ? `http://localhost:8000/api/search.php?q=${debouncedQuery}`
      : 'http://localhost:8000/api/movies.php'

    fetch(url)
      .then(res => res.json())
      .then(data => {
        setMovies(data.results || [])
      })

  }, [debouncedQuery])

  return (
    <div className="bg-black min-h-screen text-white p-8">

      <h1 className="text-4xl font-bold mb-8">
        🎬 Movie App
      </h1>

      <input
        type="text"
        placeholder="Поиск фильмов..."
        value={query}
        onChange={(e) => setQuery(e.target.value)}
        className="
          w-full
          max-w-xl
          mb-8
          p-4
          rounded-xl
          bg-zinc-900
          border
          border-zinc-700
          outline-none
        "
      />

      <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">

        {movies.map(movie => (

          <Link
            to={`/movie/${movie.id}`}
            key={movie.id}
            className="
              bg-zinc-900
              rounded-xl
              overflow-hidden
              hover:scale-105
              transition
            "
          >

            <img
              src={`https://image.tmdb.org/t/p/w500${movie.poster_path}`}
              alt={movie.title}
              className="w-full"
            />

            <div className="p-3">

              <h2 className="font-bold text-sm mb-2">
                {movie.title}
              </h2>

              <div className="text-yellow-400">
                ⭐ {movie.vote_average.toFixed(1)}
              </div>

            </div>

          </Link>

        ))}

      </div>

    </div>
  )
}