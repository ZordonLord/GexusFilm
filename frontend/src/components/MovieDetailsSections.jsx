import { useRef, useState } from "react";
import { getMediaId, getMediaTitle } from "../utils/media";
import { getTmdbImageUrl } from "../utils/tmdbImage";
import MediaCard from "./MediaCard";

import "../styles/MovieDetailsSections.css";

const PROVIDER_TYPE_LABELS = {
  flatrate: "Подписка",
  free: "Бесплатно",
  ads: "С рекламой",
  rent: "Аренда",
  buy: "Покупка",
};

function Section({ title, children, className = "" }) {
  return (
    <section className={`movie-details__section ${className}`.trim()} aria-label={title}>
      <h2 className="movie-details__title">{title}</h2>
      {children}
    </section>
  );
}

function getTrailer(videos) {
  const results = Array.isArray(videos?.results) ? videos.results : [];
  const candidates = results.filter((video) =>
    video.site === "YouTube"
    && video.type === "Trailer"
    && typeof video.key === "string"
    && /^[A-Za-z0-9_-]{6,20}$/.test(video.key),
  );

  return candidates.find((video) => video.official && video.iso_639_1 === "ru")
    || candidates.find((video) => video.official)
    || candidates.find((video) => video.iso_639_1 === "ru")
    || candidates[0]
    || null;
}

function PersonPortrait({ person, role }) {
  const photo = getTmdbImageUrl(person.profile_path, "w185");

  return (
    <li className="movie-details__person">
      {photo ? (
        <img src={photo} alt="" width="120" height="180" loading="lazy" />
      ) : (
        <div className="movie-details__person-placeholder" aria-hidden="true">
          {getMediaTitle(person).slice(0, 1)}
        </div>
      )}
      <div>
        <strong>{getMediaTitle(person)}</strong>
        {role && <span>{role}</span>}
      </div>
    </li>
  );
}

function CastSection({ credits }) {
  const cast = Array.isArray(credits?.cast) ? credits.cast.slice(0, 12) : [];
  if (cast.length === 0) return null;

  return (
    <Section title="Актёры" className="movie-details__section--cast">
      <ul className="movie-details__people">
        {cast.map((person) => (
          <PersonPortrait key={person.credit_id || person.id} person={person} role={person.character} />
        ))}
      </ul>
    </Section>
  );
}

function CrewSection({ credits }) {
  const crew = Array.isArray(credits?.crew) ? credits.crew : [];
  const jobLabels = {
    Director: "Режиссёр",
    Writer: "Автор сценария",
    Screenplay: "Сценарист",
    Producer: "Продюсер",
    "Original Music Composer": "Композитор",
  };
  const jobOrder = Object.keys(jobLabels);
  const seen = new Set();
  const selected = crew
    .filter((person) => jobOrder.includes(person.job) && person.name)
    .filter((person) => {
      const key = `${person.id}:${person.job}`;
      if (seen.has(key)) return false;
      seen.add(key);
      return true;
    })
    .sort((a, b) => jobOrder.indexOf(a.job) - jobOrder.indexOf(b.job))
    .slice(0, 12);

  if (selected.length === 0) return null;

  return (
    <Section title="Съёмочная группа">
      <ul className="movie-details__crew">
        {selected.map((person) => (
          <li key={person.credit_id || `${person.id}-${person.job}`}>
            <strong>{person.name}</strong>
            <span>{jobLabels[person.job]}</span>
          </li>
        ))}
      </ul>
    </Section>
  );
}

function TrailerSection({ videos }) {
  const trailer = getTrailer(videos);
  if (!trailer) return null;

  return (
    <Section title="Трейлер">
      <div className="movie-details__video">
        <iframe
          src={`https://www.youtube-nocookie.com/embed/${trailer.key}?rel=0`}
          title={trailer.name || "Трейлер фильма"}
          loading="lazy"
          referrerPolicy="strict-origin-when-cross-origin"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowFullScreen
        />
      </div>
    </Section>
  );
}

