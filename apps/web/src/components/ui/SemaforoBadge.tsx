import { CheckCircle2, AlertTriangle, AlertCircle, MinusCircle, CalendarClock, Hourglass, type LucideIcon } from "lucide-react";
import type { Semaforo } from "@/lib";
import { cn } from "./cn";

// Semáforo de metas (doc 05 §7). Casos C/D = CORTE_AL_CIERRE (no rojo). Tipo R = FASE_3.
const map: Record<Semaforo, { box: string; label: string; icon: LucideIcon }> = {
  VERDE: { box: "bg-success-soft text-success", label: "Verde", icon: CheckCircle2 },
  AMARILLO: { box: "bg-warning-soft text-warning", label: "Amarillo", icon: AlertTriangle },
  ROJO: { box: "bg-error-soft text-error", label: "Rojo", icon: AlertCircle },
  SIN_META: { box: "bg-neutral-soft text-neutral", label: "Sin meta", icon: MinusCircle },
  CORTE_AL_CIERRE: { box: "bg-info-soft text-info", label: "Corte al cierre", icon: CalendarClock },
  FASE_3: { box: "bg-neutral-soft text-neutral", label: "Fase 3", icon: Hourglass },
};

export function SemaforoBadge({ semaforo }: { semaforo: Semaforo }) {
  const s = map[semaforo] ?? map.SIN_META;
  const Icon = s.icon;
  return (
    <span className={cn("inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium", s.box)}>
      <Icon className="size-3.5" aria-hidden="true" />
      {s.label}
    </span>
  );
}
