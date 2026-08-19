// Hero-секция главной страницы — большой баннер с трендовым контентом

import { useState, useEffect, useCallback } from "react";
import { Link } from "react-router-dom";
import {
  getMediaId,
  getMediaTitle,
  getMediaYear,
  getMediaRoute,
  formatRating,
} from "../utils/media";

export default function Hero({ items = [], mediaType = "movie" }) {
  const [current, setCurrent] = useState(0);
  const [loaded, setLoaded] = useState(false);

  const item = items[current];

  const next = useCallback(() => {
    setCurrent((prev) => (prev + 1) % Math.min(items.length, 5));
    setLoaded(false);
  }, [items.length]);

  useEffect(() => {
    if (items.length === 0) return;
    const timer = setInterval(next, 6000);
    return () => clearInterval(timer);
  }, [items.length, next]);

  if (!item || items.length === 0) {
    return null;
  }

  const id = getMediaId(item);

  if (!id) {
    return null;
  }

  const title = getMediaTitle(item);
  const year = getMediaYear(item);
  const type = item.media_type || mediaType;
  const backdrop = item.backdrop_path
    ? `https://image.tmdb.org/t/p/original${item.backdrop_path}`
    : null;
  const poster = item.poster_path
    ? `https://image.tmdb.org/t/p/w500${item.poster_path}`
    : null;
  const rating = formatRating(item.vote_average);
  const overview = item.overview || "";

  return (
    <section className="relative w-full h-[85vh] min-h-[500px] max-h-[900px] overflow-hidden">
      {/* Фоновое изображение */}
      <div className="absolute inset-0 transition-opacity duration-700 ease-in-out">
        {!loaded && (
          <div className="absolute inset-0 bg-[#0f172a] animate-pulse" />
        )}
        {backdrop && (
          <img
            src={backdrop}
            alt=""
            className={`w-full h-full object-cover transition-opacity duration-500 ${loaded ? "opacity-100" : "opacity-0"}`}
            onLoad={() => setLoaded(true)}
            onError={() => setLoaded(true)}
            fetchPriority="high"
          />
        )}
      </div>

      {/* Градиентные затемнения */}
      <div className="absolute inset-0 bg-gradient-to-t from-[#020817] via-[#020817]/60 to-transparent" />
      <div className="absolute inset-0 bg-gradient-to-r from-[#020817]/90 via-[#020817]/40 to-transparent" />

      {/* Контент */}
      <div className="relative z-10 h-full flex items-end pb-20 px-6 md:px-12 lg:px-16">
        <div className="flex items-end gap-8 w-full max-w-7xl mx-auto">
          {/* Постер (десктоп) */}
          <div className="hidden md:block w-[200px] flex-shrink-0">
            <div className="aspect-[2/3] rounded-xl overflow-hidden shadow-2xl ring-1 ring-white/10">
              {poster && (
                <img
                  src={poster}
                  alt={title}
                  className="w-full h-full object-cover"
                  loading="eager"
                  onError={(event) => { event.currentTarget.hidden = true; }}
                />
              )}
            </div>
          </div>

          {/* Текстовая информация */}
          <div className="flex-1 min-w-0">
            {/* Рейтинг и год */}
            <div className="flex items-center gap-4 mb-3">
              <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-yellow-400/20 text-yellow-400 text-sm font-semibold">
                <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                {rating}
              </span>
              <span className="text-white/60 text-sm">{year}</span>
              <span className="px-2 py-0.5 rounded text-xs font-medium bg-white/10 text-white/70 uppercase tracking-wider">
                {type === "movie" ? "Фильм" : "Сериал"}
              </span>
            </div>

            {/* Заголовок */}
            <h1 className="text-3xl md:text-5xl lg:text-6xl font-bold text-white mb-4 leading-tight">
              {title}
            </h1>

            {/* Описание */}
            {overview && (
              <p className="text-white/60 text-sm md:text-base leading-relaxed max-w-2xl line-clamp-3 mb-6">
                {overview}
              </p>
            )}

            {/* Кнопки действий */}
            <div className="flex items-center gap-3">
              <Link
                to={getMediaRoute(type, id)}
                className="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-primary text-white font-semibold text-sm hover:bg-blue-500 transition-colors shadow-lg shadow-blue-500/25"
              >
                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                </svg>
                Смотреть
              </Link>

              <button className="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-white/10 text-white font-semibold text-sm hover:bg-white/20 transition-colors backdrop-blur-sm">
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth={2}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                В список
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Индикаторы слайдов */}
      <div className="absolute bottom-6 right-6 md:right-12 lg:right-16 z-20 flex gap-2">
        {items.slice(0, 5).map((_, i) => (
          <button
            key={i}
            onClick={() => { setCurrent(i); setLoaded(false); }}
            className={`h-1.5 rounded-full transition-all duration-300 ${
              i === current
                ? "w-8 bg-primary"
                : "w-4 bg-white/30 hover:bg-white/50"
            }`}
            aria-label={`Слайд ${i + 1}`}
          />
        ))}
      </div>
    </section>
  );
}