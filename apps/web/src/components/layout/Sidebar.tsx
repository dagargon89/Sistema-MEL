import { NavLink } from "react-router-dom";
import { Star } from "lucide-react";
import { navParaRol } from "@/config/nav";
import type { RolClave } from "@/lib";
import { cn } from "@/components/ui/cn";

export function Sidebar({ rol, onNavigate }: { rol: RolClave; onNavigate?: () => void }) {
  const grupos = navParaRol(rol);
  return (
    <nav aria-label="Navegación principal" className="flex h-full flex-col gap-5 overflow-y-auto px-3 py-4">
      <div className="px-2">
        <p className="text-lg font-bold text-primary">Sistema MEL</p>
        <p className="text-xs text-text-muted">Participa Juárez</p>
      </div>
      {grupos.map((g, gi) => (
        <div key={gi}>
          {g.label && (
            <p className="px-2 pb-1 text-xs font-semibold uppercase tracking-wide text-text-muted">{g.label}</p>
          )}
          <ul className="flex flex-col gap-0.5">
            {g.items.map((item) => {
              const Icon = item.icon;
              return (
                <li key={item.path}>
                  <NavLink
                    to={item.path}
                    end={item.path === "/"}
                    onClick={onNavigate}
                    className={({ isActive }) =>
                      cn(
                        "flex items-center gap-2.5 rounded-md px-2.5 py-2 text-sm font-medium transition-colors",
                        "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary",
                        isActive
                          ? "bg-primary text-inverse"
                          : "text-text hover:bg-primary-soft",
                      )
                    }
                  >
                    <Icon className="size-4 shrink-0" aria-hidden="true" />
                    <span className="flex-1">{item.label}</span>
                    {item.star && <Star className="size-3.5 fill-accent text-accent-text" aria-label="Flujo prioritario" />}
                  </NavLink>
                </li>
              );
            })}
          </ul>
        </div>
      ))}
    </nav>
  );
}
