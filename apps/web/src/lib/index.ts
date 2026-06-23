/* =====================================================================
 * index.ts — Interruptor único de origen de datos (Demo-First v2 §5).
 * Las pantallas y hooks importan SIEMPRE desde aquí:  import { api } from "@/lib";
 * Nunca importan api.mock / api.real / db.json directamente.
 *
 * VITE_USE_MOCK !== "false"  ->  mock (db.json)   [default del demo, Fase 1]
 * VITE_USE_MOCK === "false"  ->  real (API CI4)   [Fase 2]
 *
 * Cambiar de mock a real es cambiar esta variable de entorno. Cero cambios
 * en pantallas: ese es el punto de la metodología.
 * ===================================================================== */
import type { ApiClient } from "./api";
import { apiMock } from "./api.mock";
import { apiReal } from "./api.real";

const useMock = import.meta.env.VITE_USE_MOCK !== "false";

export const api: ApiClient = useMock ? apiMock : apiReal;
export const USING_MOCK = useMock;

export * from "./types";
export type { ApiClient, PageParams, FiltrosComunes } from "./api";
