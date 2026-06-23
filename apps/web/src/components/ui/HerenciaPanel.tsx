import { Lock } from "lucide-react";
import type { HerenciaEstrategica } from "@/lib";

// Herencia estratégica en SOLO LECTURA (RF-CAT-011, doc 09 §1).
const filas: { key: keyof HerenciaEstrategica; label: string }[] = [
  { key: "eje", label: "Eje" },
  { key: "linea", label: "Línea" },
  { key: "componente", label: "Componente" },
  { key: "institucion", label: "Institución" },
];

export function HerenciaPanel({ herencia }: { herencia: HerenciaEstrategica }) {
  return (
    <div className="rounded-md border border-border bg-surface p-4">
      <div className="mb-3 flex items-center gap-2 text-sm font-semibold text-text">
        <Lock className="size-4 text-text-muted" aria-hidden="true" />
        Herencia estratégica
        <span className="text-xs font-normal text-text-muted">(solo lectura)</span>
      </div>
      <dl className="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
        {filas.map((f) => (
          <div key={f.key} className="flex flex-col">
            <dt className="text-xs uppercase tracking-wide text-text-muted">{f.label}</dt>
            <dd className="text-sm text-text">{herencia[f.key] || "—"}</dd>
          </div>
        ))}
      </dl>
    </div>
  );
}
