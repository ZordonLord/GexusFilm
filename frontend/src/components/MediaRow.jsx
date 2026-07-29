// Универсальный ряд медиа (фильмы / сериалы)

import { useRef, useState, useEffect } from "react";

import MediaCard from "./MediaCard";
import SkeletonCard from "./SkeletonCard";

import "../styles/MediaRow.css";

export default function MediaRow({
  title,
  items = [],
  mediaType = "movie",
  loading = false,
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
    <section className="media-section">

      <div className="media-section__header">
        <h2>{title}</h2>

        <div className="media-row__controls">
          <button
            className={`row-btn ${!canLeft ? "disabled" : ""}`}
            onClick={() => scroll(-1)}
            aria-label="Прокрутить влево"
          >
            ❮
          </button>

          <button
            className={`row-btn ${!canRight ? "disabled" : ""}`}
            onClick={() => scroll(1)}
            aria-label="Прокрутить вправо"
          >
            ❯
          </button>
        </div>
      </div>

      <div className="row-wrapper">
        {/* Оверлей слева — затемнение + стрелка */}
        {canLeft && (
          <button
            className="row-overlay row-overlay--left"
            onClick={() => scroll(-1)}
            aria-label="Прокрутить влево"
          >
            <svg className="row-overlay__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
        )}

        {/* Оверлей справа — затемнение + стрелка */}
        {canRight && (
          <button
            className="row-overlay row-overlay--right"
            onClick={() => scroll(1)}
            aria-label="Прокрутить вправо"
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
                <SkeletonCard key={`skeleton-${i}`} />
              ))
            : items.map((item) => (
                <MediaCard
                  key={`${item.media_type || mediaType}-${item.id}`}
                  item={item}
                  mediaType={mediaType}
                />
              ))}
        </div>
      </div>

    </section>
  );
}