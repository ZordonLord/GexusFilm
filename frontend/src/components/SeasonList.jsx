// Список сезонов сериала

import { useState } from "react";

import { getTvSeason } from "../services/api";

import "../styles/SeasonList.css";

export default function SeasonList({ seriesId, seasons = [] }) {
  const [openSeason, setOpenSeason] = useState(null);
  const [seasonData, setSeasonData] = useState({});
  const [loading, setLoading] = useState({});

  if (!seasons.length) {
    return null;
  }

  const toggleSeason = async (seasonNumber) => {
    if (openSeason === seasonNumber) {
      setOpenSeason(null);
      return;
    }

    if (!seasonData[seasonNumber]) {
      setLoading((prev) => ({ ...prev, [seasonNumber]: true }));

      try {
        const data = await getTvSeason(seriesId, seasonNumber);
        setSeasonData((prev) => ({ ...prev, [seasonNumber]: data }));
      } catch (error) {
        console.error(error);
      } finally {
        setLoading((prev) => ({ ...prev, [seasonNumber]: false }));
      }
    }

    setOpenSeason(seasonNumber);
  };

  return (
    <section className="season-list">
      <h2 className="season-list__title">Сезоны</h2>

      {seasons.map((season) => (
        <div
          key={season.id}
          className="season-item"
        >
          <button
            className="season-item__header"
            onClick={() => toggleSeason(season.season_number)}
            aria-expanded={openSeason === season.season_number}
          >
            <span className="season-item__name">
              {season.name}
            </span>
            <span className="season-item__meta">
              {season.episode_count} эпизодов · {season.air_date?.slice(0, 4) || "—"}
            </span>
            <span className="season-item__toggle">
              {openSeason === season.season_number ? "−" : "+"}
            </span>
          </button>

          {openSeason === season.season_number && (
            <div className="season-item__episodes">
              {loading[season.season_number] ? (
                <p>Загрузка эпизодов...</p>
              ) : (
                seasonData[season.season_number]?.episodes?.map((episode) => (
                  <div
                    key={episode.id}
                    className="episode-item"
                  >
                    <span className="episode-item__number">
                      {episode.episode_number}
                    </span>
                    <span className="episode-item__name">
                      {episode.name}
                    </span>
                    <span className="episode-item__date">
                      {episode.air_date || "—"}
                    </span>
                  </div>
                ))
              )}
            </div>
          )}
        </div>
      ))}
    </section>
  );
}
