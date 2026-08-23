import { normalizeSearchQuery } from "./search";

export const DEFAULT_SORT = "popularity.desc";
export const CATALOG_DEFAULT_SORT = "year.desc";

export const ALLOWED_TYPES = new Set(["all", "movie", "tv"]);

export const ALLOWED_SORTS = new Set([
  "popularity.desc",
  "popularity.asc",
  "vote_average.desc",
  "vote_average.asc",
  "year.desc",
  "year.asc",
]);

export const SORT_OPTIONS = [
  { value: "popularity.desc", label: "Сначала популярные" },
  { value: "popularity.asc", label: "Сначала менее популярные" },
  { value: "vote_average.desc", label: "По рейтингу: сначала выше" },
  { value: "vote_average.asc", label: "По рейтингу: сначала ниже" },
  { value: "year.desc", label: "Сначала новые" },
  { value: "year.asc", label: "Сначала старые" },
];

export const TYPE_OPTIONS = [
  { value: "all", label: "Всё" },
  { value: "movie", label: "Фильмы" },
  { value: "tv", label: "Сериалы" },
];

function parseGenreIds(value) {
  if (!value) return [];

  return value
    .split(",")
    .map((genreId) => genreId.trim())
    .filter((genreId, index, genreIds) => /^\d+$/.test(genreId) && genreId !== "0" && genreIds.indexOf(genreId) === index)
    .slice(0, 5);
}

export function parseCatalogParams(searchParams, { fallbackType = "all", defaultSort = DEFAULT_SORT } = {}) {
  const typeParam = searchParams.get("type");
  const sortParam = searchParams.get("sort_by");
  const pageParam = Number.parseInt(searchParams.get("page") || "1", 10);
  const perPageParam = Number.parseInt(searchParams.get("per_page") || "20", 10);
  const explicitGenreId = searchParams.get("genre_id") || "";
  const genreIds = parseGenreIds(searchParams.get("genre_ids"));
  const excludedGenreIds = parseGenreIds(searchParams.get("exclude_genre_ids"));

  if (genreIds.length === 0 && /^\d+$/.test(explicitGenreId) && explicitGenreId !== "0") {
    genreIds.push(explicitGenreId);
  }

  return {
    query: normalizeSearchQuery(searchParams.get("q")),
    type: fallbackType === "all" && ALLOWED_TYPES.has(typeParam) ? typeParam : fallbackType,
    genreId: explicitGenreId,
    genreIds,
    excludedGenreIds,
    year: searchParams.get("year") || "",
    minRating: searchParams.get("min_rating") || "",
    region: searchParams.get("region")?.toUpperCase() || "",
    sortBy: ALLOWED_SORTS.has(sortParam) ? sortParam : defaultSort,
    page: Number.isInteger(pageParam) && pageParam > 0 ? pageParam : 1,
    perPage: Number.isInteger(perPageParam) && perPageParam >= 1 && perPageParam <= 50
      ? perPageParam
      : 20,
  };
}

export function updateCatalogParams(searchParams, changes, { defaultType = "all", defaultSort = DEFAULT_SORT } = {}) {
  const nextParams = new URLSearchParams(searchParams);

  if (changes.genre_ids !== undefined) nextParams.delete("genre_id");
  if (changes.genre_id !== undefined) nextParams.delete("genre_ids");

  Object.entries(changes).forEach(([key, value]) => {
    const isDefault = value === ""
      || value === null
      || value === undefined
      || (key === "type" && value === defaultType)
      || (key === "sort_by" && value === defaultSort);

    if (isDefault) {
      nextParams.delete(key);
    } else {
      nextParams.set(key, String(value));
    }
  });

  return nextParams;
}