import { useState } from "react";
import { useQuery, keepPreviousData } from "@tanstack/react-query";
import { api } from "@/lib";
import type { SeguimientoMeta, MesPOA } from "@/lib";
import { PageHeader } from "@/components/ui/PageHeader";
import { DataTable, type Column } from "@/components/ui/DataTable";
import { SemaforoBadge } from "@/components/ui/SemaforoBadge";
import { Select } from "@/components/ui/Select";
import { Pagination } from "@/components/ui/Pagination";
import { errMsg } from "@/utils/errors";

const MESES: MesPOA[] = Array.from({ length: 18 }, (_, i) => `M${String(i + 1).padStart(2, "0")}` as MesPOA);
const PAGE_SIZE = 15;

export function MetasPage() {
  const [periodo, setPeriodo] = useState<string>("");
  const [eje, setEje] = useState<string>("");
  const [page, setPage] = useState(1);

  const ejes = useQuery({ queryKey: ["ejes"], queryFn: () => api.listarEjes() });
  const q = useQuery({
    queryKey: ["seguimientoMetas", periodo, eje],
    queryFn: () => api.seguimientoMetas({ periodo: (periodo || undefined) as MesPOA | undefined, eje: eje || undefined }),
    placeholderData: keepPreviousData,
  });

  const all = q.data ?? [];
  const totalPages = Math.max(Math.ceil(all.length / PAGE_SIZE), 1);
  const pageActual = Math.min(page, totalPages);
  const rows = all.slice((pageActual - 1) * PAGE_SIZE, pageActual * PAGE_SIZE);

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
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <Select label="" aria-label="Filtrar por eje" value={eje} onChange={(e) => { setEje(e.target.value); setPage(1); }} className="w-48">
              <option value="">Todos los ejes</option>
              {(ejes.data ?? []).map((x) => (
                <option key={x.id_eje} value={x.id_eje}>{x.clave_eje_corto ?? x.id_eje}</option>
              ))}
            </Select>
            <Select label="" aria-label="Filtrar por periodo" value={periodo} onChange={(e) => { setPeriodo(e.target.value); setPage(1); }} className="w-36">
              <option value="">Todos los meses</option>
              {MESES.map((m) => (
                <option key={m} value={m}>{m}</option>
              ))}
            </Select>
          </div>
        }
      />
      <DataTable
        columns={columns}
        rows={rows}
        rowKey={(r) => `${r.id_actividad}-${r.mes}`}
        loading={q.isLoading}
        error={q.isError ? errMsg(q.error) : null}
        onRetry={() => q.refetch()}
        emptyMessage="No hay metas cargadas para tu ámbito."
      />
      <Pagination page={pageActual} totalPages={totalPages} total={all.length} pageSize={PAGE_SIZE} onPage={setPage} disabled={q.isFetching} />
    </div>
  );
}
