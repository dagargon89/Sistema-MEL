import { useQuery } from "@tanstack/react-query";
import { api } from "@/lib";
import type { SeguimientoMeta } from "@/lib";
import { PageHeader } from "@/components/ui/PageHeader";
import { DataTable, type Column } from "@/components/ui/DataTable";
import { SemaforoBadge } from "@/components/ui/SemaforoBadge";
import { errMsg } from "@/utils/errors";

export function MetasPage() {
  const q = useQuery({ queryKey: ["seguimientoMetas"], queryFn: () => api.seguimientoMetas() });

  const columns: Column<SeguimientoMeta>[] = [
    { key: "id_actividad", header: "Actividad", render: (r) => <span className="num">{r.id_actividad}</span> },
    { key: "tipo_registro", header: "Tipo", render: (r) => r.tipo_registro ?? "—" },
    { key: "mes", header: "Mes" },
    { key: "meta_mes", header: "Meta", align: "right", render: (r) => <span className="num">{r.meta_mes}</span> },
    { key: "avance_mes", header: "Avance", align: "right", render: (r) => <span className="num">{r.avance_mes}</span> },
    {
      key: "porcentaje",
      header: "%",
      align: "right",
      render: (r) => <span className="num">{r.porcentaje == null ? "—" : `${r.porcentaje}%`}</span>,
    },
    { key: "semaforo", header: "Semáforo", render: (r) => <SemaforoBadge semaforo={r.semaforo} /> },
  ];

  return (
    <div>
      <PageHeader
        title="Metas y seguimiento"
        subtitle="Avance real (solo control=OK) vs. meta mensual. Casos C/D = corte al cierre, no rojo (QA6)."
      />
      <DataTable
        columns={columns}
        rows={q.data ?? []}
        rowKey={(r) => `${r.id_actividad}-${r.mes}`}
        loading={q.isLoading}
        error={q.isError ? errMsg(q.error) : null}
        onRetry={() => q.refetch()}
        emptyMessage="No hay metas cargadas para tu ámbito."
      />
    </div>
  );
}
