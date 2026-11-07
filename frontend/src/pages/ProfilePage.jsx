import { useState } from "react";
import { Link, Navigate, useNavigate } from "react-router-dom";

import { logout } from "../services/api";
import { useAuth } from "../contexts/useAuth";
import Header from "../components/Header";

import "../styles/AuthPage.css";

export default function ProfilePage() {
  const { user, loading, clearUser } = useAuth();
  const navigate = useNavigate();
  const [error, setError] = useState("");
  const [submitting, setSubmitting] = useState(false);

  const handleLogout = async () => {
    setSubmitting(true);
    setError("");
    try {
      await logout();
      clearUser();
      navigate("/", { replace: true });
    } catch {
      setError("Не удалось завершить сеанс. Попробуйте ещё раз.");
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return <main className="auth-page__main" id="main-content"><p role="status">Проверяем сессию…</p></main>;
  }
  if (!user) return <Navigate to="/auth" replace />;

  return (
    <div className="auth-page">
      <Header />
      <main className="auth-page__main" id="main-content" tabIndex="-1">
        <section className="auth-card" aria-labelledby="profile-title">
          <p className="auth-card__eyebrow">Аккаунт GexusFilm</p>
          <h1 id="profile-title">Профиль</h1>
          <p className="auth-card__intro">{user.display_name || user.email}</p>
          <p>{user.email}</p>
          {error && <p className="auth-form__error" role="alert">{error}</p>}
          <button className="auth-form__submit" type="button" onClick={handleLogout} disabled={submitting}>
            {submitting ? "Завершаем сеанс…" : "Выйти"}
          </button>
          <Link className="auth-card__back" to="/">Вернуться к каталогу</Link>
        </section>
      </main>
    </div>
  );
}