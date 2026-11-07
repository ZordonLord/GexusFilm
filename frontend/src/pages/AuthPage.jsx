import { useEffect, useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";

import { getCurrentUser, login, register } from "../services/api";
import Header from "../components/Header";
import { useAuth } from "../contexts/useAuth";

import "../styles/AuthPage.css";

export default function AuthPage() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const [mode, setMode] = useState(searchParams.get("mode") === "register" ? "register" : "login");
  const [status, setStatus] = useState("loading");
  const [error, setError] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [notice, setNotice] = useState("");
  const { setUser } = useAuth();

  useEffect(() => {
    let active = true;
    getCurrentUser()
      .then((currentUser) => {
        if (!active) return;
        if (currentUser) navigate("/", { replace: true });
        else setStatus("ready");
      })
      .catch((requestError) => {
        if (active && requestError.response?.status === 401) setStatus("ready");
        else if (active) setStatus("ready");
      });
    return () => { active = false; };
  }, [navigate]);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setSubmitting(true);
    setError("");
    const form = new FormData(event.currentTarget);
    const payload = {
      email: form.get("email"),
      password: form.get("password"),
    };
    if (mode === "register") payload.display_name = form.get("display_name");

    try {
      if (mode === "register") {
        const result = await register(payload);
        setNotice(result.message);
        setMode("login");
      } else {
        const result = await login(payload);
        setUser(result.user);
        navigate("/", { replace: true });
      }
    } catch (requestError) {
      const code = requestError.response?.data?.error?.code;
      if (code === "VALIDATION_ERROR") setError("Проверьте адрес и пароль. Пароль — не менее 12 символов.");
      else if (code === "RATE_LIMITED") setError("Слишком много попыток. Попробуйте позже.");
      else if (code === "UNAUTHORIZED") setError("Неверный адрес электронной почты или пароль.");
      else setError("Не удалось выполнить запрос. Попробуйте ещё раз.");
    } finally {
      setSubmitting(false);
    }
  };

  const toggleMode = () => {
    setError("");
    setMode((current) => current === "login" ? "register" : "login");
  };

  return (
    <div className="auth-page">
      <Header />
      <main className="auth-page__main" id="main-content" tabIndex="-1">
        <section className="auth-card" aria-labelledby="auth-title">
          <p className="auth-card__eyebrow">GexusFilm</p>
          <h1 id="auth-title">{mode === "login" ? "С возвращением" : "Создать аккаунт"}</h1>
          <p className="auth-card__intro">
            {mode === "login" ? "Войдите, чтобы продолжить." : "Сохраняйте фильмы и сериалы в личных списках."}
          </p>
          {status === "loading" ? (
            <p role="status">Проверяем сессию…</p>
          ) : (
            <form className="auth-form" onSubmit={handleSubmit}>
              {notice && <p className="auth-form__notice" role="status">{notice}</p>}
              {mode === "register" && (
                <label className="auth-form__field">
                  <span>Имя <span className="auth-form__optional">(необязательно)</span></span>
                  <input name="display_name" type="text" autoComplete="name" maxLength={80} />
                </label>
              )}
              <label className="auth-form__field">
                <span>Электронная почта</span>
                <input name="email" type="email" autoComplete="email" maxLength={254} required />
              </label>
              <label className="auth-form__field">
                <span>Пароль</span>
                <input
                  name="password"
                  type="password"
                  autoComplete={mode === "login" ? "current-password" : "new-password"}
                  minLength={mode === "register" ? 12 : undefined}
                  maxLength={1024}
                  required
                />
              </label>
              {mode === "register" && (
                <p className="auth-form__hint">Не менее 12 символов. Используйте уникальный пароль.</p>
              )}
              {error && <p className="auth-form__error" role="alert">{error}</p>}
              <button className="auth-form__submit" type="submit" disabled={submitting}>
                {submitting ? "Подождите…" : mode === "login" ? "Войти" : "Зарегистрироваться"}
              </button>
            </form>
          )}
          <p className="auth-card__switch">
            {mode === "login" ? "Впервые здесь? " : "Уже есть аккаунт? "}
            <button type="button" onClick={toggleMode}>
              {mode === "login" ? "Создать аккаунт" : "Войти"}
            </button>
          </p>
          <Link className="auth-card__back" to="/">Вернуться к каталогу</Link>
        </section>
      </main>
    </div>
  );
}