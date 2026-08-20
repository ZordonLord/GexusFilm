const SEARCH_PATH = "/search";

export function normalizeSearchQuery(value) {
  return typeof value === "string" ? value.trim() : "";
}

export function buildSearchPath(query) {
  const normalizedQuery = normalizeSearchQuery(query);
  const searchParams = new URLSearchParams();

  if (normalizedQuery) {
    searchParams.set("q", normalizedQuery);
  }

  const queryString = searchParams.toString();
  return queryString ? `${SEARCH_PATH}?${queryString}` : SEARCH_PATH;
}