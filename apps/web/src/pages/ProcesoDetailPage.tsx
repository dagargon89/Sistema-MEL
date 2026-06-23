import type { ReactNode } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { ArrowLeft } from "lucide-react";
import { api } from "@/lib";
import { PageHeader } from "@/components/ui/PageHeader";
import { ErrorState } from "@/components/ui/ErrorState";
import { EmptyState } from "@/components/ui/EmptyState";
import { Spinner } from "@/components/ui/Spinner";
import { errMsg } from "@/utils/errors";

export function ProcesoDetailPage() {
  const id = Number(useParams().id);
  const navigate = useNavigate();
  const q = useQuery({ queryKey: ["procesos"], queryFn: () => api.listarProcesos({ limit: 100 }) });

  const proceso = q.data?.data.find((p) => p.id_proceso === id);

  return (
    <div>
      <button
        onClick={() => navigate("/programacion")}
        className="mb-3 inline-flex items-center gap-1 text-sm text-primary hover:underline"
      >
        <ArrowLeft className="size-4" aria-hidden="true" /> Programación
      </button>
      <PageHeader title={`Proceso #${id}`} subtitle="Agrupa eventos multisesión de una misma iniciativa (RN-030)." />

      {q.isLoading ? (
        <Spinner label="Cargando proceso…" />
      ) : q.isError ? (
        <ErrorState message={errMsg(q.error)} onRetry={() => q.refetch()} />
      ) : !proceso ? (
        <EmptyState title="Proceso no encontrado" message="No existe en tu ámbito o fue cancelado." />
      ) : (
        <div className="rounded-md border border-border bg-surface p-4">
          <dl className="grid grid-cols-2 gap-x-6 gap-y-3">
            <Item label="Nombre">{proceso.nombre}</Item>
            <Item label="Tipo">{proceso.tipo_programacion}</Item>
            <Item label="Actividad">{proceso.id_actividad}</Item>
            <Item label="Estatus">{proceso.estatus}</Item>
            <Item label="Inicio">{proceso.fecha_inicio ?? "—"}</Item>
            <Item label="Fin">{proceso.fecha_fin ?? "—"}</Item>
            <Item label="Sesiones programadas">{proceso.total_sesiones_programadas ?? "—"}</Item>
            <Item label="Responsable">{proceso.responsable ?? "—"}</Item>
          </dl>
        </div>
      )}
    </div>
  );
}

function Item({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div>
      <dt className="text-xs uppercase tracking-wide text-text-muted">{label}</dt>
      <dd className="text-sm text-text">{children}</dd>
    </div>
  );
}
