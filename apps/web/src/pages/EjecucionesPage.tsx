import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { api } from "@/lib";
import type { Ejecucion, ControlRegistro } from "@/lib";
import { PageHeader } from "@/components/ui/PageHeader";
import { DataTable, type Column } from "@/components/ui/DataTable";
import { StatusBadge } from "@/components/ui/StatusBadge";
import { Select } from "@/components/ui/Select";
import { errMsg } from "@/utils/errors";

const CONTROLES: ControlRegistro[] = ["CAPTURADO", "INCOMPLETO", "REVISAR", "OK", "AGREGADO"];

export function EjecucionesPage() {
  const navigate = useNavigate();
  const [control, setControl] = useState<string>("");

  const q = useQuery({
    queryKey: ["ejecuciones", control],
    queryFn: () => api.listarEjecuciones({ control: (control || undefined) as ControlRegistro | undefined }),
  });

  const columns: Column<Ejecucion>[] = [
    { key: "id_ejecucion", header: "ID", render: (r) => <span className="num">#{r.id_ejecucion}</span> },
    { key: "id_evento_programado", header: "Evento", render: (r) => <span className="num">EVP {r.id_evento_programado}</span> },
    { key: "fecha_ejecucion_real", header: "Fecha real", render: (r) => r.fecha_ejecucion_real ?? "—" },
    { key: "estatus_ejecucion", header: "Estatus", render: (r) => r.estatus_ejecucion ?? "—" },
    { key: "tipo_registro_participacion", header: "Tipo" },
    { key: "control_registro", header: "Control", render: (r) => <StatusBadge control={r.control_registro} /> },
  ];

  return (
    <div>
      <PageHeader
        title="Ejecuciones"
        subtitle="Lo que realmente ocurrió. La máquina de estados (control_registro) la valida el servidor."
        actions={
          <Select
            label=""
            aria-label="Filtrar por control"
            value={control}
            onChange={(e) => setControl(e.target.value)}
            className="w-44"
          >
            <option value="">Todos los estados</option>
            {CONTROLES.map((c) => (
              <option key={c} value={c}>
                {c}
              </option>
            ))}
          </Select>
        }
      />
      <DataTable
        columns={columns}
        rows={q.data?.data ?? []}
        rowKey={(r) => r.id_ejecucion}
        loading={q.isLoading}
        error={q.isError ? errMsg(q.error) : null}
        onRetry={() => q.refetch()}
        emptyMessage="No hay ejecuciones en este filtro. Programa un evento y regístralo."
        onRowClick={(r) => navigate(`/ejecuciones/${r.id_ejecucion}`)}
      />
    </div>
  );
}
