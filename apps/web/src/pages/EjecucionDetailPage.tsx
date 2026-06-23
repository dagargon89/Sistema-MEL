import { useState, type ReactNode } from "react";
import { useParams, useNavigate, Link } from "react-router-dom";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Users, ShieldCheck, ArrowLeft } from "lucide-react";
import { api } from "@/lib";
import type { ControlRegistro } from "@/lib";
import { PageHeader } from "@/components/ui/PageHeader";
import { Button } from "@/components/ui/Button";
import { Modal } from "@/components/ui/Modal";
import { Select } from "@/components/ui/Select";
import { Textarea } from "@/components/ui/Textarea";
import { StatusBadge } from "@/components/ui/StatusBadge";
import { ErrorState } from "@/components/ui/ErrorState";
import { Spinner } from "@/components/ui/Spinner";
import { toast } from "@/store/toast";
import { errMsg } from "@/utils/errors";

const DESTINOS: ControlRegistro[] = ["INCOMPLETO", "REVISAR", "OK"];

function Campo({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div>
      <dt className="text-xs uppercase tracking-wide text-text-muted">{label}</dt>
      <dd className="text-sm text-text">{children ?? "—"}</dd>
    </div>
  );
}

export function EjecucionDetailPage() {
  const id = Number(useParams().id);
  const navigate = useNavigate();
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const [destino, setDestino] = useState<ControlRegistro>("OK");
  const [detalle, setDetalle] = useState("");

  const q = useQuery({ queryKey: ["ejecucion", id], queryFn: () => api.obtenerEjecucion(id) });

  const validar = useMutation({
    mutationFn: () => api.validarEjecucion(id, { control_registro: destino, detalle: detalle || null }),
    onSuccess: () => {
      toast.success(`Estado cambiado a ${destino}.`);
      setOpen(false);
      setDetalle("");
      qc.invalidateQueries({ queryKey: ["ejecucion", id] });
      qc.invalidateQueries({ queryKey: ["ejecuciones"] });
      qc.invalidateQueries({ queryKey: ["tablero"] });
    },
    onError: (e) => toast.error(errMsg(e)),
  });

  if (q.isLoading) return <Spinner label="Cargando ejecución…" />;
  if (q.isError || !q.data) return <ErrorState message={errMsg(q.error)} onRetry={() => q.refetch()} />;

  const ej = q.data;

  return (
    <div>
      <button
        onClick={() => navigate("/ejecuciones")}
        className="mb-3 inline-flex items-center gap-1 text-sm text-primary hover:underline"
      >
        <ArrowLeft className="size-4" aria-hidden="true" /> Ejecuciones
      </button>

      <PageHeader
        title={`Ejecución #${ej.id_ejecucion}`}
        subtitle={`Evento programado EVP ${ej.id_evento_programado}`}
        actions={
          <>
            <StatusBadge control={ej.control_registro} />
            <Button variant="secondary" icon={<ShieldCheck className="size-4" />} onClick={() => setOpen(true)}>
              Cambiar estado
            </Button>
          </>
        }
      />

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="lg:col-span-2 rounded-md border border-border bg-surface p-4">
          <dl className="grid grid-cols-2 gap-x-6 gap-y-3">
            <Campo label="Fecha real">{ej.fecha_ejecucion_real}</Campo>
            <Campo label="Estatus ejecución">{ej.estatus_ejecucion}</Campo>
            <Campo label="Tipo participación">{ej.tipo_registro_participacion}</Campo>
            <Campo label="Participantes">{ej.total_participantes}</Campo>
            <Campo label="Lugar real">{ej.lugar_real}</Campo>
            <Campo label="Responsable">{ej.responsable_real}</Campo>
          </dl>
          <div className="mt-4">
            <Campo label="Resumen narrativo">{ej.resumen_narrativo}</Campo>
          </div>
          <div className="mt-4">
            <Campo label="Evidencia">
              {ej.evidencia_url ? (
                <a href={ej.evidencia_url} target="_blank" rel="noreferrer" className="text-primary underline">
                  {ej.nombre_archivo_evidencia ?? ej.evidencia_url}
                </a>
              ) : (
                "Sin evidencia"
              )}
            </Campo>
          </div>
        </div>

        <div className="rounded-md border border-border bg-surface p-4">
          <h3 className="mb-2 text-sm font-semibold text-text">Lista de asistencia</h3>
          <p className="mb-3 text-xs text-text-muted">
            Cada participación se deduplica en el servidor para contar beneficiarios únicos.
          </p>
          <Link to={`/ejecuciones/${ej.id_ejecucion}/participaciones`}>
            <Button icon={<Users className="size-4" />} className="w-full">
              Ver participaciones
            </Button>
          </Link>
        </div>
      </div>

      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title="Cambiar estado de la ejecución"
        footer={
          <>
            <Button variant="ghost" onClick={() => setOpen(false)}>
              Cancelar
            </Button>
            <Button loading={validar.isPending} onClick={() => validar.mutate()}>
              Aplicar
            </Button>
          </>
        }
      >
        <p className="mb-3 text-sm text-text-muted">
          La transición REVISAR → OK es exclusiva de coordinación; el servidor rechaza las transiciones inválidas.
        </p>
        <Select label="Nuevo estado" value={destino} onChange={(e) => setDestino(e.target.value as ControlRegistro)}>
          {DESTINOS.map((d) => (
            <option key={d} value={d}>
              {d}
            </option>
          ))}
        </Select>
        <Textarea
          label="Detalle (opcional)"
          className="mt-3"
          value={detalle}
          onChange={(e) => setDetalle(e.target.value)}
          placeholder="Motivo o nota de la validación…"
        />
      </Modal>
    </div>
  );
}
