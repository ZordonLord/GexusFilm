// Утилиты для работы с медиа (фильмы / сериалы)

const VALID_MEDIA_TYPES = new Set(["movie", "tv"]);

const TMDB_GENRE_NAMES = {
  12: "Приключения",
  14: "Фэнтези",
  16: "Анимация",
  18: "Драма",
  27: "Ужасы",
  28: "Боевик",
  35: "Комедия",
  36: "История",
  37: "Вестерн",
  53: "Триллер",
  80: "Криминал",
  99: "Документальный",
  878: "Фантастика",
  9648: "Детектив",
  10402: "Музыка",
  10749: "Мелодрама",
  10751: "Семейный",
  10752: "Военный",
  10759: "Боевик и приключения",
  10762: "Детский",
  10763: "Новости",
  10764: "Реалити",
  10765: "Фантастика и фэнтези",
  10766: "Мыльная опера",
  10767: "Ток-шоу",
  10768: "Военный и политика",
  10770: "Телефильм",
};

export function getMediaType(item, fallback = "movie") {
  const type = item?.media_type;
  return VALID_MEDIA_TYPES.has(type) ? type : fallback;
}

export function getMediaTypeLabel(type) {
  return type === "tv" ? "Сериал" : "Фильм";
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

export function getMediaGenres(item) {
  if (Array.isArray(item?.genres) && item.genres.length > 0) {
    return item.genres
      .map((genre) => (typeof genre === "string" ? genre : genre?.name))
      .filter(Boolean);
  }

  if (Array.isArray(item?.genre_ids)) {
    return item.genre_ids
      .map((genreId) => TMDB_GENRE_NAMES[Number(genreId)])
      .filter(Boolean);
  }

  return [];
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
