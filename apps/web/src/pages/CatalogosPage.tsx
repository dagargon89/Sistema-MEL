import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { api } from "@/lib";
import type { ActividadConHerencia, TipoRegistro } from "@/lib";
import { PageHeader } from "@/components/ui/PageHeader";
import { DataTable, type Column } from "@/components/ui/DataTable";
import { Select } from "@/components/ui/Select";
import { cn } from "@/components/ui/cn";
import { errMsg } from "@/utils/errors";

const tipoStyle: Record<TipoRegistro, string> = {
  P: "bg-primary-soft text-primary",
  E: "bg-info-soft text-info",
  R: "bg-neutral-soft text-neutral",
};

export function CatalogosPage() {
  const [tipo, setTipo] = useState<string>("");
  const q = useQuery({
    queryKey: ["catalogos", "actividades", tipo],
    queryFn: () => api.listarActividades({ tipo: (tipo || undefined) as TipoRegistro | undefined, limit: 100 }),
  });

  const columns: Column<ActividadConHerencia>[] = [
    { key: "id_actividad", header: "ID", render: (r) => <span className="num">{r.id_actividad}</span> },
    { key: "nombre", header: "Actividad" },
    {
      key: "tipo_registro",
      header: "Tipo",
      render: (r) => (
        <span className={cn("inline-flex rounded-full px-2 py-0.5 text-xs font-semibold", tipoStyle[r.tipo_registro])}>
          {r.tipo_registro}
        </span>
      ),
    },
    { key: "caso_excepcional", header: "Caso", render: (r) => r.caso_excepcional ?? "—" },
    { key: "eje", header: "Eje", render: (r) => r.herencia.eje },
    { key: "institucion", header: "Institución", render: (r) => r.herencia.institucion },
  ];

  return (
    <div>
      <PageHeader
        title="Catálogos · Actividades"
        subtitle="La verdad estratégica vive aquí y se hereda (eje→línea→componente→institución). Escritura: coordinación/admin."
        actions={
          <Select label="" aria-label="Filtrar por tipo" value={tipo} onChange={(e) => setTipo(e.target.value)} className="w-44">
            <option value="">Todos los tipos</option>
            <option value="P">P · Participación</option>
            <option value="E">E · Entregable</option>
            <option value="R">R · Resultado</option>
          </Select>
        }
      />
      <DataTable
        columns={columns}
        rows={q.data?.data ?? []}
        rowKey={(r) => r.id_actividad}
        loading={q.isLoading}
        error={q.isError ? errMsg(q.error) : null}
        onRetry={() => q.refetch()}
        emptyMessage="No hay actividades para este filtro en tu ámbito."
      />
    </div>
  );
}
