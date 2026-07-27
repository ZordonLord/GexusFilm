// Утилиты для работы с медиа (фильмы / сериалы)

const VALID_MEDIA_TYPES = new Set(["movie", "tv"]);

export function getMediaType(item, fallback = "movie") {
  const type = item?.media_type;
  return VALID_MEDIA_TYPES.has(type) ? type : fallback;
}

export function getMediaTitle(item) {
  return item?.title || item?.name || "Без названия";
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
  if (!rating || rating <= 0) {
    return "—";
  }
  return rating.toFixed(1);
}
