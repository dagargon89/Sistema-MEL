import { useState } from "react";
import { useQuery, useMutation, useQueryClient, keepPreviousData } from "@tanstack/react-query";
import { api } from "@/lib";
import type { DuplicadoEnCola, ResolucionDuplicadoInput } from "@/lib";
import { PageHeader } from "@/components/ui/PageHeader";
import { DataTable, type Column } from "@/components/ui/DataTable";
import { Button } from "@/components/ui/Button";
import { Modal } from "@/components/ui/Modal";
import { Select } from "@/components/ui/Select";
import { Textarea } from "@/components/ui/Textarea";
import { Pagination, metaToPagination } from "@/components/ui/Pagination";
import { toast } from "@/store/toast";
import { errMsg } from "@/utils/errors";

export function DuplicadosPage() {
  const qc = useQueryClient();
  const [sel, setSel] = useState<DuplicadoEnCola | null>(null);
  const [accion, setAccion] = useState<ResolucionDuplicadoInput["accion"]>("fusionar");
  const [motivo, setMotivo] = useState("");
  const [page, setPage] = useState(1);

  const q = useQuery({
    queryKey: ["duplicados", page],
    queryFn: () => api.colaDuplicados({ page }),
    placeholderData: keepPreviousData,
  });

  const resolver = useMutation({
    mutationFn: () =>
      api.resolverDuplicado(sel!.id_participacion, {
        accion,
        id_persona_destino: accion === "fusionar" ? sel!.id_persona_sugerida : null,
        motivo: motivo.trim(),
      }),
    onSuccess: () => {
      toast.success(accion === "fusionar" ? "Participación fusionada con la persona sugerida." : "Confirmada como persona nueva.");
      setSel(null);
      setMotivo("");
      qc.invalidateQueries({ queryKey: ["duplicados"] });
      qc.invalidateQueries({ queryKey: ["tablero"] });
    },
    onError: (e) => toast.error(errMsg(e)),
  });

  const columns: Column<DuplicadoEnCola>[] = [
    { key: "id_participacion", header: "Part.", render: (r) => <span className="num">#{r.id_participacion}</span> },
    { key: "nombre", header: "Nombre", render: (r) => `${r.nombres} ${r.apellido_paterno}` },
    { key: "id_persona_sugerida", header: "Sugerida", render: (r) => <span className="num">{r.id_persona_sugerida ?? "—"}</span> },
    {
      key: "score_similitud",
      header: "Score",
      align: "right",
      render: (r) => <span className="num">{(r.score_similitud * 100).toFixed(0)}%</span>,
    },
    { key: "motivo", header: "Motivo" },
    {
      key: "accion",
      header: "",
      render: (r) => (
        <Button variant="secondary" onClick={() => setSel(r)}>
          Resolver
        </Button>
      ),
    },
  ];

  return (
    <div>
      <PageHeader
        title="Cola de duplicados"
        subtitle="Coordinación resuelve con traza. Nunca hay autofusión: la decisión queda en auditoría (ADR-003)."
      />
      <DataTable
        columns={columns}
        rows={q.data?.data ?? []}
        rowKey={(r) => r.id_participacion}
        loading={q.isLoading}
        error={q.isError ? errMsg(q.error) : null}
        onRetry={() => q.refetch()}
        emptyTitle="Cola vacía"
        emptyMessage="No hay duplicados sospechosos por revisar."
      />
      <Pagination {...metaToPagination(q.data?.meta)} onPage={setPage} disabled={q.isFetching} />

      <Modal
        open={!!sel}
        onClose={() => setSel(null)}
        title={`Resolver duplicado · participación #${sel?.id_participacion ?? ""}`}
        footer={
          <>
            <Button variant="ghost" onClick={() => setSel(null)}>
              Cancelar
            </Button>
            <Button loading={resolver.isPending} disabled={!motivo.trim()} onClick={() => resolver.mutate()}>
              Confirmar
            </Button>
          </>
        }
      >
        {sel && (
          <>
            <p className="mb-3 text-sm text-text">
              <strong>
                {sel.nombres} {sel.apellido_paterno}
              </strong>{" "}
              · sugerencia {sel.id_persona_sugerida ?? "—"} (score {(sel.score_similitud * 100).toFixed(0)}%).
            </p>
            <Select label="Acción" value={accion} onChange={(e) => setAccion(e.target.value as ResolucionDuplicadoInput["accion"])}>
              <option value="fusionar">Fusionar con la persona sugerida</option>
              <option value="confirmar_nueva">Confirmar como persona nueva</option>
            </Select>
            <Textarea
              label="Motivo"
              required
              className="mt-3"
              value={motivo}
              onChange={(e) => setMotivo(e.target.value)}
              placeholder="Justificación de la decisión (queda en auditoría)…"
            />
          </>
        )}
      </Modal>
    </div>
  );
}
