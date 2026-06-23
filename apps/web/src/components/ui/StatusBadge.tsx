import { CheckCircle2, AlertTriangle, FileEdit, Layers, Clock, type LucideIcon } from "lucide-react";
import { cn } from "./cn";

// control_registro (SRS §4). Estado no solo por color: lleva icono + texto (doc 08 §10).
const map: Record<string, { box: string; label: string; icon: LucideIcon }> = {
  OK: { box: "bg-success-soft text-success", label: "OK", icon: CheckCircle2 },
  INCOMPLETO: { box: "bg-warning-soft text-warning", label: "Incompleto", icon: FileEdit },
  REVISAR: { box: "bg-error-soft text-error", label: "Revisar", icon: AlertTriangle },
  AGREGADO: { box: "bg-info-soft text-info", label: "Agregado", icon: Layers },
  CAPTURADO: { box: "bg-neutral-soft text-neutral", label: "Capturado", icon: Clock },
};

export function StatusBadge({ control }: { control: string }) {
  const s = map[control] ?? map.CAPTURADO;
  const Icon = s.icon;
  return (
    <span
      className={cn(
        "inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium",
        s.box,
      )}
    >
      <Icon className="size-3.5" aria-hidden="true" />
      {s.label}
    </span>
  );
}
