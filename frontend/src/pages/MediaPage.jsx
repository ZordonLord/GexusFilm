// Универсальная страница медиа (фильм / сериал)

import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";

import { getMovie, getMovieCollection, getTvShow } from "../services/api";
import { getTmdbImageUrl } from "../utils/tmdbImage";

import Header from "../components/Header";
import Sidebar from "../components/Sidebar";
import Footer from "../components/Footer";
import MediaHero from "../components/MediaHero";
import MovieDetailsSections, { MediaDetailsSections } from "../components/MovieDetailsSections";
import SeasonList from "../components/SeasonList";

import "../styles/MediaPage.css";

const API_BY_TYPE = {
  movie: getMovie,
  tv: getTvShow,
};

export default function MediaPage() {
  const { type, id } = useParams();

  const [media, setMedia] = useState(null);
  const [collection, setCollection] = useState(null);
  const [loading, setLoading] = useState(true);
  const backdropUrl = getTmdbImageUrl(media?.backdrop_path, "original");

  useEffect(() => {
    let active = true;

    async function loadMedia() {
      setLoading(true);
      setCollection(null);

      try {
        const fetchMedia = API_BY_TYPE[type] || getMovie;
        const data = await fetchMedia(id);
        if (!active) return;
        setMedia(data);

        if (type === "movie" && data.belongs_to_collection?.id) {
          getMovieCollection(data.belongs_to_collection.id)
            .then((collectionData) => {
              if (active) setCollection(collectionData);
            })
            .catch((error) => {
              if (active) console.error("Не удалось загрузить части франшизы:", error);
            });
        } else {
          setCollection(null);
        }
      } catch (error) {
        if (active) console.error(error);
      } finally {
        if (active) setLoading(false);
      }
    }

    loadMedia();

    return () => {
      active = false;
    };
  }, [type, id]);

  if (loading) {
    return (
      <div className="media-page-shell">
        <Header />
        <div className="media-page-shell__body">
          <Sidebar />
          <main className="media-page-shell__main media-page-shell__main--state" id="main-content" tabIndex="-1">
            <div className="media-loading">Загрузка...</div>
          </main>
        </div>
      </div>
    );
  }

  if (!media) {
    return (
      <div className="media-page-shell">
        <Header />
        <div className="media-page-shell__body">
          <Sidebar />
          <main className="media-page-shell__main media-page-shell__main--state" id="main-content" tabIndex="-1">
            <div className="media-loading">Не найдено</div>
          </main>
        </div>
      </div>
    );
  }

  return (
    <div className="media-page-shell">
      <Header />

      <div className="media-page-shell__body">
        <Sidebar />

        <main className="media-page-shell__main" id="main-content" tabIndex="-1">
          <div
            className="media-page"
            style={backdropUrl ? {
              "--media-backdrop-image": `url("${backdropUrl}")`,
            } : undefined}
          >
            <MediaHero media={media} type={type} />

            {type === "movie" && <MovieDetailsSections media={media} collection={collection} />}

            {type === "tv" && (
              <div className="tv-seasons">
                <SeasonList
                  seriesId={media.id}
                  seasons={media.seasons || []}
                />
                <MediaDetailsSections media={media} mediaType="tv" />
              </div>
            )}
          </div>

          <Footer />
        </main>
      </div>
    </div>
  );
}