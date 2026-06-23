import { ChevronLeft, ChevronRight } from "lucide-react";
import type { PageMeta } from "@/lib";
import { cn } from "./cn";

interface Props {
  /** Página actual (1-based). */
  page: number;
  /** Total de páginas. */
  totalPages: number;
  /** Total de registros (sin paginar). */
  total: number;
  /** Tamaño de página. */
  pageSize: number;
  onPage: (p: number) => void;
  /** Deshabilita los controles (p. ej. mientras carga). */
  disabled?: boolean;
}

const btn =
  "inline-flex items-center gap-1 rounded-md border border-border px-2.5 py-1.5 text-sm font-medium " +
  "text-text transition-colors hover:bg-primary-soft disabled:cursor-not-allowed disabled:opacity-40 " +
  "disabled:hover:bg-transparent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary";

/** Navegación de páginas. Se oculta si no hay registros. */
export function Pagination({ page, totalPages, total, pageSize, onPage, disabled }: Props) {
  if (total === 0) return null;
  const desde = (page - 1) * pageSize + 1;
  const hasta = Math.min(page * pageSize, total);

  return (
    <div className="flex flex-wrap items-center justify-between gap-3 px-1 py-3 text-sm text-text-muted">
      <span>
        Mostrando <span className="num text-text">{desde}–{hasta}</span> de{" "}
        <span className="num text-text">{total}</span>
      </span>
      <div className="flex items-center gap-2">
        <button
          type="button"
          className={cn(btn)}
          disabled={disabled || page <= 1}
          onClick={() => onPage(page - 1)}
          aria-label="Página anterior"
        >
          <ChevronLeft className="size-4" aria-hidden="true" /> Anterior
        </button>
        <span className="px-1">
          Página <span className="num text-text">{page}</span> de{" "}
          <span className="num text-text">{totalPages}</span>
        </span>
        <button
          type="button"
          className={cn(btn)}
          disabled={disabled || page >= totalPages}
          onClick={() => onPage(page + 1)}
          aria-label="Página siguiente"
        >
          Siguiente <ChevronRight className="size-4" aria-hidden="true" />
        </button>
      </div>
    </div>
  );
}

/** Construye los props de paginación a partir del meta del servidor. */
export function metaToPagination(meta: PageMeta | undefined) {
  return {
    page: meta?.page ?? 1,
    totalPages: meta?.total_pages ?? 1,
    total: meta?.total ?? 0,
    pageSize: meta?.per_page ?? 15,
  };
}
