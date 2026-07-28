// Подвал сайта — копирайт и ссылки

export default function Footer() {
  const year = new Date().getFullYear();

  return (
    <footer className="border-t border-white/[0.06] bg-[var(--bg)] mt-auto">
      <div className="max-w-7xl mx-auto px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-4">
        {/* Левая часть — логотип */}
        <div className="flex items-center gap-2.5">
          <div className="w-7 h-7 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
            <span className="text-white font-bold text-xs">G</span>
          </div>
          <span className="text-sm font-semibold text-white/60">
            Gexus<span className="text-blue-400">Film</span>
          </span>
        </div>

        {/* Центр — копирайт */}
        <p className="text-xs text-white/30 text-center">
          &copy; {year} GexusFilm. Все права защищены.
          <br className="sm:hidden" /> Данные предоставлены TMDB.
        </p>

        {/* Правая часть — ссылки */}
        <div className="flex items-center gap-4">
          <a
            href="#"
            className="text-xs text-white/30 hover:text-white/60 transition-colors duration-200"
          >
            Политика конфиденциальности
          </a>
          <a
            href="#"
            className="text-xs text-white/30 hover:text-white/60 transition-colors duration-200"
          >
            Контакты
          </a>
        </div>
      </div>
    </footer>
  );
}