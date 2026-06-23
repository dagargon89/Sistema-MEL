import { useState } from "react";
import { Outlet } from "react-router-dom";
import { useSession } from "@/store/session";
import { Sidebar } from "./Sidebar";
import { Topbar } from "./Topbar";
import { cn } from "@/components/ui/cn";

export function AppShell() {
  const { user } = useSession();
  const [drawerOpen, setDrawerOpen] = useState(false);
  const rol = user?.rol ?? "capturista";

  return (
    <div className="flex h-dvh bg-bg text-text">
      {/* Sidebar fijo en escritorio */}
      <aside className="hidden w-64 shrink-0 border-r border-border bg-surface lg:block">
        <Sidebar rol={rol} />
      </aside>

      {/* Drawer móvil */}
      {drawerOpen && (
        <div className="fixed inset-0 z-30 lg:hidden">
          <div className="absolute inset-0 bg-black/40" onClick={() => setDrawerOpen(false)} aria-hidden="true" />
          <div className={cn("absolute left-0 top-0 h-full w-64 border-r border-border bg-surface shadow-md")}>
            <Sidebar rol={rol} onNavigate={() => setDrawerOpen(false)} />
          </div>
        </div>
      )}

      <div className="flex min-w-0 flex-1 flex-col">
        <Topbar onOpenMenu={() => setDrawerOpen(true)} />
        <main className="flex-1 overflow-y-auto px-4 py-6 lg:px-8">
          <div className="mx-auto max-w-6xl">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
}
