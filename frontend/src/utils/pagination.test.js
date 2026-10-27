import assert from "node:assert/strict";
import test from "node:test";

import { getPaginationItems } from "./pagination.js";

test("returns all pages when the result has no more than the window size", () => {
  assert.deepEqual(getPaginationItems(3, 5), [1, 2, 3, 4, 5]);
});

test("shows first and last page with a centered window and ellipses", () => {
  assert.deepEqual(getPaginationItems(9, 500), [1, "ellipsis", 5, 6, 7, 8, 9, 10, 11, 12, 13, "ellipsis", 500]);
});

test("keeps a full page window near the beginning and end", () => {
  assert.deepEqual(getPaginationItems(1, 500), [1, 2, 3, 4, 5, 6, 7, 8, 9, "ellipsis", 500]);
  assert.deepEqual(getPaginationItems(500, 500), [1, "ellipsis", 492, 493, 494, 495, 496, 497, 498, 499, 500]);
});

test("renders a single omitted page explicitly instead of an ellipsis", () => {
  assert.deepEqual(getPaginationItems(1, 11), [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]);
});

test("clamps invalid current pages and handles empty results", () => {
  assert.deepEqual(getPaginationItems(0, 4), [1, 2, 3, 4]);
  assert.deepEqual(getPaginationItems(20, 4), [1, 2, 3, 4]);
  assert.deepEqual(getPaginationItems(1, 0), []);
});