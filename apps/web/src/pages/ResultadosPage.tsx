import { useState, type FormEvent } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { TrendingUp } from "lucide-react";
import { api } from "@/lib";
import type { Resultado } from "@/lib";
import { PageHeader } from "@/components/ui/PageHeader";
import { Select } from "@/components/ui/Select";
import { TextField } from "@/components/ui/TextField";
import { Button } from "@/components/ui/Button";
import { toast } from "@/store/toast";
import { errMsg } from "@/utils/errors";

const vacio = { id_actividad: "", indicador: "", linea_base: "", valor_medido: "", metodo_medicion: "" };

export function ResultadosPage() {
  const qc = useQueryClient();
  const [f, setF] = useState({ ...vacio });
  // Solo actividades tipo R admiten resultados (RF-RES-100).
  const acts = useQuery({ queryKey: ["actividades", "R"], queryFn: () => api.listarActividades({ tipo: "R", limit: 100 }) });

  const crear = useMutation({
    mutationFn: () => {
      const input: Omit<Resultado, "id_resultado"> = {
        id_actividad: f.id_actividad,
        indicador: f.indicador.trim(),
        linea_base: f.linea_base ? Number(f.linea_base) : null,
        valor_medido: f.valor_medido ? Number(f.valor_medido) : null,
        metodo_medicion: f.metodo_medicion.trim() || null,
        fecha_medicion: null,
        evidencia_url: null,
      };
      return api.crearResultado(input);
    },
    onSuccess: () => {
      toast.success("Resultado registrado.");
      setF({ ...vacio });
      qc.invalidateQueries({ queryKey: ["seguimientoMetas"] });
    },
    onError: (e) => toast.error(errMsg(e)),
  });

  return (
    <div>
      <PageHeader
        title="Resultados (tipo R)"
        subtitle="Capa MEL de madurez (Fase 4): indicador, línea base, valor medido, método y evidencia."
      />
      <form
        onSubmit={(e: FormEvent) => { e.preventDefault(); crear.mutate(); }}
        className="grid max-w-xl gap-3 rounded-md border border-border bg-surface p-4"
      >
        <Select label="Actividad (tipo R)" required value={f.id_actividad} onChange={(e) => setF({ ...f, id_actividad: e.target.value })}>
          <option value="">Selecciona…</option>
          {(acts.data?.data ?? []).map((a) => (
            <option key={a.id_actividad} value={a.id_actividad}>
              {a.id_actividad} · {a.nombre}
            </option>
          ))}
        </Select>
        <TextField label="Indicador" required value={f.indicador} onChange={(e) => setF({ ...f, indicador: e.target.value })} />
        <div className="grid grid-cols-2 gap-3">
          <TextField label="Línea base" type="number" value={f.linea_base} onChange={(e) => setF({ ...f, linea_base: e.target.value })} />
          <TextField label="Valor medido" type="number" value={f.valor_medido} onChange={(e) => setF({ ...f, valor_medido: e.target.value })} />
        </div>
        <TextField label="Método de medición" value={f.metodo_medicion} onChange={(e) => setF({ ...f, metodo_medicion: e.target.value })} />
        <Button type="submit" loading={crear.isPending} icon={<TrendingUp className="size-4" />} className="mt-1">
          Registrar resultado
        </Button>
      </form>
    </div>
  );
}
