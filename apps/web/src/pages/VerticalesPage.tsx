import { PageHeader } from "@/components/ui/PageHeader";
import { ModuleNotice } from "@/components/ui/ModuleNotice";

export function VerticalesPage() {
  return (
    <div>
      <PageHeader title="Verticales" subtitle="Ocupación de shelter y sostenibilidad financiera." />
      <ModuleNotice
        fase="Fase 3 · Sprint 6"
        descripcion="Los subsistemas verticales se incorporan en la Fase 3. Sus cálculos viven en vistas, no en columnas (doc 03 §3.6):"
        items={[
          "Ocupación de shelter: % de ocupación calculado por mes y tipo de espacio.",
          "Sostenibilidad financiera: utilidad neta, recursos acumulados, % de avance anual y semáforo.",
          "Ambos se reportan sobre registros con control_registro = OK.",
        ]}
      />
    </div>
  );
}
