const ENGLISH_REGIONS = ["US", "GB", "CA", "AU", "NZ", "IE"];

export function getEnglishAlternativeTitle(alternativeTitles, translations, originalTitle) {
  const translatedTitles = Array.isArray(translations?.translations) ? translations.translations : [];
  const englishTranslation = translatedTitles.find((entry) => (
    entry.iso_639_1 === "en" && (entry.data?.title?.trim() || entry.data?.name?.trim())
  ));
  const translatedTitle = englishTranslation?.data?.title?.trim() || englishTranslation?.data?.name?.trim();

  if (translatedTitle && translatedTitle !== originalTitle) {
    return translatedTitle;
  }

  const titles = Array.isArray(alternativeTitles?.titles)
    ? alternativeTitles.titles
    : Array.isArray(alternativeTitles?.results) ? alternativeTitles.results : [];

  for (const region of ENGLISH_REGIONS) {
    const candidate = titles.find((entry) => entry.iso_3166_1 === region && entry.title?.trim());
    if (candidate && candidate.title.trim() !== originalTitle) return candidate.title.trim();
  }

  return null;
}