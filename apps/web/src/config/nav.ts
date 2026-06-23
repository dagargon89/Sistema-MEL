import {
  LayoutDashboard,
  CalendarDays,
  ClipboardCheck,
  Package,
  Copy,
  Users,
  Target,
  TrendingUp,
  Megaphone,
  Building2,
  FolderTree,
  MessageSquare,
  ScrollText,
  type LucideIcon,
} from "lucide-react";
import type { RolClave } from "@/lib";

export interface NavItem {
  path: string;
  label: string;
  icon: LucideIcon;
  roles: RolClave[];
  star?: boolean;
}
export interface NavGroup {
  label: string | null;
  items: NavItem[];
}

const TODOS: RolClave[] = ["capturista", "coordinacion", "direccion", "administrador"];

// Inventario de pantallas y roles (doc 09 §2/§3).
export const NAV: NavGroup[] = [
  {
    label: null,
    items: [{ path: "/", label: "Tablero", icon: LayoutDashboard, roles: TODOS }],
  },
  {
    label: "Captura",
    items: [
      { path: "/programacion", label: "Programación", icon: CalendarDays, roles: ["capturista", "coordinacion"] },
      { path: "/ejecuciones", label: "Ejecuciones", icon: ClipboardCheck, roles: ["capturista", "coordinacion"], star: true },
      { path: "/productos", label: "Productos", icon: Package, roles: ["capturista", "coordinacion"] },
    ],
  },
  {
    label: "Calidad",
    items: [
      { path: "/duplicados", label: "Duplicados", icon: Copy, roles: ["coordinacion"], star: true },
      { path: "/personas", label: "Personas", icon: Users, roles: ["coordinacion"] },
    ],
  },
  {
    label: "Metas y resultados",
    items: [
      { path: "/metas", label: "Metas", icon: Target, roles: TODOS },
      { path: "/resultados", label: "Resultados", icon: TrendingUp, roles: ["coordinacion"] },
      { path: "/incidencia", label: "Incidencia", icon: Megaphone, roles: ["capturista", "coordinacion"] },
      { path: "/verticales", label: "Verticales", icon: Building2, roles: ["capturista", "coordinacion"] },
    ],
  },
  {
    label: "Gobernanza",
    items: [
      { path: "/catalogos", label: "Catálogos", icon: FolderTree, roles: ["coordinacion", "administrador"] },
      { path: "/solicitudes", label: "Solicitudes", icon: MessageSquare, roles: TODOS },
      { path: "/auditoria", label: "Auditoría", icon: ScrollText, roles: ["coordinacion", "direccion", "administrador"] },
    ],
  },
];

export function navParaRol(rol: RolClave): NavGroup[] {
  // El administrador funciona como superadmin: ve todas las secciones.
  if (rol === "administrador") return NAV;
  return NAV.map((g) => ({ ...g, items: g.items.filter((i) => i.roles.includes(rol)) })).filter(
    (g) => g.items.length > 0,
  );
}
