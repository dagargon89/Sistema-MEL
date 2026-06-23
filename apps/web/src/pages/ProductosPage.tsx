import { useState, type FormEvent } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { PackagePlus } from "lucide-react";
import { api } from "@/lib";
import type { ProductoInput, EstatusProducto } from "@/lib";
import { PageHeader } from "@/components/ui/PageHeader";
import { Select } from "@/components/ui/Select";
import { TextField } from "@/components/ui/TextField";
import { Button } from "@/components/ui/Button";
import { toast } from "@/store/toast";
import { errMsg } from "@/utils/errors";

const vacio = { id_actividad: "", nombre_producto: "", estatus: "en_proceso" as EstatusProducto, evidencia_url: "" };

export function ProductosPage() {
  const qc = useQueryClient();
  const [f, setF] = useState({ ...vacio });
  // Solo actividades tipo E admiten productos (RN-021 / RF-PROD-060).
  const acts = useQuery({ queryKey: ["actividades", "E"], queryFn: () => api.listarActividades({ tipo: "E", limit: 100 }) });

  const crear = useMutation({
    mutationFn: () => {
      const input: ProductoInput = {
        id_actividad: f.id_actividad,
        nombre_producto: f.nombre_producto.trim(),
        estatus: f.estatus,
        evidencia_url: f.evidencia_url.trim() || null,
      };
      return api.crearProducto(input);
    },
    onSuccess: (p) => {
      toast.success(`Producto registrado (${p.control_registro}).`);
      setF({ ...vacio });
      qc.invalidateQueries({ queryKey: ["tablero"] });
    },
    onError: (e) => toast.error(errMsg(e)),
  });

  return (
    <div>
      <PageHeader
        title="Productos / entregables"
        subtitle="Rama tipo E. El servidor bloquea registrar un producto sobre una actividad que no sea tipo E (QA5)."
      />
      <form
        onSubmit={(e: FormEvent) => { e.preventDefault(); crear.mutate(); }}
        className="grid max-w-xl gap-3 rounded-md border border-border bg-surface p-4"
      >
        <Select label="Actividad (tipo E)" required value={f.id_actividad} onChange={(e) => setF({ ...f, id_actividad: e.target.value })}>
          <option value="">Selecciona…</option>
          {(acts.data?.data ?? []).map((a) => (
            <option key={a.id_actividad} value={a.id_actividad}>
              {a.id_actividad} · {a.nombre}
            </option>
          ))}
        </Select>
        <TextField label="Nombre del producto" required value={f.nombre_producto} onChange={(e) => setF({ ...f, nombre_producto: e.target.value })} />
        <Select label="Estatus" value={f.estatus} onChange={(e) => setF({ ...f, estatus: e.target.value as EstatusProducto })}>
          <option value="en_proceso">En proceso</option>
          <option value="entregado">Entregado</option>
          <option value="cancelado">Cancelado</option>
        </Select>
        <TextField label="Evidencia (URL Drive)" type="url" value={f.evidencia_url} onChange={(e) => setF({ ...f, evidencia_url: e.target.value })} placeholder="https://drive.google.com/…" />
        <Button type="submit" loading={crear.isPending} icon={<PackagePlus className="size-4" />} className="mt-1">
          Registrar producto
        </Button>
      </form>
    </div>
  );
}
