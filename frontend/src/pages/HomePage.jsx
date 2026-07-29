// Главная страница

import { useEffect, useState } from "react";

import Header from "../components/Header";
import Sidebar from "../components/Sidebar";
import Footer from "../components/Footer";
import Hero from "../components/Hero";
import MediaRow from "../components/MediaRow";

import {
  getTrendingMovies,
  getPopularMovies,
  getNowPlayingMovies,
  getUpcomingMovies,
  getTrendingTv,
  getPopularTv,
  getOnTheAirTv,
  getAiringTodayTv,
} from "../services/api";

export default function HomePage() {
  const [trendingMovies, setTrendingMovies] = useState([]);
  const [popularMovies, setPopularMovies] = useState([]);
  const [nowPlaying, setNowPlaying] = useState([]);
  const [upcoming, setUpcoming] = useState([]);
  const [trendingTv, setTrendingTv] = useState([]);
  const [popularTv, setPopularTv] = useState([]);
  const [onTheAir, setOnTheAir] = useState([]);
  const [airingToday, setAiringToday] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;

    async function loadData() {
      try {
        const [
          trendingMoviesData,
          popularMoviesData,
          nowPlayingData,
          upcomingData,
          trendingTvData,
          popularTvData,
          onTheAirData,
          airingTodayData,
        ] = await Promise.all([
          getTrendingMovies(),
          getPopularMovies(),
          getNowPlayingMovies(),
          getUpcomingMovies(),
          getTrendingTv(),
          getPopularTv(),
          getOnTheAirTv(),
          getAiringTodayTv(),
        ]);

        if (cancelled) {
          return;
        }

        setTrendingMovies(trendingMoviesData.results || []);
        setPopularMovies(popularMoviesData.results || []);
        setNowPlaying(nowPlayingData.results || []);
        setUpcoming(upcomingData.results || []);
        setTrendingTv(trendingTvData.results || []);
        setPopularTv(popularTvData.results || []);
        setOnTheAir(onTheAirData.results || []);
        setAiringToday(airingTodayData.results || []);
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
          {/* Hero-секция с трендовыми фильмами */}
          <Hero items={trendingMovies} mediaType="movie" />

          {/* Контентные ряды */}
          <div className="relative z-10 -mt-16 space-y-6 pb-12">
            <MediaRow
              title="Популярное"
              items={popularMovies}
              mediaType="movie"
              loading={loading}
            />

            <MediaRow
              title="Сейчас в кино"
              items={nowPlaying}
              mediaType="movie"
              loading={loading}
            />

            <MediaRow
              title="Скоро выйдут"
              items={upcoming}
              mediaType="movie"
              loading={loading}
            />

            <MediaRow
              title="Трендовые сериалы"
              items={trendingTv}
              mediaType="tv"
              loading={loading}
            />

            <MediaRow
              title="Популярные сериалы"
              items={popularTv}
              mediaType="tv"
              loading={loading}
            />

            <MediaRow
              title="Сериалы в эфире"
              items={onTheAir}
              mediaType="tv"
              loading={loading}
            />

            <MediaRow
              title="Сериалы сегодня"
              items={airingToday}
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