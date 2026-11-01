const ENGLISH_REGIONS = ["US", "GB", "CA", "AU", "NZ", "IE"];

export function getEnglishAlternativeTitle(alternativeTitles, translations, originalTitle) {
  const translatedTitles = Array.isArray(translations?.translations) ? translations.translations : [];
  const englishTranslation = translatedTitles.find((entry) => entry.iso_639_1 === "en" && entry.data?.title?.trim());

  if (englishTranslation && englishTranslation.data.title.trim() !== originalTitle) {
    return englishTranslation.data.title.trim();
  }

  const titles = Array.isArray(alternativeTitles?.titles) ? alternativeTitles.titles : [];

  for (const region of ENGLISH_REGIONS) {
    const candidate = titles.find((entry) => entry.iso_3166_1 === region && entry.title?.trim());
    if (candidate && candidate.title.trim() !== originalTitle) return candidate.title.trim();
  }

  return null;
}