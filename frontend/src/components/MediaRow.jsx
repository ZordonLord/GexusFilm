// Универсальный ряд медиа (фильмы / сериалы)

import { useRef, useState, useEffect } from "react";
import { Link } from "react-router-dom";

import MediaCard from "./MediaCard";
import SkeletonCard from "./SkeletonCard";
import { getMediaKey } from "../utils/media";

import "../styles/MediaRow.css";

export default function MediaRow({
  title,
  items = [],
  mediaType = "movie",
  loading = false,
  compact = false,
  showOverlayControls = true,
  titleHref = "",
  viewAllHref = "",
  viewAllLabel = "Смотреть все",
}) {
  const rowRef = useRef(null);

  const [canLeft, setCanLeft] = useState(false);
  const [canRight, setCanRight] = useState(false);

  const updateButtons = () => {
    const row = rowRef.current;

    if (!row) {
      return;
    }

    setCanLeft(row.scrollLeft > 5);
    setCanRight(row.scrollLeft + row.clientWidth < row.scrollWidth - 5);
  };

  useEffect(() => {
    updateButtons();

    const row = rowRef.current;

    if (!row) {
      return undefined;
    }

    row.addEventListener("scroll", updateButtons);
    window.addEventListener("resize", updateButtons);

    return () => {
      row.removeEventListener("scroll", updateButtons);
      window.removeEventListener("resize", updateButtons);
    };
  }, [items]);

  const scroll = (direction) => {
    const row = rowRef.current;

    if (!row) {
      return;
    }

    row.scrollBy({
      left: direction * 520,
      behavior: "smooth",
    });
  };

  return (
    <section className={`media-section ${compact ? "media-section--compact" : ""}`}>

      <div className="media-section__header">
        {titleHref ? (
          <Link className="media-section__title-link" to={titleHref}>
            <h2>{title}</h2>
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path strokeLinecap="round" strokeLinejoin="round" d="M5 12h14m-6-6 6 6-6 6" />
            </svg>
          </Link>
        ) : (
          <h2>{title}</h2>
        )}

        <div className="media-row__actions">
          {viewAllHref && (
            <Link className="media-section__view-all" to={viewAllHref}>
              {viewAllLabel}
              <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" strokeLinejoin="round" d="M5 12h14m-6-6 6 6-6 6" />
              </svg>
            </Link>
          )}

          <div className="media-row__controls">
            <button
              className={`row-btn ${!canLeft ? "disabled" : ""}`}
              onClick={() => scroll(-1)}
              aria-label={`Прокрутить раздел «${title}» влево`}
            >
              <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" strokeLinejoin="round" d="m15 19-7-7 7-7" />
              </svg>
            </button>

            <button
              className={`row-btn ${!canRight ? "disabled" : ""}`}
              onClick={() => scroll(1)}
              aria-label={`Прокрутить раздел «${title}» вправо`}
            >
              <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" strokeLinejoin="round" d="m9 5 7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div className="row-wrapper">
        {showOverlayControls && canLeft && (
          <button
            className="row-overlay row-overlay--left"
            onClick={() => scroll(-1)}
            aria-label={`Прокрутить раздел «${title}» влево`}
          >
            <svg className="row-overlay__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
        )}

        {showOverlayControls && canRight && (
          <button
            className="row-overlay row-overlay--right"
            onClick={() => scroll(1)}
            aria-label={`Прокрутить раздел «${title}» вправо`}
          >
            <svg className="row-overlay__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        )}

        <div
          className="row"
          ref={rowRef}
        >
          {loading
            ? Array.from({ length: 8 }).map((_, i) => (
              <SkeletonCard key={`skeleton-${i}`} compact={compact} />
              ))
            : items.map((item) => {
                const key = getMediaKey(item, mediaType);

                if (!key) {
                  return null;
                }

                return (
                  <MediaCard
                    key={key}
                    item={item}
                    mediaType={mediaType}
                    compact={compact}
                  />
                );
              })}
        </div>
      </div>

    </section>
  );
}