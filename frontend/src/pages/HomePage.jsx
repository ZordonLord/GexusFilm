// Главная страница

import { useEffect, useState } from "react";

import Header from "../components/Header";
import Sidebar from "../components/Sidebar";
import Footer from "../components/Footer";
import Hero from "../components/Hero";
import MediaRow from "../components/MediaRow";

import {
  getTopRatedMovies,
  getTrendingMoviesWeek,
  getNewMovies,
  getTopRatedTv,
  getTrendingTvWeek,
  getNewTv,
} from "../services/api";

import "../styles/HomePage.css";

export default function HomePage() {
  const [topRatedMovies, setTopRatedMovies] = useState([]);
  const [trendingMoviesWeek, setTrendingMoviesWeek] = useState([]);
  const [newMovies, setNewMovies] = useState([]);
  const [topRatedTv, setTopRatedTv] = useState([]);
  const [trendingTvWeek, setTrendingTvWeek] = useState([]);
  const [newTv, setNewTv] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);
  const [reloadKey, setReloadKey] = useState(0);
  const [heroIndex, setHeroIndex] = useState(0);

  useEffect(() => {
    let cancelled = false;

    async function loadData() {
      setLoading(true);
      setError(false);

      const requests = [
        getTopRatedMovies(),
        getTrendingMoviesWeek(),
        getNewMovies(),
        getTopRatedTv(),
        getTrendingTvWeek(),
        getNewTv(),
      ];

      try {
        const results = await Promise.allSettled(requests);

        if (cancelled) {
          return;
        }

        const getResults = (index) => (
          results[index].status === "fulfilled"
            ? results[index].value?.results || []
            : []
        );

        setTopRatedMovies(getResults(0));
        setTrendingMoviesWeek(getResults(1));
        setNewMovies(getResults(2));
        setTopRatedTv(getResults(3));
        setTrendingTvWeek(getResults(4));
        setNewTv(getResults(5));

        if (results.every((result) => result.status === "rejected")) {
          setError(true);
        }
      } catch (loadError) {
        console.error(loadError);
        setError(true);
      } finally {
        if (!cancelled) setLoading(false);
      }
    }

    loadData();

    return () => {
      cancelled = true;
    };
  }, [reloadKey]);

  const heroItems = [
    ...trendingMoviesWeek.map((item) => ({ ...item, media_type: item.media_type || "movie" })),
    ...trendingTvWeek.map((item) => ({ ...item, media_type: item.media_type || "tv" })),
  ];

  const hasContent = [
    topRatedMovies,
    trendingMoviesWeek,
    newMovies,
    topRatedTv,
    trendingTvWeek,
    newTv,
  ].some((items) => items.length > 0);

  const activeHeroIndex = Math.min(heroIndex, Math.max(heroItems.length - 1, 0));

  const handleRetry = () => setReloadKey((key) => key + 1);

  return (
    <div className="home-page-shell">
      <Header />

      <div className="home-page-shell__body">
        <Sidebar />

        <main className="home-page-shell__main">
          <div className="home-page__top">
            <Hero
              items={heroItems}
              loading={loading}
              error={error}
              onRetry={handleRetry}
              activeIndex={activeHeroIndex}
              onActiveIndexChange={setHeroIndex}
              compact
            />

            <section className="latest-comments" aria-labelledby="latest-comments-title">
            <div className="latest-comments__header">
              <div>
                <span className="latest-comments__eyebrow">Сообщество</span>
                <h2 id="latest-comments-title">Последние комментарии</h2>
              </div>
            </div>
            <div className="latest-comments__empty" role="status">
              <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
                <path strokeLinecap="round" strokeLinejoin="round" d="M7.5 8.25h9m-9 3.75h5.25m-8.25 8.25 2.82-3.38a1.5 1.5 0 0 1 1.15-.54h7.03A3.75 3.75 0 0 0 19.25 12V7.5a3.75 3.75 0 0 0-3.75-3.75h-7A3.75 3.75 0 0 0 4.75 7.5v4.5a3.75 3.75 0 0 0 3.75 3.75v4.5Z" />
              </svg>
              <h3>Комментарии появятся позже</h3>
              <p>Раздел готов для отзывов пользователей после запуска аккаунтов и обсуждений.</p>
            </div>
            </section>
          </div>

          {!(error && !hasContent) && (
            <div className="home-page__rows">
              {error && (
                <p className="home-page__partial-error" role="status">
                  Часть подборок временно недоступна. Уже загруженные разделы остаются доступны.
                </p>
              )}

            <MediaRow
                title="Популярные фильмы"
                items={topRatedMovies}
                mediaType="movie"
                loading={loading}
                compact
                showOverlayControls={false}
                titleHref="/movies"
                viewAllHref="/movies"
                viewAllLabel="Все фильмы"
              />

              <MediaRow
                title="Популярные сериалы"
                items={topRatedTv}
                mediaType="tv"
                loading={loading}
                compact
                showOverlayControls={false}
                titleHref="/tv"
                viewAllHref="/tv"
                viewAllLabel="Все сериалы"
              />

              <MediaRow
                title="Новые фильмы"
                items={newMovies}
                mediaType="movie"
                loading={loading}
                compact
                showOverlayControls={false}
                titleHref="/movies"
                viewAllHref="/movies"
                viewAllLabel="Все фильмы"
              />

              <MediaRow
                title="Новые сериалы"
                items={newTv}
                mediaType="tv"
                loading={loading}
                compact
                showOverlayControls={false}
                titleHref="/tv"
                viewAllHref="/tv"
                viewAllLabel="Все сериалы"
              />

              <MediaRow
                title="Фильмы в тренде за неделю"
                items={trendingMoviesWeek}
                mediaType="movie"
                loading={loading}
                compact
                showOverlayControls={false}
                titleHref="/discover?type=movie"
                viewAllHref="/discover?type=movie"
                viewAllLabel="Открыть каталог"
              />

              <MediaRow
                title="Сериалы в тренде за неделю"
                items={trendingTvWeek}
                mediaType="tv"
                loading={loading}
                compact
                showOverlayControls={false}
                titleHref="/discover?type=tv"
                viewAllHref="/discover?type=tv"
                viewAllLabel="Открыть каталог"
              />

              {!loading && !hasContent && (
                <section className="home-page__state" role="status">
                  <h2>Подборки пока пусты</h2>
                  <p>Откройте каталог, чтобы найти фильм или сериал самостоятельно.</p>
                </section>
              )}
            </div>
          )}

          <Footer />
        </main>
      </div>
    </div>
  );
}