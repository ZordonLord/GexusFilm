import assert from "node:assert/strict";
import test from "node:test";

import { getEnglishAlternativeTitle } from "./englishTitle.js";

test("prefers the English translation when available", () => {
  assert.equal(getEnglishAlternativeTitle(
    { titles: [{ iso_3166_1: "US", title: "US marketing title" }] },
    { translations: [{ iso_639_1: "en", data: { title: "English translation" } }] },
    "原題",
  ), "English translation");
});

test("falls back to an English-region alternative title", () => {
  assert.equal(getEnglishAlternativeTitle(
    { titles: [{ iso_3166_1: "GB", title: "British title" }] },
    { translations: [] },
    "原題",
  ), "British title");
});

test("does not label the original or non-English region title as English", () => {
  assert.equal(getEnglishAlternativeTitle(
    { titles: [
      { iso_3166_1: "JP", title: "日本語の題名" },
      { iso_3166_1: "US", title: "Original title" },
    ] },
    { translations: [] },
    "Original title",
  ), null);
});