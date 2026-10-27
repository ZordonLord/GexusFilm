const PAGE_WINDOW_SIZE = 9;
const PAGE_WINDOW_RADIUS = Math.floor(PAGE_WINDOW_SIZE / 2);

export function getPaginationItems(currentPage, totalPages) {
  const total = Math.max(0, Math.floor(Number(totalPages) || 0));
  if (total === 0) return [];

  const current = Math.min(total, Math.max(1, Math.floor(Number(currentPage) || 1)));
  let start = Math.max(1, current - PAGE_WINDOW_RADIUS);
  let end = Math.min(total, start + PAGE_WINDOW_SIZE - 1);
  start = Math.max(1, end - PAGE_WINDOW_SIZE + 1);

  const pages = new Set([1, total]);
  for (let page = start; page <= end; page += 1) pages.add(page);

  const items = [];
  let previousPage = 0;
  [...pages].sort((first, second) => first - second).forEach((page) => {
    const gap = page - previousPage;
    if (gap === 2) items.push(previousPage + 1);
    if (gap > 2) items.push("ellipsis");
    items.push(page);
    previousPage = page;
  });

  return items;
}