function GallerySection({ images }) {
  const backdrops = (Array.isArray(images?.backdrops) ? images.backdrops : [])
    .map((image) => ({
      key: image.file_path,
      src: getTmdbImageUrl(image.file_path, "w780"),
      fullSize: getTmdbImageUrl(image.file_path, "original"),
      width: image.width || 780,
      height: image.height || 440,
    }))
    .filter((image) => image.key && image.src && image.fullSize)
    .slice(0, 8);
  const dialogRef = useRef(null);
  const [activeIndex, setActiveIndex] = useState(0);
  if (backdrops.length === 0) return null;

  const activeImage = backdrops[activeIndex];

  function showImage(index) {
    setActiveIndex(index);
    dialogRef.current?.showModal();
  }

  function moveImage(direction) {
    setActiveIndex((currentIndex) => (currentIndex + direction + backdrops.length) % backdrops.length);
  }

  function handleDialogKeyDown(event) {
    if (event.key !== "ArrowLeft" && event.key !== "ArrowRight") return;

    event.preventDefault();
    moveImage(event.key === "ArrowRight" ? 1 : -1);
  }

  return (
    <Section title="Кадры из фильма">
      <ul className="movie-details__gallery">
        {backdrops.map((image, index) => (
          <li key={image.key}>
            <button
              className="movie-details__gallery-button"
              type="button"
              aria-label={`Открыть кадр ${index + 1} из ${backdrops.length}`}
              onClick={() => showImage(index)}
            >
              <img src={image.src} alt="Кадр из фильма" width={image.width} height={image.height} loading="lazy" />
            </button>
          </li>
        ))}
      </ul>
      <dialog
        className="movie-details__lightbox"
        ref={dialogRef}
        aria-labelledby="movie-details-lightbox-title"
        onKeyDown={handleDialogKeyDown}
        onClick={(event) => {
          if (event.target === dialogRef.current) dialogRef.current.close();
        }}
      >
        <button
          className="movie-details__lightbox-close"
          type="button"
          aria-label="Закрыть галерею"
          autoFocus
          onClick={() => dialogRef.current?.close()}
        >
          <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="m6 6 12 12M18 6 6 18" />
          </svg>
        </button>
        <button
          className="movie-details__lightbox-nav movie-details__lightbox-nav--previous"
          type="button"
          aria-label="Предыдущий кадр"
          onClick={() => moveImage(-1)}
        >
          <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="m15 18-6-6 6-6" />
          </svg>
        </button>
        <figure className="movie-details__lightbox-figure">
          <img src={activeImage.fullSize} alt="Кадр из фильма" width={activeImage.width} height={activeImage.height} />
          <figcaption id="movie-details-lightbox-title">
            Кадр {activeIndex + 1} из {backdrops.length}
          </figcaption>
        </figure>
        <button
          className="movie-details__lightbox-nav movie-details__lightbox-nav--next"
          type="button"
          aria-label="Следующий кадр"
          onClick={() => moveImage(1)}
        >
          <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="m9 18 6-6-6-6" />
          </svg>
        </button>
      </dialog>
    </Section>
  );
}

function getProviderGroups(region) {
  return Object.entries(PROVIDER_TYPE_LABELS)
    .map(([key, label]) => ({
      key,
      label,
      providers: Array.isArray(region?.[key]) ? region[key] : [],
    }))
    .filter((group) => group.providers.length > 0);
}

function ProviderList({ groups, regionName, link }) {
  return (
    <div className="movie-details__watch-region">
      <div className="movie-details__watch-region-heading">
        <h3>{regionName}</h3>
        {link && (
          <a href={link} target="_blank" rel="noreferrer">
            Все варианты <span aria-hidden="true">↗</span>
          </a>
        )}
      </div>
      {groups.length > 0 ? (
        <div className="movie-details__provider-groups">
          {groups.map((group) => (
            <div className="movie-details__provider-group" key={group.key}>
              <h4>{group.label}</h4>
              <ul>
                {group.providers.map((provider) => {
                  const logo = getTmdbImageUrl(provider.logo_path, "w92");
                  return (
                    <li key={`${group.key}-${provider.provider_id}`}>
                      {logo && <img src={logo} alt="" width="36" height="36" loading="lazy" />}
                      <span>{provider.provider_name}</span>
                    </li>
                  );
                })}
              </ul>
            </div>
          ))}
        </div>
      ) : (
        <p className="movie-details__muted">Данные о доступности пока не указаны в TMDB.</p>
      )}
      {groups.length > 0 && (
        <p className="movie-details__attribution">Данные о доступности предоставлены JustWatch.</p>
      )}
    </div>
  );
}

