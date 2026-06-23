import type { RolClave } from "@/lib";
import { cn } from "./cn";

const map: Record<RolClave, { box: string; label: string }> = {
  capturista: { box: "bg-primary-soft text-primary", label: "Capturista" },
  coordinacion: { box: "bg-accent text-accent-text", label: "Coordinación MEL" },
  direccion: { box: "bg-info-soft text-info", label: "Dirección" },
  administrador: { box: "bg-neutral-soft text-neutral", label: "Administrador" },
};

export function RoleBadge({ rol }: { rol: RolClave }) {
  const s = map[rol];
  return (
    <span className={cn("inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium", s.box)}>
      {s.label}
    </span>
  );
}
