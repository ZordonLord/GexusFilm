import { useEffect, useRef, useState } from "react";
import { getMediaId, getMediaTitle } from "../utils/media";
import { getFranchiseParts } from "../utils/movieCollection";
import { getTmdbWatchLink } from "../utils/tmdbWatchLink";
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

function Section({ title, children, className = "", id, collapsible = false }) {
  const [isDesktop, setIsDesktop] = useState(() => (
    typeof window !== "undefined" && window.matchMedia("(min-width: 761px)").matches
  ));
  const [isExpanded, setIsExpanded] = useState(() => (
    collapsible && typeof window !== "undefined" && window.matchMedia("(min-width: 761px)").matches
  ));
  const contentId = `${id || title.toLowerCase().replace(/[^a-zа-яё0-9]+/gi, "-")}-content`;

  function toggleDisclosure() {
    if (!collapsible) return;
    setIsExpanded((expanded) => (window.matchMedia("(max-width: 760px)").matches ? !expanded : true));
  }

  useEffect(() => {
    if (!collapsible) return undefined;

    const desktopQuery = window.matchMedia("(min-width: 761px)");
    const updateExpanded = (event) => {
      setIsDesktop(event.matches);
      setIsExpanded(event.matches);
    };

    desktopQuery.addEventListener("change", updateExpanded);
    return () => desktopQuery.removeEventListener("change", updateExpanded);
  }, [collapsible]);

  return (
    <section id={id} className={`movie-details__section ${className}`.trim()} aria-label={title}>
      {collapsible && !isDesktop ? (
        <h2 className="movie-details__title">
          <button
            className="movie-details__disclosure-trigger"
            type="button"
            aria-expanded={isExpanded}
            aria-controls={contentId}
            onClick={toggleDisclosure}
          >
            <span>{title}</span>
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
              <path d="m6 9 6 6 6-6" />
            </svg>
          </button>
        </h2>
      ) : (
        <h2 className="movie-details__title">{title}</h2>
      )}
      <div id={collapsible ? contentId : undefined} className="movie-details__section-content" hidden={collapsible && !isDesktop && !isExpanded}>
        {children}
      </div>
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

function ScrollControls({ targetRef, label }) {
  function scroll(direction) {
    const element = targetRef.current;
    if (!element) return;

    element.scrollBy({
      left: direction * Math.max(element.clientWidth * 0.8, 240),
      behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth",
    });
  }

  return (
    <div className="movie-details__scroll-controls" aria-label={label}>
      <button type="button" aria-label={`Прокрутить: ${label} назад`} onClick={() => scroll(-1)}>
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
          <path d="m15 18-6-6 6-6" />
        </svg>
      </button>
      <button type="button" aria-label={`Прокрутить: ${label} вперёд`} onClick={() => scroll(1)}>
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
          <path d="m9 18 6-6-6-6" />
        </svg>
      </button>
    </div>
  );
}

function CastSection({ credits }) {
  const cast = Array.isArray(credits?.cast) ? credits.cast.slice(0, 12) : [];
  const castRef = useRef(null);
  if (cast.length === 0) return null;

  return (
    <Section title="Актёры" className="movie-details__section--cast" collapsible>
      <div className="movie-details__scroll-header">
        <p className="movie-details__scroll-hint">Основной состав</p>
        <ScrollControls targetRef={castRef} label="Актёры" />
      </div>
      <div className="movie-details__people-viewport" ref={castRef} tabIndex="0" aria-label="Актёры фильма">
        <ul className="movie-details__people">
          {cast.map((person) => (
            <PersonPortrait key={person.credit_id || person.id} person={person} role={person.character} />
          ))}
        </ul>
      </div>
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
    <Section title="Съёмочная группа" className="movie-details__section--crew" collapsible>
      <ul className="movie-details__crew movie-details__crew--editorial">
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

function TrailerSection({ videos, mediaType }) {
  const trailer = getTrailer(videos);
  if (!trailer) return null;

  return (
    <Section title={mediaType === "tv" ? "Трейлер сериала" : "Трейлер"}>
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

function GallerySection({ images, mediaType }) {
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
    <Section title={mediaType === "tv" ? "Кадры из сериала" : "Кадры из фильма"}>
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
                      {link ? (
                        <a
                          className="movie-details__provider-link"
                          href={link}
                          target="_blank"
                          rel="noopener noreferrer"
                          aria-label={`${provider.provider_name} — открыть страницу фильма в TMDB`}
                        >
                          {logo && <img src={logo} alt="" width="36" height="36" loading="lazy" />}
                          <span>{provider.provider_name}</span>
                          <span className="movie-details__provider-external" aria-hidden="true">↗</span>
                        </a>
                      ) : (
                        <>
                          {logo && <img src={logo} alt="" width="36" height="36" loading="lazy" />}
                          <span>{provider.provider_name}</span>
                        </>
                      )}
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

function WatchSection({ media, mediaType }) {
  const [activeTab, setActiveTab] = useState("official");
  const watchProviders = media["watch/providers"];
  const russianRegion = watchProviders?.results?.RU;
  const russianGroups = getProviderGroups(russianRegion);
  const russianLink = getTmdbWatchLink(russianRegion?.link, mediaType);
  const productionCountries = Array.isArray(media.production_countries)
    ? media.production_countries
    : (Array.isArray(media.origin_country) ? media.origin_country : []).map((iso_3166_1) => ({ iso_3166_1 }));
  const originalRegion = productionCountries
    .map((country) => ({ ...country, providers: watchProviders?.results?.[country.iso_3166_1] }))
    .find((country) => country.providers);
  const originalGroups = getProviderGroups(originalRegion?.providers);
  const originalLink = getTmdbWatchLink(originalRegion?.providers?.link, mediaType);

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
      <Section title="Смотреть" className="movie-details__section--watch" id="watch-section">
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
                  regionName={`${mediaType === "tv" ? "В стране производства" : "В оригинале"} · ${originalRegion.name || originalRegion.native_name || originalRegion.english_name || originalRegion.iso_3166_1}`}
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

function FranchiseSection({ media, collection }) {
  const parts = getFranchiseParts(collection, getMediaId(media));
  const partsRef = useRef(null);
  if (parts.length === 0) return null;

  return (
    <Section title="Части франшизы" className="movie-details__section--franchise">
      <div className="movie-details__scroll-header">
        <p className="movie-details__scroll-hint">{collection.name || "Другие фильмы коллекции"}</p>
        <ScrollControls targetRef={partsRef} label="Части франшизы" />
      </div>
      <div className="movie-details__recommendations-viewport" ref={partsRef} tabIndex="0" aria-label="Фильмы этой франшизы">
        <div className="movie-details__recommendations">
          {parts.map((part) => (
            <MediaCard key={getMediaId(part)} item={part} mediaType="movie" compact />
          ))}
        </div>
      </div>
    </Section>
  );
}

function RecommendationsSection({ recommendations, mediaType }) {
  const items = (Array.isArray(recommendations?.results) ? recommendations.results : [])
    .filter((item) => getMediaId(item))
    .slice(0, 8);
  const recommendationsRef = useRef(null);
  if (items.length === 0) return null;

  return (
    <Section title={mediaType === "tv" ? "Похожие сериалы" : "Рекомендации"} className="movie-details__section--recommendations">
      <div className="movie-details__scroll-header">
        <p className="movie-details__scroll-hint">{mediaType === "tv" ? "Ещё сериалы для просмотра" : "Ещё фильмы для просмотра"}</p>
        <ScrollControls targetRef={recommendationsRef} label="Рекомендации" />
      </div>
      <div className="movie-details__recommendations-viewport" ref={recommendationsRef} tabIndex="0" aria-label="Рекомендованные фильмы">
        <div className="movie-details__recommendations">
          {items.map((item) => (
            <MediaCard key={getMediaId(item)} item={item} mediaType={mediaType} compact />
          ))}
        </div>
      </div>
    </Section>
  );
}

export function MediaDetailsSections({ media, collection, mediaType = "movie" }) {
  const hasTrailer = Boolean(getTrailer(media.videos));
  const hasBackdrops = Array.isArray(media.images?.backdrops) && media.images.backdrops.length > 0;

  return (
    <div className="movie-details">
      <CrewSection credits={media.credits} />
      <CastSection credits={media.credits} />
      {(hasTrailer || hasBackdrops) && (
        <div className={`movie-details__media-row${hasTrailer && hasBackdrops ? " movie-details__media-row--split" : ""}`}>
      <TrailerSection videos={media.videos} mediaType={mediaType} />
      <GallerySection images={media.images} mediaType={mediaType} />
        </div>
      )}
      <WatchSection media={media} mediaType={mediaType} />
      {mediaType === "movie" && <FranchiseSection media={media} collection={collection} />}
      <RecommendationsSection recommendations={media.recommendations} mediaType={mediaType} />
    </div>
  );
}

export default function MovieDetailsSections({ media, collection }) {
  return <MediaDetailsSections media={media} collection={collection} mediaType="movie" />;
}