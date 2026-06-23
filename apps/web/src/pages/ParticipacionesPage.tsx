import { useState, type FormEvent } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { useQuery, useMutation, useQueryClient, keepPreviousData } from "@tanstack/react-query";
import { ArrowLeft, UserPlus, Search } from "lucide-react";
import { api } from "@/lib";
import type { Participacion, ParticipacionInput, Sexo } from "@/lib";
import { PageHeader } from "@/components/ui/PageHeader";
import { DataTable, type Column } from "@/components/ui/DataTable";
import { StatusBadge } from "@/components/ui/StatusBadge";
import { TextField } from "@/components/ui/TextField";
import { Select } from "@/components/ui/Select";
import { Button } from "@/components/ui/Button";
import { Pagination, metaToPagination } from "@/components/ui/Pagination";
import { toast } from "@/store/toast";
import { errMsg } from "@/utils/errors";

const vacio = {
  nombres: "",
  apellido_paterno: "",
  apellido_materno: "",
  anio_nacimiento: "",
  sexo: "F" as Sexo,
  telefono: "",
  correo: "",
  colonia_persona: "",
};

export function ParticipacionesPage() {
  const idEjecucion = Number(useParams().id);
  const navigate = useNavigate();
  const qc = useQueryClient();
  const [form, setForm] = useState({ ...vacio });
  const [busca, setBusca] = useState("");
  const [page, setPage] = useState(1);

  const q = useQuery({
    queryKey: ["participaciones", idEjecucion, busca, page],
    queryFn: () => api.listarParticipaciones(idEjecucion, { q: busca.trim() || undefined, page }),
    placeholderData: keepPreviousData,
  });

  const crear = useMutation({
    mutationFn: () => {
      const input: ParticipacionInput = {
        id_ejecucion: idEjecucion,
        nombres: form.nombres.trim(),
        apellido_paterno: form.apellido_paterno.trim(),
        apellido_materno: form.apellido_materno.trim() || null,
        anio_nacimiento: form.anio_nacimiento ? Number(form.anio_nacimiento) : null,
        sexo: form.sexo,
        telefono: form.telefono.trim(),
        correo: form.correo.trim() || null,
        colonia_persona: form.colonia_persona.trim(),
      };
      return api.crearParticipacion(input);
    },
    onSuccess: (r) => {
      const msg =
        r.alerta_duplicado === "DUPLICADO_EN_CAPTURA"
          ? `Posible duplicado → cola de revisión (${r.control_registro}).`
          : `Registrada. Persona ${r.id_persona ?? "—"} · ${r.control_registro}.`;
      toast[r.alerta_duplicado === "DUPLICADO_EN_CAPTURA" ? "info" : "success"](msg);
      setForm({ ...vacio });
      qc.invalidateQueries({ queryKey: ["participaciones", idEjecucion] });
      qc.invalidateQueries({ queryKey: ["duplicados"] });
      qc.invalidateQueries({ queryKey: ["tablero"] });
    },
    onError: (e) => toast.error(errMsg(e)),
  });

  function submit(e: FormEvent) {
    e.preventDefault();
    crear.mutate();
  }

  const set = (k: keyof typeof vacio) => (e: { target: { value: string } }) =>
    setForm((f) => ({ ...f, [k]: e.target.value }));

  const columns: Column<Participacion>[] = [
    {
      key: "nombre",
      header: "Nombre",
      render: (r) => `${r.nombres} ${r.apellido_paterno} ${r.apellido_materno ?? ""}`.trim(),
    },
    { key: "id_persona", header: "Persona", render: (r) => <span className="num">{r.id_persona ?? "—"}</span> },
    { key: "telefono", header: "Teléfono", render: (r) => <span className="num">{r.telefono}</span> },
    { key: "alerta_duplicado", header: "Alerta", render: (r) => (r.alerta_duplicado === "OK" ? "—" : "Duplicado en captura") },
    { key: "control_registro", header: "Control", render: (r) => <StatusBadge control={r.control_registro} /> },
  ];

  return (
    <div>
      <button
        onClick={() => navigate(`/ejecuciones/${idEjecucion}`)}
        className="mb-3 inline-flex items-center gap-1 text-sm text-primary hover:underline"
      >
        <ArrowLeft className="size-4" aria-hidden="true" /> Ejecución #{idEjecucion}
      </button>

      <PageHeader
        title="Participaciones"
        subtitle="El servidor calcula la clave de deduplicación y asigna la persona; el cliente nunca envía id_persona."
      />

      <div className="grid gap-6 lg:grid-cols-5">
        <form onSubmit={submit} className="lg:col-span-2 rounded-md border border-border bg-surface p-4">
          <h3 className="mb-3 text-sm font-semibold text-text">Registrar participación</h3>
          <div className="grid grid-cols-1 gap-3">
            <TextField label="Nombres" value={form.nombres} onChange={set("nombres")} required />
            <TextField label="Apellido paterno" value={form.apellido_paterno} onChange={set("apellido_paterno")} required />
            <TextField label="Apellido materno" value={form.apellido_materno} onChange={set("apellido_materno")} />
            <div className="grid grid-cols-2 gap-3">
              <TextField label="Año nac." type="number" value={form.anio_nacimiento} onChange={set("anio_nacimiento")} />
              <Select label="Sexo" value={form.sexo} onChange={set("sexo")} required>
                <option value="F">Femenino</option>
                <option value="M">Masculino</option>
                <option value="X">Otro</option>
              </Select>
            </div>
            <TextField label="Teléfono" value={form.telefono} onChange={set("telefono")} required />
            <TextField label="Correo" type="email" value={form.correo} onChange={set("correo")} />
            <TextField label="Colonia" value={form.colonia_persona} onChange={set("colonia_persona")} required />
          </div>
          <Button type="submit" loading={crear.isPending} icon={<UserPlus className="size-4" />} className="mt-4 w-full">
            Registrar
          </Button>
          <p className="mt-2 text-xs text-text-muted">
            Prueba: captura “Jose Perez” sin acento con el mismo teléfono de un “José Pérez” existente → se consolida (QA2).
          </p>
        </form>

        <div className="lg:col-span-3">
          <div className="relative mb-3">
            <Search className="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-text-muted" aria-hidden="true" />
            <input
              type="search"
              aria-label="Buscar participante"
              placeholder="Buscar por nombre, teléfono o persona…"
              value={busca}
              onChange={(e) => { setBusca(e.target.value); setPage(1); }}
              className="w-full rounded-md border border-border bg-bg py-2 pl-8 pr-3 text-sm text-text focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-soft"
            />
          </div>
          <DataTable
            columns={columns}
            rows={q.data?.data ?? []}
            rowKey={(r) => r.id_participacion}
            loading={q.isLoading}
            error={q.isError ? errMsg(q.error) : null}
            onRetry={() => q.refetch()}
            emptyMessage="Sin participaciones. Registra la primera en el formulario."
          />
          <Pagination {...metaToPagination(q.data?.meta)} onPage={setPage} disabled={q.isFetching} />
        </div>
      </div>
    </div>
  );
}
