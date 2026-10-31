// Подвал сайта — копирайт и ссылки

import "../styles/Footer.css";

export default function Footer() {
  const year = new Date().getFullYear();

  return (
    <footer className="site-footer">
      <div className="site-footer__inner">
        {/* Левая часть — логотип */}
        <div className="site-footer__brand">
          <img className="site-footer__logo" src="/logo.png" alt="" />
          <span className="site-footer__wordmark">
            Gexus<span>Film</span>
          </span>
        </div>

        {/* Центр — копирайт */}
        <p className="site-footer__copyright">
          &copy; {year} GexusFilm. Все права защищены.
          <br />
          <a className="site-footer__tmdb" href="https://www.themoviedb.org" target="_blank" rel="noreferrer" aria-label="TMDB — The Movie Database">
            <img src="https://www.themoviedb.org/assets/2/v4/logos/primary-green.svg" alt="TMDB" width="92" height="24" loading="lazy" onError={(event) => { event.currentTarget.hidden = true; }} />
            <span>Данные и изображения TMDB</span>
          </a>
          <br />
          <span className="site-footer__attribution-label">Credits</span>{" "}
          This product uses the TMDB API but is not endorsed or certified by TMDB.
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