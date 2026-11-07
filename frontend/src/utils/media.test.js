import test from "node:test";
import assert from "node:assert/strict";

import { getMediaIdentityKey } from "./media.js";

test("media identity distinguishes movies and TV with the same numeric id", () => {
  assert.equal(getMediaIdentityKey("movie", 550), "movie:550");
  assert.equal(getMediaIdentityKey("tv", 550), "tv:550");
  assert.notEqual(getMediaIdentityKey("movie", 550), getMediaIdentityKey("tv", 550));
});

test("media identity rejects unsupported types and missing identifiers", () => {
  assert.equal(getMediaIdentityKey("person", 550), null);
  assert.equal(getMediaIdentityKey("movie", null), null);
});