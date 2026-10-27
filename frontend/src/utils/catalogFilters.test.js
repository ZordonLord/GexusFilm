import assert from "node:assert/strict";
import test from "node:test";

import {
  ALLOWED_CATALOG_TYPES,
  DEFAULT_CATALOG_PER_PAGE,
  PAGE_SIZE_OPTIONS,
  parseCatalogParams,
} from "./catalogFilters.js";

test("catalog defaults to movies with 20 results per page", () => {
  const filters = parseCatalogParams(new URLSearchParams(), {
    fallbackType: "movie",
    defaultPerPage: DEFAULT_CATALOG_PER_PAGE,
    pageSizeOptions: PAGE_SIZE_OPTIONS,
  });

  assert.equal(filters.type, "movie");
  assert.equal(filters.perPage, 20);
});

test("catalog accepts only page sizes that are multiples of TMDB page size", () => {
  for (const pageSize of [20, 40, 80]) {
    const filters = parseCatalogParams(new URLSearchParams(`per_page=${pageSize}`), {
      fallbackType: "tv",
      defaultPerPage: DEFAULT_CATALOG_PER_PAGE,
      pageSizeOptions: PAGE_SIZE_OPTIONS,
    });

    assert.equal(filters.perPage, pageSize);
    assert.equal(filters.type, "tv");
  }

  const invalidPageSize = parseCatalogParams(new URLSearchParams("per_page=50"), {
    fallbackType: "movie",
    defaultPerPage: DEFAULT_CATALOG_PER_PAGE,
    pageSizeOptions: PAGE_SIZE_OPTIONS,
  });
  assert.equal(invalidPageSize.perPage, 20);
});

test("legacy all catalog links fall back to movies while search can retain all", () => {
  const legacyCatalog = parseCatalogParams(new URLSearchParams("type=all"), {
    fallbackType: "movie",
    allowedTypes: ALLOWED_CATALOG_TYPES,
    pageSizeOptions: PAGE_SIZE_OPTIONS,
  });
  const search = parseCatalogParams(new URLSearchParams("type=all"));

  assert.equal(legacyCatalog.type, "movie");
  assert.equal(search.type, "all");
});