import { useQuery } from "@tanstack/react-query";
import { Users, ClipboardCheck, CalendarDays, Layers, Activity, UserCheck, Percent } from "lucide-react";
import { api } from "@/lib";
import { KpiCard } from "@/components/ui/KpiCard";
import { PageHeader } from "@/components/ui/PageHeader";
import { ErrorState } from "@/components/ui/ErrorState";
import { SemaforoBadge } from "@/components/ui/SemaforoBadge";
import { useSession } from "@/store/session";
import { errMsg } from "@/utils/errors";
import type { Semaforo } from "@/lib";

const SEMAFOROS: Semaforo[] = ["VERDE", "AMARILLO", "ROJO", "CORTE_AL_CIERRE", "SIN_META", "FASE_3"];

export function TableroPage() {
  const { user } = useSession();
  const tablero = useQuery({ queryKey: ["tablero", "ejecutivo"], queryFn: () => api.tablero("ejecutivo") });
  const metas = useQuery({ queryKey: ["seguimientoMetas"], queryFn: () => api.seguimientoMetas() });

  const d = tablero.data;
  const loading = tablero.isLoading;

  const conteoSemaforo = (s: Semaforo) => (metas.data ?? []).filter((m) => m.semaforo === s).length;

  return (
    <div>
      <PageHeader
        title="Tablero"
        subtitle="KPIs sobre datos reales (control_registro = OK). Ningún número proviene de filas-plantilla."
      />

      {tablero.isError ? (
        <ErrorState message={errMsg(tablero.error)} onRetry={() => tablero.refetch()} />
      ) : (
        <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
          <KpiCard
            label="Beneficiarios únicos"
            value={d?.beneficiarios_unicos ?? 0}
            hint="Personas OK distintas (deduplicadas)"
            icon={<Users className="size-5" />}
            loading={loading}
          />
          <KpiCard
            label="Participaciones nominales"
            value={d?.participaciones_nominales ?? 0}
            icon={<UserCheck className="size-5" />}
            loading={loading}
          />
          <KpiCard
            label="Participaciones agregadas"
            value={d?.participaciones_agregadas ?? 0}
            icon={<Layers className="size-5" />}
            loading={loading}
          />
          <KpiCard
            label="Cobertura total"
            value={d?.cobertura_total ?? 0}
            hint="Nominales + agregadas"
            icon={<Activity className="size-5" />}
            loading={loading}
          />
          <KpiCard
            label="Eventos programados"
            value={d?.eventos_programados ?? 0}
            icon={<CalendarDays className="size-5" />}
            loading={loading}
          />
          <KpiCard
            label="Ejecuciones reales"
            value={d?.ejecuciones ?? 0}
            icon={<ClipboardCheck className="size-5" />}
            loading={loading}
          />
          <KpiCard
            label="Cumplimiento ejecución"
            value={d ? `${Math.round(d.cumplimiento_ejecucion * 100)}%` : "—"}
            hint="Ejecuciones reales / programadas"
            icon={<Percent className="size-5" />}
            loading={loading}
          />
        </div>
      )}

      <div className="mt-8">
        <h2 className="mb-3 text-lg font-semibold text-text">Semáforo de metas</h2>
        {metas.isError ? (
          <ErrorState message={errMsg(metas.error)} onRetry={() => metas.refetch()} />
        ) : (
          <div className="flex flex-wrap gap-2">
            {SEMAFOROS.map((s) => (
              <div key={s} className="flex items-center gap-2 rounded-md border border-border bg-surface px-3 py-2">
                <SemaforoBadge semaforo={s} />
                <span className="num text-sm font-semibold text-text">{conteoSemaforo(s)}</span>
              </div>
            ))}
          </div>
        )}
        <p className="mt-3 text-xs text-text-muted">
          Vista en vivo · sesión de {user?.nombre}. Los casos C/D aparecen como “corte al cierre”, no en rojo (QA6).
        </p>
      </div>
    </div>
  );
}
