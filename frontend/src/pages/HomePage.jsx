// Главная страница

import { useEffect, useState } from "react";

import Header from "../components/Header";
import Sidebar from "../components/Sidebar";
import Footer from "../components/Footer";
import MediaRow from "../components/MediaRow";

import {
  getTopRatedMovies,
  getTrendingMoviesWeek,
  getNewMovies,
  getTopRatedTv,
  getTrendingTvWeek,
  getNewTv,
} from "../services/api";

export default function HomePage() {
  const [topRatedMovies, setTopRatedMovies] = useState([]);
  const [trendingMoviesWeek, setTrendingMoviesWeek] = useState([]);
  const [newMovies, setNewMovies] = useState([]);
  const [topRatedTv, setTopRatedTv] = useState([]);
  const [trendingTvWeek, setTrendingTvWeek] = useState([]);
  const [newTv, setNewTv] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;

    async function loadData() {
      try {
        const [
          topRatedMoviesData,
          trendingMoviesWeekData,
          newMoviesData,
          topRatedTvData,
          trendingTvWeekData,
          newTvData,
        ] = await Promise.all([
          getTopRatedMovies(),
          getTrendingMoviesWeek(),
          getNewMovies(),
          getTopRatedTv(),
          getTrendingTvWeek(),
          getNewTv(),
        ]);

        if (cancelled) {
          return;
        }

        setTopRatedMovies(topRatedMoviesData.results || []);
        setTrendingMoviesWeek(trendingMoviesWeekData.results || []);
        setNewMovies(newMoviesData.results || []);
        setTopRatedTv(topRatedTvData.results || []);
        setTrendingTvWeek(trendingTvWeekData.results || []);
        setNewTv(newTvData.results || []);
      } catch (error) {
        console.error(error);
      } finally {
        if (!cancelled) setLoading(false);
      }
    }

    loadData();

    return () => {
      cancelled = true;
    };
  }, []);

  return (
    <div className="flex flex-col min-h-screen bg-[var(--bg)]">
      {/* Header на всю ширину сверху */}
      <Header />

      {/* Под header — sidebar и контент */}
      <div className="flex flex-1">
        <Sidebar />

        <main className="flex-1 min-w-0 flex flex-col">
          {/* Контентные ряды */}
          <div className="relative z-10 space-y-6 pb-12">
            <MediaRow
              title="Лучшие фильмы"
              items={topRatedMovies}
              mediaType="movie"
              loading={loading}
            />

            <MediaRow
              title="Фильмы в тренде за неделю"
              items={trendingMoviesWeek}
              mediaType="movie"
              loading={loading}
            />

            <MediaRow
              title="Новые фильмы"
              items={newMovies}
              mediaType="movie"
              loading={loading}
            />

            <MediaRow
              title="Лучшие сериалы"
              items={topRatedTv}
              mediaType="tv"
              loading={loading}
            />

            <MediaRow
              title="Сериалы в тренде за неделю"
              items={trendingTvWeek}
              mediaType="tv"
              loading={loading}
            />

            <MediaRow
              title="Новые сериалы"
              items={newTv}
              mediaType="tv"
              loading={loading}
            />
          </div>

          {/* Подвал */}
          <Footer />
        </main>
      </div>
    </div>
  );
}