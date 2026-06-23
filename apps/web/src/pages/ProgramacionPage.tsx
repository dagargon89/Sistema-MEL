import { useState, type FormEvent } from "react";
import { useQuery, useMutation, useQueryClient, keepPreviousData } from "@tanstack/react-query";
import { CalendarPlus } from "lucide-react";
import { api } from "@/lib";
import type { EventoProgramado, EventoProgramadoInput, TipoProgramacion } from "@/lib";
import { PageHeader } from "@/components/ui/PageHeader";
import { DataTable, type Column } from "@/components/ui/DataTable";
import { Button } from "@/components/ui/Button";
import { Modal } from "@/components/ui/Modal";
import { Select } from "@/components/ui/Select";
import { TextField } from "@/components/ui/TextField";
import { Pagination, metaToPagination } from "@/components/ui/Pagination";
import { toast } from "@/store/toast";
import { errMsg } from "@/utils/errors";

const TIPOS: TipoProgramacion[] = ["SESION_UNICA", "MULTI_SESION_PROGRAMADA", "PROCESO_CONTINUO"];

export function ProgramacionPage() {
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const [f, setF] = useState({ id_actividad: "", tipo_programacion: "SESION_UNICA" as TipoProgramacion, fecha_inicio: "", fecha_finalizacion: "" });
  const [page, setPage] = useState(1);

  const eventos = useQuery({
    queryKey: ["eventos", page],
    queryFn: () => api.listarEventos({ page }),
    placeholderData: keepPreviousData,
  });
  const acts = useQuery({ queryKey: ["actividades", "all"], queryFn: () => api.listarActividades({ limit: 100 }) });

  const crear = useMutation({
    mutationFn: () => {
      const input: EventoProgramadoInput = {
        id_actividad: f.id_actividad,
        tipo_programacion: f.tipo_programacion,
        fecha_inicio: f.fecha_inicio,
        fecha_finalizacion: f.fecha_finalizacion || f.fecha_inicio,
      };
      return api.crearEvento(input);
    },
    onSuccess: () => {
      toast.success("Evento programado creado.");
      setOpen(false);
      setF({ id_actividad: "", tipo_programacion: "SESION_UNICA", fecha_inicio: "", fecha_finalizacion: "" });
      qc.invalidateQueries({ queryKey: ["eventos"] });
      qc.invalidateQueries({ queryKey: ["tablero"] });
    },
    onError: (e) => toast.error(errMsg(e)),
  });

  function submit(e: FormEvent) {
    e.preventDefault();
    crear.mutate();
  }

  const columns: Column<EventoProgramado>[] = [
    { key: "id_evento_programado", header: "EVP", render: (r) => <span className="num">{r.id_evento_programado}</span> },
    { key: "id_actividad", header: "Actividad", render: (r) => <span className="num">{r.id_actividad}</span> },
    { key: "tipo_programacion", header: "Tipo" },
    { key: "fecha_inicio", header: "Inicio" },
    { key: "fecha_finalizacion", header: "Fin" },
    { key: "estatus", header: "Estatus" },
  ];

  return (
    <div>
      <PageHeader
        title="Programación"
        subtitle="Calendario de eventos planeados. Un evento multisesión exige un proceso (RN-030)."
        actions={
          <Button icon={<CalendarPlus className="size-4" />} onClick={() => setOpen(true)}>
            Programar evento
          </Button>
        }
      />
      <DataTable
        columns={columns}
        rows={eventos.data?.data ?? []}
        rowKey={(r) => r.id_evento_programado}
        loading={eventos.isLoading}
        error={eventos.isError ? errMsg(eventos.error) : null}
        onRetry={() => eventos.refetch()}
        emptyMessage="Sin eventos programados en tu ámbito."
      />
      <Pagination {...metaToPagination(eventos.data?.meta)} onPage={setPage} disabled={eventos.isFetching} />

      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title="Programar evento"
        footer={
          <>
            <Button variant="ghost" onClick={() => setOpen(false)}>
              Cancelar
            </Button>
            <Button form="form-evento" type="submit" loading={crear.isPending}>
              Crear
            </Button>
          </>
        }
      >
        <form id="form-evento" onSubmit={submit} className="grid gap-3">
          <Select label="Actividad" required value={f.id_actividad} onChange={(e) => setF({ ...f, id_actividad: e.target.value })}>
            <option value="">Selecciona…</option>
            {(acts.data?.data ?? []).map((a) => (
              <option key={a.id_actividad} value={a.id_actividad}>
                {a.id_actividad} · {a.nombre} ({a.tipo_registro})
              </option>
            ))}
          </Select>
          <Select label="Tipo de programación" value={f.tipo_programacion} onChange={(e) => setF({ ...f, tipo_programacion: e.target.value as TipoProgramacion })}>
            {TIPOS.map((t) => (
              <option key={t} value={t}>
                {t}
              </option>
            ))}
          </Select>
          <div className="grid grid-cols-2 gap-3">
            <TextField label="Fecha inicio" type="date" required value={f.fecha_inicio} onChange={(e) => setF({ ...f, fecha_inicio: e.target.value })} />
            <TextField label="Fecha fin" type="date" value={f.fecha_finalizacion} onChange={(e) => setF({ ...f, fecha_finalizacion: e.target.value })} />
          </div>
        </form>
      </Modal>
    </div>
  );
}
