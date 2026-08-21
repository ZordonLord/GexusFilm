// Верхняя панель с логотипом, поиском и кнопкой авторизации

import { Link, useLocation, useNavigate } from "react-router-dom";

import { buildSearchPath } from "../utils/search";
import { useSidebar } from "./SidebarContext";

import "../styles/Header.css";

export default function Header() {
  const location = useLocation();
  const navigate = useNavigate();
  const { isOpen, toggleSidebar } = useSidebar();

  const handleSearch = (event) => {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    navigate(buildSearchPath(formData.get("q")));
  };

  const currentQuery = location.pathname === "/search"
    ? new URLSearchParams(location.search).get("q") || ""
    : "";

  return (
    <header className="site-header">
      {/* Левая часть — логотип */}
      <div className="site-header__brand">
        <button
          type="button"
          className="site-header__menu-toggle"
          onClick={toggleSidebar}
          aria-controls="site-sidebar"
          aria-expanded={isOpen}
          aria-label={isOpen ? "Скрыть боковую панель" : "Показать боковую панель"}
          title={isOpen ? "Скрыть боковую панель" : "Показать боковую панель"}
        >
          <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <Link to="/" className="site-header__logo">
          <div className="site-header__logo-mark">
            <span>G</span>
          </div>
          <span className="site-header__logo-text">
            Gexus<span className="text-blue-400">Film</span>
          </span>
        </Link>
      </div>

      {/* Поиск — по центру / растягивается */}
      <form
        onSubmit={handleSearch}
        className="site-header__search"
        role="search"
        method="get"
        action="/search"
      >
        <div className="site-header__search-control">
          {/* Иконка лупы */}
          <svg
            className="site-header__search-icon"
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
            id="site-search"
            name="q"
            type="search"
            defaultValue={currentQuery}
            placeholder="Поиск фильмов и сериалов..."
            className="site-header__search-input"
            autoComplete="off"
            maxLength={200}
            enterKeyHint="search"
            aria-label="Поиск фильмов и сериалов"
          />
        </div>
      </form>

      {/* Правая часть — кнопка авторизации */}
      <div className="site-header__actions">
        <button
          type="button"
          className="site-header__login"
        >
          <svg
            className="site-header__login-icon"
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
          <span className="site-header__login-text">Войти</span>
        </button>
      </div>
    </header>
  );
}