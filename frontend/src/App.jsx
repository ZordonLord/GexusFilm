import { BrowserRouter, Routes, Route } from "react-router-dom";

import HomePage from "./pages/HomePage";
import MediaPage from "./pages/MediaPage";
import CatalogPage from "./pages/CatalogPage";
import SearchPage from "./pages/SearchPage";

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<HomePage />} />
        <Route path="/discover" element={<CatalogPage />} />
        <Route path="/search" element={<SearchPage />} />
        <Route path="/:type/:id" element={<MediaPage />} />
      </Routes>
    </BrowserRouter>
  );
}
