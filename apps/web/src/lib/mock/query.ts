/* =====================================================================
 * query.ts — Helper mínimo de consulta sobre db.json (Demo-First v2 §5).
 * Clonar + filtrar + paginar + normalizar. Sin dependencias externas.
 * NO se usa fuera de la capa mock; en Fase 2 se borra junto con db.json.
 * ===================================================================== */
import dbRaw from "./db.json";
import type { PageMeta } from "../types";

type Db = typeof dbRaw;

/** Clon profundo por lectura: nunca mutamos el JSON importado. */
const clone = <T>(v: T): T => JSON.parse(JSON.stringify(v)) as T;

/** Devuelve una COPIA del arreglo de una "tabla" del db.json. */
export function tabla<T>(nombre: Exclude<keyof Db, "_demo">): T[] {
  return clone(dbRaw[nombre] as unknown as T[]);
}

/** Metadatos del demo (now fijo, periodo POA actual, casos QA). */
export const demoMeta = clone(dbRaw._demo);

/** Normaliza acentos y caja (espejo de la colación utf8mb4_0900_ai_ci). */
export function norm(s: string | null | undefined): string {
  if (!s) return "";
  return s
    .normalize("NFD")
    .replace(/\p{Diacritic}/gu, "")
    .trim()
    .toUpperCase();
}

/** Búsqueda parcial acento/caja-insensible. */
export function coincide(haystack: string | null | undefined, needle: string): boolean {
  if (!needle) return true;
  return norm(haystack).includes(norm(needle));
}

/** Pagina un arreglo ya filtrado/ordenado y arma el `meta` del doc 05 §1.6. */
export function paginar<T>(rows: T[], page = 1, perPage = 15): { data: T[]; meta: PageMeta } {
  const per_page = Math.min(Math.max(perPage, 1), 100);
  const total = rows.length;
  const total_pages = Math.max(Math.ceil(total / per_page), 1);
  const p = Math.min(Math.max(page, 1), total_pages);
  const start = (p - 1) * per_page;
  return { data: rows.slice(start, start + per_page), meta: { page: p, per_page, total, total_pages } };
}
