// Универсальная страница медиа (фильм / сериал)

import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";

import { getMovie, getTvShow } from "../services/api";

import Sidebar from "../components/Sidebar";
import Header from "../components/Header";
import Footer from "../components/Footer";
import MediaHero from "../components/MediaHero";
import SeasonList from "../components/SeasonList";

import "../styles/MediaPage.css";

const API_BY_TYPE = {
  movie: getMovie,
  tv: getTvShow,
};

export default function MediaPage() {
  const { type, id } = useParams();

  const [media, setMedia] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function loadMedia() {
      setLoading(true);

      try {
        const fetchMedia = API_BY_TYPE[type] || getMovie;
        const data = await fetchMedia(id);
        setMedia(data);
      } catch (error) {
        console.error(error);
      } finally {
        setLoading(false);
      }
    }

    loadMedia();
  }, [type, id]);

  if (loading) {
    return (
      <div className="flex min-h-screen bg-[var(--bg)]">
        <Sidebar />
        <main className="flex-1 ml-[var(--sidebar-width)] min-w-0 flex items-center justify-center">
          <div className="media-loading">Загрузка...</div>
        </main>
      </div>
    );
  }

  if (!media) {
    return (
      <div className="flex min-h-screen bg-[var(--bg)]">
        <Sidebar />
        <main className="flex-1 ml-[var(--sidebar-width)] min-w-0 flex items-center justify-center">
          <div className="media-loading">Не найдено</div>
        </main>
      </div>
    );
  }

  return (
    <div className="flex min-h-screen bg-[var(--bg)]">
      <Sidebar />

      <main className="flex-1 ml-[var(--sidebar-width)] min-w-0 flex flex-col">
        <Header />

        <div
          className="media-page flex-1 flex flex-col"
          style={{
            backgroundImage:
              `url(https://image.tmdb.org/t/p/original${media.backdrop_path})`,
          }}
        >
          <MediaHero media={media} />

          {type === "tv" && (
            <SeasonList
              seriesId={media.id}
              seasons={media.seasons || []}
            />
          )}
        </div>

        <Footer />
      </main>
    </div>
  );
}