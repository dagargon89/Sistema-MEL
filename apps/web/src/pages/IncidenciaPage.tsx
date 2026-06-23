import { PageHeader } from "@/components/ui/PageHeader";
import { ModuleNotice } from "@/components/ui/ModuleNotice";

export function IncidenciaPage() {
  return (
    <div>
      <PageHeader title="Incidencia" subtitle="Subsistema vertical de incidencia y abogacía." />
      <ModuleNotice
        fase="Fase 3 · Sprint 6"
        descripcion="La captura de incidencia se incorpora en la Fase 3, ampliando el contrato API de forma coordinada (SRS 01 + API 05). Entidades del modelo de datos (doc 03 §3.5):"
        items={[
          "Propuestas de incidencia (elegibles para reporte).",
          "Procesos de incidencia (persisten hasta cerrarse).",
          "Compromisos (FK obligatoria a un proceso — RN-004).",
          "Alianzas e hitos de incidencia (bitácora con evidencia).",
        ]}
      />
    </div>
  );
}
