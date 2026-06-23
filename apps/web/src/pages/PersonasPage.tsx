import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { api } from "@/lib";
import type { Persona } from "@/lib";
import { PageHeader } from "@/components/ui/PageHeader";
import { DataTable, type Column } from "@/components/ui/DataTable";
import { StatusBadge } from "@/components/ui/StatusBadge";
import { Select } from "@/components/ui/Select";
import { errMsg } from "@/utils/errors";

export function PersonasPage() {
  const [control, setControl] = useState<string>("");
  const q = useQuery({
    queryKey: ["personas", control],
    queryFn: () => api.listarPersonas({ control: (control || undefined) as Persona["control_registro"] | undefined }),
  });

  const columns: Column<Persona>[] = [
    { key: "id_persona", header: "ID", render: (r) => <span className="num">{r.id_persona}</span> },
    { key: "nombre_completo", header: "Nombre", render: (r) => r.nombre_completo ?? "—" },
    { key: "telefono", header: "Teléfono", render: (r) => <span className="num">{r.telefono ?? "—"}</span> },
    { key: "colonia", header: "Colonia", render: (r) => r.colonia ?? "—" },
    { key: "total_participaciones", header: "Particip.", align: "right", render: (r) => <span className="num">{r.total_participaciones}</span> },
    { key: "control_registro", header: "Control", render: (r) => <StatusBadge control={r.control_registro} /> },
  ];

  return (
    <div>
      <PageHeader
        title="Personas (consolidado)"
        subtitle="Tabla derivada por deduplicación; no tiene alta manual (RF-PART-044). Solo las OK cuentan como beneficiarios."
        actions={
          <Select label="" aria-label="Filtrar por control" value={control} onChange={(e) => setControl(e.target.value)} className="w-40">
            <option value="">Todas</option>
            <option value="OK">OK</option>
            <option value="REVISAR">Revisar</option>
          </Select>
        }
      />
      <DataTable
        columns={columns}
        rows={q.data?.data ?? []}
        rowKey={(r) => r.id_persona}
        loading={q.isLoading}
        error={q.isError ? errMsg(q.error) : null}
        onRetry={() => q.refetch()}
        emptyMessage="Aún no hay personas consolidadas."
      />
    </div>
  );
}
