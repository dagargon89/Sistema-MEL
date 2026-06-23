import { useState } from "react";
import { useQuery, keepPreviousData } from "@tanstack/react-query";
import { Search } from "lucide-react";
import { api } from "@/lib";
import type { Persona } from "@/lib";
import { PageHeader } from "@/components/ui/PageHeader";
import { DataTable, type Column } from "@/components/ui/DataTable";
import { StatusBadge } from "@/components/ui/StatusBadge";
import { Select } from "@/components/ui/Select";
import { Pagination, metaToPagination } from "@/components/ui/Pagination";
import { errMsg } from "@/utils/errors";

export function PersonasPage() {
  const [control, setControl] = useState<string>("");
  const [q, setQ] = useState<string>("");
  const [page, setPage] = useState(1);

  const query = useQuery({
    queryKey: ["personas", control, q, page],
    queryFn: () =>
      api.listarPersonas({
        control: (control || undefined) as Persona["control_registro"] | undefined,
        q: q.trim() || undefined,
        page,
      }),
    placeholderData: keepPreviousData,
  });

  const columns: Column<Persona>[] = [
    { key: "id_persona", header: "ID", render: (r) => <span className="num">{r.id_persona}</span> },
    { key: "nombre_completo", header: "Nombre", render: (r) => r.nombre_completo ?? "—" },
    { key: "telefono", header: "Teléfono", render: (r) => <span className="num">{r.telefono ?? "—"}</span> },
    { key: "colonia", header: "Colonia", render: (r) => r.colonia ?? "—" },
    { key: "total_participaciones", header: "Particip.", align: "right", render: (r) => <span className="num">{r.total_participaciones}</span> },
    { key: "control_registro", header: "Control", render: (r) => <StatusBadge control={r.control_registro} /> },
  ];

  return (
    <div>
      <PageHeader
        title="Personas (consolidado)"
        subtitle="Tabla derivada por deduplicación; no tiene alta manual (RF-PART-044). Solo las OK cuentan como beneficiarios."
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <div className="relative">
              <Search className="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-text-muted" aria-hidden="true" />
              <input
                type="search"
                aria-label="Buscar persona"
                placeholder="Buscar nombre, teléfono o correo…"
                value={q}
                onChange={(e) => { setQ(e.target.value); setPage(1); }}
                className="w-64 rounded-md border border-border bg-bg py-2 pl-8 pr-3 text-sm text-text focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-soft"
              />
            </div>
            <Select label="" aria-label="Filtrar por control" value={control} onChange={(e) => { setControl(e.target.value); setPage(1); }} className="w-40">
              <option value="">Todas</option>
              <option value="OK">OK</option>
              <option value="REVISAR">Revisar</option>
            </Select>
          </div>
        }
      />
      <DataTable
        columns={columns}
        rows={query.data?.data ?? []}
        rowKey={(r) => r.id_persona}
        loading={query.isLoading}
        error={query.isError ? errMsg(query.error) : null}
        onRetry={() => query.refetch()}
        emptyMessage="Sin personas que coincidan con la búsqueda."
      />
      <Pagination {...metaToPagination(query.data?.meta)} onPage={setPage} disabled={query.isFetching} />
    </div>
  );
}
