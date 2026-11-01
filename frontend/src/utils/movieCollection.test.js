import assert from "node:assert/strict";
import test from "node:test";

import { getFranchiseParts } from "./movieCollection.js";

test("removes the current movie and sorts remaining franchise parts by release date", () => {
  const parts = getFranchiseParts({
    parts: [
      { id: 3, title: "Third", release_date: "2020-01-01" },
      { id: 1, title: "Current", release_date: "1990-01-01" },
      { id: 2, title: "Second", release_date: "2000-01-01" },
    ],
  }, "1");

  assert.deepEqual(parts.map((part) => part.id), [2, 3]);
});

test("ignores missing and duplicate IDs and handles an empty collection", () => {
  assert.deepEqual(getFranchiseParts({
    parts: [
      { id: 2, title: "One" },
      { id: 2, title: "Duplicate" },
      { title: "No ID" },
    ],
  }, 1).map((part) => part.title), ["One"]);
  assert.deepEqual(getFranchiseParts(null, 1), []);
});