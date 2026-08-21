import { useEffect, useState } from "react";

import { SidebarContext } from "./SidebarContext";

const MOBILE_QUERY = "(max-width: 768px)";

function getIsMobile() {
  return typeof window !== "undefined" && window.matchMedia(MOBILE_QUERY).matches;
}

export function SidebarProvider({ children }) {
  const [isMobile, setIsMobile] = useState(getIsMobile);
  const [isOpen, setIsOpen] = useState(() => !getIsMobile());

  useEffect(() => {
    const mediaQuery = window.matchMedia(MOBILE_QUERY);
    const handleViewportChange = (event) => {
      setIsMobile(event.matches);
      setIsOpen(!event.matches);
    };

    mediaQuery.addEventListener("change", handleViewportChange);

    return () => mediaQuery.removeEventListener("change", handleViewportChange);
  }, []);

  const toggleSidebar = () => setIsOpen((open) => !open);
  const closeSidebar = () => setIsOpen(false);

  return (
    <SidebarContext.Provider value={{ isMobile, isOpen, toggleSidebar, closeSidebar }}>
      {children}
    </SidebarContext.Provider>
  );
}