// Главная страница

import { useEffect, useState } from "react";

import Sidebar from "../components/Sidebar";
import MediaRow from "../components/MediaRow";

import "../styles/HomePage.css";

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
      }
    }

    loadData();

    return () => {
      cancelled = true;
    };
  }, []);

  return (
    <div className="app-layout">
      <Sidebar />

      <main className="content">
        <MediaRow
          title="Сейчас смотрят"
          items={trendingMovies}
          mediaType="movie"
        />

        <MediaRow
          title="Популярное"
          items={popularMovies}
          mediaType="movie"
        />

        <MediaRow
          title="Сейчас в кино"
          items={nowPlaying}
          mediaType="movie"
        />

        <MediaRow
          title="Скоро выйдут"
          items={upcoming}
          mediaType="movie"
        />

        <MediaRow
          title="Трендовые сериалы"
          items={trendingTv}
          mediaType="tv"
        />

        <MediaRow
          title="Популярные сериалы"
          items={popularTv}
          mediaType="tv"
        />

        <MediaRow
          title="Сериалы в эфире"
          items={onTheAir}
          mediaType="tv"
        />

        <MediaRow
          title="Сериалы сегодня"
          items={airingToday}
          mediaType="tv"
        />
      </main>
    </div>
  );
}
