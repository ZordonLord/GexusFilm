import { useEffect } from "react";
import { BrowserRouter, Routes, Route, useLocation } from "react-router-dom";

import HomePage from "./pages/HomePage";
import MediaPage from "./pages/MediaPage";
import CatalogPage from "./pages/CatalogPage";
import SearchPage from "./pages/SearchPage";
import AuthPage from "./pages/AuthPage";
import ProfilePage from "./pages/ProfilePage";
import UserMediaPage from "./pages/UserMediaPage";
import { AuthProvider } from "./contexts/AuthContext";
import { SidebarProvider } from "./components/SidebarProvider";

function ScrollToTop() {
  const { pathname } = useLocation();

  useEffect(() => {
    window.scrollTo({ top: 0, left: 0, behavior: "auto" });
  }, [pathname]);

  return null;
}

export default function App() {
  return (
    <BrowserRouter>
      <ScrollToTop />
      <AuthProvider>
        <SidebarProvider>
          <Routes>
            <Route path="/" element={<HomePage />} />
            <Route path="/movies" element={<CatalogPage key="movies-catalog" movieOnly />} />
            <Route path="/tv" element={<CatalogPage key="tv-catalog" tvOnly />} />
            <Route path="/discover" element={<CatalogPage key="all-catalog" />} />
            <Route path="/search" element={<SearchPage />} />
            <Route path="/auth" element={<AuthPage />} />
            <Route path="/profile" element={<ProfilePage />} />
            <Route path="/favorites" element={<UserMediaPage kind="favorites" />} />
            <Route path="/history" element={<UserMediaPage kind="history" />} />
            <Route path="/:type/:id" element={<MediaPage />} />
          </Routes>
        </SidebarProvider>
      </AuthProvider>
    </BrowserRouter>
  );
}
