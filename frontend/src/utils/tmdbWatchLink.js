export function getTmdbWatchLink(value, mediaType = "movie") {
  if (typeof value !== "string") return null;
  if (mediaType !== "movie" && mediaType !== "tv") return null;

  try {
    const url = new URL(value);
    const isWatchPath = new RegExp(`^/${mediaType}/\\d+[^/]*\\/watch$`).test(url.pathname);

    if (url.protocol !== "https:" || url.hostname !== "www.themoviedb.org" || !isWatchPath) {
      return null;
    }

    return url.href;
  } catch {
    return null;
  }
}