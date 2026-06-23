import { useQuery } from "@tanstack/react-query";
import { api } from "@/lib";
import type { Auditoria } from "@/lib";
import { PageHeader } from "@/components/ui/PageHeader";
import { DataTable, type Column } from "@/components/ui/DataTable";
import { errMsg } from "@/utils/errors";

export function AuditoriaPage() {
  const q = useQuery({ queryKey: ["auditoria"], queryFn: () => api.listarAuditoria({ limit: 50 }) });

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
    </div>
  );
}
