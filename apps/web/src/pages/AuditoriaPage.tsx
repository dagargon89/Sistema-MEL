import { useState } from "react";
import { useQuery, keepPreviousData } from "@tanstack/react-query";
import { api } from "@/lib";
import type { Auditoria } from "@/lib";
import { PageHeader } from "@/components/ui/PageHeader";
import { DataTable, type Column } from "@/components/ui/DataTable";
import { Select } from "@/components/ui/Select";
import { Pagination, metaToPagination } from "@/components/ui/Pagination";
import { errMsg } from "@/utils/errors";

const ENTIDADES = ["ejecuciones", "participaciones", "actividades", "productos_entregables", "metas", "solicitudes"];

export function AuditoriaPage() {
  const [entidad, setEntidad] = useState<string>("");
  const [page, setPage] = useState(1);
  const q = useQuery({
    queryKey: ["auditoria", entidad, page],
    queryFn: () => api.listarAuditoria({ entidad: entidad || undefined, page }),
    placeholderData: keepPreviousData,
  });

  const columns: Column<Auditoria>[] = [
    { key: "fecha_hora", header: "Fecha", render: (r) => <span className="num">{r.fecha_hora}</span> },
    { key: "id_usuario", header: "Usuario", render: (r) => <span className="num">{r.id_usuario ?? "sistema"}</span> },
    { key: "entidad", header: "Entidad" },
    { key: "id_registro", header: "Registro", render: (r) => <span className="num">{r.id_registro}</span> },
    { key: "accion", header: "Acción" },
  ];

  return (
    <div>
      <PageHeader
        title="Auditoría"
        subtitle="Bitácora append-only de toda escritura: quién, qué, cuándo, valor antes/después (RF-GOB-112)."
        actions={
          <Select label="" aria-label="Filtrar por entidad" value={entidad} onChange={(e) => { setEntidad(e.target.value); setPage(1); }} className="w-48">
            <option value="">Todas las entidades</option>
            {ENTIDADES.map((x) => (
              <option key={x} value={x}>{x}</option>
            ))}
          </Select>
        }
      />
      <DataTable
        columns={columns}
        rows={q.data?.data ?? []}
        rowKey={(r) => r.id_evento}
        loading={q.isLoading}
        error={q.isError ? errMsg(q.error) : null}
        onRetry={() => q.refetch()}
        emptyMessage="Sin eventos de auditoría aún."
      />
      <Pagination {...metaToPagination(q.data?.meta)} onPage={setPage} disabled={q.isFetching} />
    </div>
  );
}
