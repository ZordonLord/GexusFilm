import { useCallback, useEffect, useState } from "react";
import { Link, Navigate } from "react-router-dom";
import { clearVisitHistory, getFavorites, getMediaByType, getVisitHistory } from "../services/api";
import { useAuth } from "../contexts/useAuth";
import Header from "../components/Header";
import Sidebar from "../components/Sidebar";
import Footer from "../components/Footer";
import MediaCard from "../components/MediaCard";
import { useSidebar } from "../components/SidebarContext";
import { getMediaIdentityKey } from "../utils/media";
import "../styles/UserMediaPage.css";

export default function UserMediaPage({ kind }) {
  const { user, loading: authLoading } = useAuth();
  const { isMobile } = useSidebar();
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [clearing, setClearing] = useState(false);
  const isFavorites = kind === "favorites";

  const loadItems = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const records = isFavorites ? await getFavorites() : await getVisitHistory();
      const resolved = await Promise.all(records.map(async (item) => {
        try {
          const media = await getMediaByType(item.media_type, item.source_id);
          return { ...media, media_type: item.media_type, last_viewed_at: item.last_viewed_at };
        } catch {
          return null;
        }
      }));
      setItems(resolved.filter(Boolean));
    } catch {
      setError("Не удалось загрузить список. Попробуйте ещё раз.");
    } finally {
      setLoading(false);
    }
  }, [isFavorites]);

  useEffect(() => {
    if (!user) return undefined;
    const timer = window.setTimeout(() => loadItems(), 0);
    return () => window.clearTimeout(timer);
  }, [loadItems, user]);

  const handleClear = async () => {
    setClearing(true);
    try {
      await clearVisitHistory();
      setItems([]);
    } catch {
      setError("Не удалось очистить историю. Попробуйте ещё раз.");
    } finally {
      setClearing(false);
    }
  };

  if (!authLoading && !user) return <Navigate to="/auth" replace />;

  return (
    <div className="user-media-page">
      <Header />
      <div className="user-media-page__body">
        <Sidebar />
        <main className={`user-media-page__main${isMobile ? " user-media-page__main--mobile" : ""}`} id="main-content" tabIndex="-1">
          <header className="user-media-page__header">
            <div>
              <p className="user-media-page__eyebrow">Моя коллекция</p>
              <h1>{isFavorites ? "Избранное" : "История просмотров"}</h1>
              <p>{isFavorites ? "Сохранённые фильмы и сериалы." : "Недавно открытые страницы фильмов и сериалов."}</p>
            </div>
            {!isFavorites && items.length > 0 && (
              <button type="button" className="user-media-page__clear" onClick={handleClear} disabled={clearing}>
                {clearing ? "Очищаем…" : "Очистить историю"}
              </button>
            )}
          </header>
          {authLoading || (user && loading) ? (
            <p className="user-media-page__state" role="status">Загружаем…</p>
          ) : error ? (
            <section className="user-media-page__state" role="alert">
              <p>{error}</p>
              <button type="button" onClick={loadItems}>Повторить</button>
            </section>
          ) : items.length === 0 ? (
            <section className="user-media-page__empty">
              <h2>{isFavorites ? "Пока ничего не сохранено" : "История пока пуста"}</h2>
              <p>{isFavorites ? "Добавляйте интересное в избранное со страницы фильма или сериала." : "Открытые страницы медиа появятся здесь."}</p>
              <Link to="/discover">Перейти в каталог</Link>
            </section>
          ) : (
            <div className="user-media-page__grid">
              {items.map((item) => (
                <article className="user-media-page__item" key={getMediaIdentityKey(item.media_type, item.id)}>
                  {!isFavorites && item.last_viewed_at && (
                    <time dateTime={item.last_viewed_at}>
                      {new Intl.DateTimeFormat("ru-RU", { dateStyle: "medium", timeStyle: "short" }).format(new Date(item.last_viewed_at))}
                    </time>
                  )}
                  <MediaCard item={item} mediaType={item.media_type} />
                </article>
              ))}
            </div>
          )}
          <Footer />
        </main>
      </div>
    </div>
  );
}