function WatchSection({ media }) {
  const [activeTab, setActiveTab] = useState("official");
  const watchProviders = media["watch/providers"];
  const russianRegion = watchProviders?.results?.RU;
  const russianGroups = getProviderGroups(russianRegion);
  const russianLink = typeof russianRegion?.link === "string"
    && /^https:\/\/www\.themoviedb\.org\//.test(russianRegion.link)
    ? russianRegion.link
    : null;
  const productionCountries = Array.isArray(media.production_countries)
    ? media.production_countries
    : [];
  const originalRegion = productionCountries
    .map((country) => ({ ...country, providers: watchProviders?.results?.[country.iso_3166_1] }))
    .find((country) => country.providers);
  const originalGroups = getProviderGroups(originalRegion?.providers);
  const originalLink = typeof originalRegion?.providers?.link === "string"
    && /^https:\/\/www\.themoviedb\.org\//.test(originalRegion.providers.link)
    ? originalRegion.providers.link
    : null;

  function handleTabKeyDown(event) {
    const tabButtons = Array.from(event.currentTarget.querySelectorAll('[role="tab"]'));
    const currentIndex = tabButtons.indexOf(event.target);
    let nextIndex = currentIndex;

    if (event.key === "ArrowRight") nextIndex = (currentIndex + 1) % tabButtons.length;
    if (event.key === "ArrowLeft") nextIndex = (currentIndex - 1 + tabButtons.length) % tabButtons.length;
    if (event.key === "Home") nextIndex = 0;
    if (event.key === "End") nextIndex = tabButtons.length - 1;
    if (nextIndex === currentIndex) return;

    event.preventDefault();
    const nextTab = tabButtons[nextIndex].id.endsWith("search") ? "search" : "official";
    setActiveTab(nextTab);
    tabButtons[nextIndex].focus();
  }

  return (
    <Section title="Смотреть" className="movie-details__section--watch">
      <div className="movie-details__watch-panel">
        <div className="movie-details__tabs" role="tablist" aria-label="Источники просмотра" onKeyDown={handleTabKeyDown}>
          <button
            id="watch-tab-official"
            className="movie-details__tab"
            type="button"
            role="tab"
            aria-selected={activeTab === "official"}
            aria-controls="watch-panel-official"
            tabIndex={activeTab === "official" ? 0 : -1}
            onClick={() => setActiveTab("official")}
          >
            Официальные источники
          </button>
          <button
            id="watch-tab-search"
            className="movie-details__tab"
            type="button"
            role="tab"
            aria-selected={activeTab === "search"}
            aria-controls="watch-panel-search"
            tabIndex={activeTab === "search" ? 0 : -1}
            onClick={() => setActiveTab("search")}
          >
            Искать в интернете
          </button>
        </div>
        {activeTab === "official" ? (
          <div id="watch-panel-official" className="movie-details__watch-content" role="tabpanel" aria-labelledby="watch-tab-official">
            <div className="movie-details__watch-columns">
              <ProviderList groups={russianGroups} regionName="В России" link={russianLink} />
              {originalRegion && (
                <ProviderList
                  groups={originalGroups}
                  regionName={`В оригинале · ${originalRegion.name || originalRegion.native_name || originalRegion.english_name || originalRegion.iso_3166_1}`}
                  link={originalLink}
                />
              )}
            </div>
            <p className="movie-details__muted movie-details__watch-note">
              Доступность зависит от региона и может меняться.
            </p>
          </div>
        ) : (
          <div id="watch-panel-search" className="movie-details__watch-content" role="tabpanel" aria-labelledby="watch-tab-search" />
        )}
      </div>
    </Section>
  );
}

function RecommendationsSection({ recommendations }) {
  const items = (Array.isArray(recommendations?.results) ? recommendations.results : [])
    .filter((item) => getMediaId(item))
    .slice(0, 8);
  if (items.length === 0) return null;

  return (
    <Section title="Рекомендации" className="movie-details__section--recommendations">
      <div className="movie-details__recommendations">
        {items.map((item) => (
          <MediaCard key={getMediaId(item)} item={item} mediaType="movie" compact />
        ))}
      </div>
    </Section>
  );
}

export default function MovieDetailsSections({ media }) {
  const hasTrailer = Boolean(getTrailer(media.videos));
  const hasBackdrops = Array.isArray(media.images?.backdrops) && media.images.backdrops.length > 0;

  return (
    <div className="movie-details">
      <CrewSection credits={media.credits} />
      <CastSection credits={media.credits} />
      {(hasTrailer || hasBackdrops) && (
        <div className={`movie-details__media-row${hasTrailer && hasBackdrops ? " movie-details__media-row--split" : ""}`}>
          <TrailerSection videos={media.videos} />
          <GallerySection images={media.images} />
        </div>
      )}
      <WatchSection media={media} />
      <RecommendationsSection recommendations={media.recommendations} />
    </div>
  );
}