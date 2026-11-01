export function getTmdbWatchLink(value) {
  if (typeof value !== "string") return null;

  try {
    const url = new URL(value);
    const isWatchPath = /^\/movie\/\d+[^/]*\/watch$/.test(url.pathname);

    if (url.protocol !== "https:" || url.hostname !== "www.themoviedb.org" || !isWatchPath) {
      return null;
    }

    return url.href;
  } catch {
    return null;
  }
}