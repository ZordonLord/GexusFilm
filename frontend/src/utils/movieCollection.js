import { getMediaId, getMediaTitle } from "./media.js";

export function getFranchiseParts(collection, currentMovieId) {
  const parts = Array.isArray(collection?.parts) ? collection.parts : [];
  const seenIds = new Set();

  return parts
    .filter((part) => {
      const id = getMediaId(part);
      if (!id || id === String(currentMovieId) || seenIds.has(id)) return false;
      seenIds.add(id);
      return true;
    })
    .sort((first, second) => {
      const firstDate = first.release_date || "";
      const secondDate = second.release_date || "";
      return firstDate.localeCompare(secondDate) || getMediaTitle(first).localeCompare(getMediaTitle(second));
    });
}