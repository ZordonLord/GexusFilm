// Утилиты для работы с медиа (фильмы / сериалы)

const VALID_MEDIA_TYPES = new Set(["movie", "tv"]);

export function getMediaType(item, fallback = "movie") {
  const type = item?.media_type;
  return VALID_MEDIA_TYPES.has(type) ? type : fallback;
}

export function getMediaTitle(item) {
  return item?.title || item?.name || "Без названия";
}

export function getMediaId(item) {
  const id = item?.id ?? item?.source_id ?? item?.tmdb_id;

  return id === null || id === undefined || id === "" ? null : String(id);
}

export function getMediaKey(item, fallback = "movie") {
  const id = getMediaId(item);

  if (!id) {
    return null;
  }

  return `${getMediaType(item, fallback)}-${id}`;
}

export function getMediaYear(item) {
  const date = item?.release_date || item?.first_air_date;
  return date ? date.slice(0, 4) : "—";
}

export function getMediaRoute(type, id) {
  const normalizedType = VALID_MEDIA_TYPES.has(type) ? type : "movie";
  return `/${normalizedType}/${id}`;
}

export function formatRuntime(minutes) {
  if (!minutes || minutes <= 0) {
    return "—";
  }
  return `${minutes} мин`;
}

export function formatRating(rating) {
  const numericRating = Number(rating);

  if (!Number.isFinite(numericRating) || numericRating <= 0) {
    return "—";
  }

  return numericRating.toFixed(1);
}
