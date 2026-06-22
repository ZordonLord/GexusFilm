// Главная страница

import { useEffect, useState } from "react";

import Sidebar from "../components/Sidebar";
import MovieRow from "../components/MovieRow";

import {
  getTrendingMovies,
  getPopularMovies,
  getNowPlayingMovies,
  getUpcomingMovies,
} from "../services/api";

export default function HomePage() {
  const [trending, setTrending] = useState([]);
  const [popular, setPopular] = useState([]);
  const [nowPlaying, setNowPlaying] = useState([]);
  const [upcoming, setUpcoming] = useState([]);

  useEffect(() => {
    loadData();
  }, []);

  async function loadData() {
    try {
      const [
        trendingData,
        popularData,
        nowPlayingData,
        upcomingData,
      ] = await Promise.all([
        getTrendingMovies(),
        getPopularMovies(),
        getNowPlayingMovies(),
        getUpcomingMovies(),
      ]);

      setTrending(trendingData.results || []);
      setPopular(popularData.results || []);
      setNowPlaying(nowPlayingData.results || []);
      setUpcoming(upcomingData.results || []);
    } catch (error) {
      console.error(error);
    }
  }

  return (
    <div className="app-layout">
      <Sidebar />

      <main className="content">
        <MovieRow
          title="🔥 Сейчас смотрят"
          movies={trending}
        />

        <MovieRow
          title="🎬 Популярное"
          movies={popular}
        />

        <MovieRow
          title="🍿 Сейчас в кино"
          movies={nowPlaying}
        />

        <MovieRow
          title="🚀 Скоро выйдут"
          movies={upcoming}
        />
      </main>
    </div>
  );
}