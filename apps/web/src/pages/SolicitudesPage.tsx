import { useState, type FormEvent } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Plus } from "lucide-react";
import { api } from "@/lib";
import type { Solicitud, SolicitudInput, TipoSolicitud, NivelCriticidad, EstadoSolicitud } from "@/lib";
import { useSession } from "@/store/session";
import { PageHeader } from "@/components/ui/PageHeader";
import { DataTable, type Column } from "@/components/ui/DataTable";
import { Button } from "@/components/ui/Button";
import { Modal } from "@/components/ui/Modal";
import { Select } from "@/components/ui/Select";
import { Textarea } from "@/components/ui/Textarea";
import { toast } from "@/store/toast";
import { errMsg } from "@/utils/errors";

const ESTADOS: EstadoSolicitud[] = ["en_revision", "en_proceso", "resuelta", "descartada"];

export function SolicitudesPage() {
  const { user } = useSession();
  const esCoord = user?.rol === "coordinacion";
  const qc = useQueryClient();
  const [crearOpen, setCrearOpen] = useState(false);
  const [resol, setResol] = useState<Solicitud | null>(null);
  const [nuevoEstado, setNuevoEstado] = useState<EstadoSolicitud>("en_proceso");
  const [f, setF] = useState({ descripcion: "", tipo_solicitud: "correccion" as TipoSolicitud, nivel_criticidad: "MEDIA" as NivelCriticidad, entidad_afectada: "" });

  const q = useQuery({ queryKey: ["solicitudes"], queryFn: () => api.listarSolicitudes({ limit: 50 }) });

  const crear = useMutation({
    mutationFn: () => {
      const input: SolicitudInput = {
        descripcion: f.descripcion.trim(),
        tipo_solicitud: f.tipo_solicitud,
        nivel_criticidad: f.nivel_criticidad,
        entidad_afectada: f.entidad_afectada.trim() || null,
      };
      return api.crearSolicitud(input);
    },
    onSuccess: () => {
      toast.success("Solicitud registrada.");
      setCrearOpen(false);
      setF({ descripcion: "", tipo_solicitud: "correccion", nivel_criticidad: "MEDIA", entidad_afectada: "" });
      qc.invalidateQueries({ queryKey: ["solicitudes"] });
    },
    onError: (e) => toast.error(errMsg(e)),
  });

  const resolver = useMutation({
    mutationFn: () => api.resolverSolicitud(resol!.id_solicitud, { estado: nuevoEstado }),
    onSuccess: () => {
      toast.success("Solicitud actualizada.");
      setResol(null);
      qc.invalidateQueries({ queryKey: ["solicitudes"] });
    },
    onError: (e) => toast.error(errMsg(e)),
  });

  const columns: Column<Solicitud>[] = [
    { key: "id_solicitud", header: "ID", render: (r) => <span className="num">#{r.id_solicitud}</span> },
    { key: "descripcion", header: "Descripción" },
    { key: "tipo_solicitud", header: "Tipo" },
    { key: "nivel_criticidad", header: "Criticidad" },
    { key: "estado", header: "Estado" },
    {
      key: "accion",
      header: "",
      render: (r) =>
        esCoord ? (
          <Button variant="ghost" onClick={() => { setResol(r); setNuevoEstado(r.estado); }}>
            Resolver
          </Button>
        ) : null,
    },
  ];

  return (
    <div>
      <PageHeader
        title="Solicitudes"
        subtitle="Cualquier usuario pide correcciones; coordinación las resuelve. Todo queda en auditoría."
        actions={
          <Button icon={<Plus className="size-4" />} onClick={() => setCrearOpen(true)}>
            Nueva solicitud
          </Button>
        }
      />
      <DataTable
        columns={columns}
        rows={q.data?.data ?? []}
        rowKey={(r) => r.id_solicitud}
        loading={q.isLoading}
        error={q.isError ? errMsg(q.error) : null}
        onRetry={() => q.refetch()}
        emptyMessage="No hay solicitudes registradas."
      />

      <Modal
        open={crearOpen}
        onClose={() => setCrearOpen(false)}
        title="Nueva solicitud"
        footer={
          <>
            <Button variant="ghost" onClick={() => setCrearOpen(false)}>Cancelar</Button>
            <Button form="form-sol" type="submit" loading={crear.isPending} disabled={!f.descripcion.trim()}>Registrar</Button>
          </>
        }
      >
        <form id="form-sol" onSubmit={(e: FormEvent) => { e.preventDefault(); crear.mutate(); }} className="grid gap-3">
          <Textarea label="Descripción" required value={f.descripcion} onChange={(e) => setF({ ...f, descripcion: e.target.value })} />
          <div className="grid grid-cols-2 gap-3">
            <Select label="Tipo" value={f.tipo_solicitud} onChange={(e) => setF({ ...f, tipo_solicitud: e.target.value as TipoSolicitud })}>
              <option value="correccion">Corrección</option>
              <option value="mejora">Mejora</option>
              <option value="ajuste">Ajuste</option>
            </Select>
            <Select label="Criticidad" value={f.nivel_criticidad} onChange={(e) => setF({ ...f, nivel_criticidad: e.target.value as NivelCriticidad })}>
              <option value="BAJA">Baja</option>
              <option value="MEDIA">Media</option>
              <option value="ALTA">Alta</option>
            </Select>
          </div>
          <TextFieldEntidad value={f.entidad_afectada} onChange={(v) => setF({ ...f, entidad_afectada: v })} />
        </form>
      </Modal>

      <Modal
        open={!!resol}
        onClose={() => setResol(null)}
        title={`Resolver solicitud #${resol?.id_solicitud ?? ""}`}
        footer={
          <>
            <Button variant="ghost" onClick={() => setResol(null)}>Cancelar</Button>
            <Button loading={resolver.isPending} onClick={() => resolver.mutate()}>Aplicar</Button>
          </>
        }
      >
        <Select label="Nuevo estado" value={nuevoEstado} onChange={(e) => setNuevoEstado(e.target.value as EstadoSolicitud)}>
          {ESTADOS.map((s) => (
            <option key={s} value={s}>{s}</option>
          ))}
        </Select>
      </Modal>
    </div>
  );
}

function TextFieldEntidad({ value, onChange }: { value: string; onChange: (v: string) => void }) {
  return (
    <label className="block text-sm font-medium text-text">
      Entidad afectada (opcional)
      <input
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder="p. ej. participaciones, ejecuciones…"
        className="mt-1 w-full rounded-md border border-border bg-bg px-3 py-2 text-sm text-text focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-soft"
      />
    </label>
  );
}
