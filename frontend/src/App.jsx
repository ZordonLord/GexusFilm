import { BrowserRouter, Routes, Route } from "react-router-dom";

import HomePage from "./pages/HomePage";
import MediaPage from "./pages/MediaPage";
import CatalogPage from "./pages/CatalogPage";
import SearchPage from "./pages/SearchPage";
import { SidebarProvider } from "./components/SidebarProvider";

export default function App() {
  return (
    <BrowserRouter>
      <SidebarProvider>
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/movies" element={<CatalogPage key="movies-catalog" movieOnly />} />
          <Route path="/tv" element={<CatalogPage key="tv-catalog" tvOnly />} />
          <Route path="/discover" element={<CatalogPage key="all-catalog" />} />
          <Route path="/search" element={<SearchPage />} />
          <Route path="/:type/:id" element={<MediaPage />} />
        </Routes>
      </SidebarProvider>
    </BrowserRouter>
  );
}
