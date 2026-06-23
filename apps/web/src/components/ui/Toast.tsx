import { CheckCircle2, AlertTriangle, Info, X } from "lucide-react";
import { useToasts, type ToastKind } from "@/store/toast";
import { cn } from "./cn";

const kindStyle: Record<ToastKind, { box: string; icon: typeof Info }> = {
  success: { box: "bg-success-soft text-success border-success/30", icon: CheckCircle2 },
  error: { box: "bg-error-soft text-error border-error/30", icon: AlertTriangle },
  info: { box: "bg-info-soft text-info border-info/30", icon: Info },
};

export function ToastViewport() {
  const { items, dismiss } = useToasts();
  return (
    <div
      aria-live="polite"
      role="status"
      className="pointer-events-none fixed bottom-4 right-4 z-50 flex w-full max-w-sm flex-col gap-2"
    >
      {items.map((t) => {
        const s = kindStyle[t.kind];
        const Icon = s.icon;
        return (
          <div
            key={t.id}
            className={cn(
              "pointer-events-auto flex items-start gap-2 rounded-md border px-3 py-2 text-sm shadow-md",
              s.box,
            )}
          >
            <Icon className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            <p className="flex-1 text-text">{t.message}</p>
            <button
              onClick={() => dismiss(t.id)}
              aria-label="Cerrar notificación"
              className="rounded p-0.5 text-text-muted hover:bg-bg/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            >
              <X className="size-4" aria-hidden="true" />
            </button>
          </div>
        );
      })}
    </div>
  );
}
