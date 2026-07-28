// Верхняя панель с поиском и кнопкой авторизации

import { useState } from "react";
import { useNavigate } from "react-router-dom";

export default function Header() {
  const [query, setQuery] = useState("");
  const navigate = useNavigate();

  const handleSearch = (e) => {
    e.preventDefault();
    const trimmed = query.trim();
    if (trimmed) {
      navigate(`/search?q=${encodeURIComponent(trimmed)}`);
    }
  };

  return (
    <header className="sticky top-0 z-20 w-full h-16 flex items-center justify-between gap-4 px-6 border-b border-white/[0.06] bg-[var(--bg)]/80 backdrop-blur-xl">
      {/* Левая часть — заголовок (опционально) */}
      <div className="flex items-center gap-3 min-w-0">
        <h2 className="text-lg font-semibold text-white/80 hidden sm:block truncate">
          GexusFilm
        </h2>
      </div>

      {/* Поиск — по центру / растягивается */}
      <form
        onSubmit={handleSearch}
        className="flex-1 max-w-md mx-auto w-full"
        role="search"
      >
        <div className="relative group">
          {/* Иконка лупы */}
          <svg
            className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-white/30 group-focus-within:text-[var(--primary)] transition-colors duration-200 pointer-events-none"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            strokeWidth={2}
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>

          <input
            type="search"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Поиск фильмов и сериалов..."
            className="w-full h-10 pl-10 pr-4 rounded-xl bg-white/[0.06] border border-white/[0.08] text-white text-sm placeholder:text-white/30 outline-none transition-all duration-200 focus:bg-white/[0.08] focus:border-[var(--primary)]/40 focus:shadow-[0_0_0_3px_rgba(59,130,246,0.12)]"
            aria-label="Поиск фильмов и сериалов"
          />
        </div>
      </form>

      {/* Правая часть — кнопка авторизации */}
      <div className="flex items-center gap-3 shrink-0">
        <button
          type="button"
          className="inline-flex items-center gap-2 h-10 px-5 rounded-xl bg-[var(--primary)] text-white text-sm font-semibold transition-all duration-200 hover:bg-blue-500 active:scale-95 shadow-lg shadow-blue-500/20"
        >
          <svg
            className="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            strokeWidth={2}
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
            />
          </svg>
          <span className="hidden sm:inline">Войти</span>
        </button>
      </div>
    </header>
  );
}