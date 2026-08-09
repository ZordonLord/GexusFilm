import { BrowserRouter, Routes, Route, useLocation } from "react-router-dom";

import HomePage from "./pages/HomePage";
import MediaPage from "./pages/MediaPage";
import CatalogPage from "./pages/CatalogPage";
import SearchPage from "./pages/SearchPage";

function SearchRoute() {
  const location = useLocation();

  // Ключ URL-состояния не даёт локальному draft поиска расходиться с back/forward браузера.
  return <SearchPage key={location.search} />;
}

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<HomePage />} />
        <Route path="/discover" element={<CatalogPage />} />
        <Route path="/search" element={<SearchRoute />} />
        <Route path="/:type/:id" element={<MediaPage />} />
      </Routes>
    </BrowserRouter>
  );
}
