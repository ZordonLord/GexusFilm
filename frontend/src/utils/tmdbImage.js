const TMDB_IMAGE_SIZES = new Set([
  "w92",
  "w154",
  "w185",
  "w342",
  "w500",
  "w780",
  "original",
]);

const SAFE_IMAGE_FILENAME = /^[A-Za-z0-9_-]+\.(?:jpe?g|png|webp)$/i;

export function getTmdbImageUrl(imagePath, size = "w500") {
  if (typeof imagePath !== "string" || !TMDB_IMAGE_SIZES.has(size)) {
    return null;
  }

  const filename = imagePath.startsWith("/")
    ? imagePath.slice(1)
    : imagePath;

  if (!SAFE_IMAGE_FILENAME.test(filename)) {
    return null;
  }

  return `/tmdb-image/${size}/${filename}`;
}
