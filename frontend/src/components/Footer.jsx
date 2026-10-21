// Подвал сайта — копирайт и ссылки

import "../styles/Footer.css";

export default function Footer() {
  const year = new Date().getFullYear();

  return (
    <footer className="site-footer">
      <div className="site-footer__inner">
        {/* Левая часть — логотип */}
        <div className="site-footer__brand">
          <div className="site-footer__logo">
            <span>G</span>
          </div>
          <span className="site-footer__wordmark">
            Gexus<span>Film</span>
          </span>
        </div>

        {/* Центр — копирайт */}
        <p className="site-footer__copyright">
          &copy; {year} GexusFilm. Все права защищены.
          <br /> Данные предоставлены TMDB.
        </p>

        {/* Правая часть — ссылки */}
        <div className="site-footer__links">
          <a
            href="#"
            className="site-footer__link"
          >
            Политика конфиденциальности
          </a>
          <a
            href="#"
            className="site-footer__link"
          >
            Контакты
          </a>
        </div>
      </div>
    </footer>
  );
}