import assert from "node:assert/strict";
import test from "node:test";

import { getTmdbWatchLink } from "./tmdbWatchLink.js";

test("accepts a TMDB movie watch page", () => {
  assert.equal(
    getTmdbWatchLink("https://www.themoviedb.org/movie/603-the-matrix/watch?locale=RU"),
    "https://www.themoviedb.org/movie/603-the-matrix/watch?locale=RU",
  );
});

test("accepts a TMDB TV watch page when the media type is TV", () => {
  assert.equal(
    getTmdbWatchLink("https://www.themoviedb.org/tv/1399-game-of-thrones/watch?locale=RU", "tv"),
    "https://www.themoviedb.org/tv/1399-game-of-thrones/watch?locale=RU",
  );
  assert.equal(
    getTmdbWatchLink("https://www.themoviedb.org/movie/603-the-matrix/watch?locale=RU", "tv"),
    null,
  );
});

test("rejects non-TMDB, non-HTTPS and non-watch URLs", () => {
  assert.equal(getTmdbWatchLink("https://www.themoviedb.org.evil.example/movie/603/watch"), null);
  assert.equal(getTmdbWatchLink("http://www.themoviedb.org/movie/603/watch"), null);
  assert.equal(getTmdbWatchLink("https://www.themoviedb.org/movie/603"), null);
  assert.equal(getTmdbWatchLink(null), null);
});