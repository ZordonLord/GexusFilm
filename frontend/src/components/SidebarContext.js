import { createContext, useContext } from "react";

export const SidebarContext = createContext(null);

export function useSidebar() {
  const context = useContext(SidebarContext);

  if (!context) {
    throw new Error("useSidebar должен использоваться внутри SidebarProvider");
  }

  return context;
}