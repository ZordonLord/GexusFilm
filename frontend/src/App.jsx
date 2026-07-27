import { BrowserRouter, Routes, Route } from "react-router-dom";

import HomePage from "./pages/HomePage";
import MediaPage from "./pages/MediaPage";

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<HomePage />} />
        <Route path="/:type/:id" element={<MediaPage />} />
      </Routes>
    </BrowserRouter>
  );
}
