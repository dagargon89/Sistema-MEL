/* =====================================================================
 * api.real.ts — Implementación REAL del contrato ApiClient (Fase 2).
 *
 * Misma firma que api.mock.ts; aquí cada método llama a la API CI4 (doc 05)
 * con axios y traduce la respuesta `{ success, data, pager }` al shape del
 * contrato. El estado del demo está VACÍO aquí a propósito: se rellena al
 * conectar el backend (Demo-First v2 §6). Mientras VITE_USE_MOCK !== "false"
 * este archivo no se ejecuta.
 *
 * Pasos de promoción a Fase 2:
 *   1. Mover demo-ux/app/  ->  apps/web/
 *   2. VITE_USE_MOCK=false  (.env)
 *   3. Sembrar MySQL con el mismo db.json (InitialSeeder -> insertBatch)
 *   4. Rellenar los métodos de abajo y borrar api.mock.ts + mock/
 * ===================================================================== */
import axios, { AxiosError, type AxiosInstance } from "axios";
import type { ApiClient } from "./api";
import { ApiError, type ErrorPayload } from "./types";

const BASE_URL = import.meta.env.VITE_API_BASE_URL ?? "/api/v1";

const http: AxiosInstance = axios.create({
  baseURL: BASE_URL,
  headers: { "Content-Type": "application/json" },
});

/** Inyecta el Bearer token (doc 05 §1.2). Se rellena al implementar el store de sesión. */
http.interceptors.request.use((config) => {
  const token = sessionStorage.getItem("mel.token");
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

/** Traduce errores HTTP del doc 05 §1.3/§1.4 al ApiError tipado del contrato. */
http.interceptors.response.use(
  (r) => r,
  (e: AxiosError<{ message?: string; errors?: Record<string, string> }>) => {
    const status = e.response?.status ?? 0;
    const payload: ErrorPayload = {
      message: e.response?.data?.message ?? e.message ?? "Error de red.",
      errors: e.response?.data?.errors,
    };
    return Promise.reject(new ApiError(status, payload));
  },
);

/** PENDIENTE Fase 2: implementar cada método contra los endpoints del doc 05.
 *  Se exporta el mismo tipo ApiClient para que el interruptor en index.ts compile;
 *  invocarlo antes de implementarlo lanza un error explícito en vez de fallar silencioso. */
const noImpl = (m: string) => (): never => {
  throw new ApiError(501, { code: "NOT_IMPLEMENTED", message: `api.real.${m} pendiente (Fase 2).` });
};

export const apiReal: ApiClient = {
  login: noImpl("login"),
  logout: noImpl("logout"),
  me: noImpl("me"),
  listarActividades: noImpl("listarActividades"),
  crearActividad: noImpl("crearActividad"),
  reclasificarActividad: noImpl("reclasificarActividad"),
  listarEjes: noImpl("listarEjes"),
  listarLineas: noImpl("listarLineas"),
  listarComponentes: noImpl("listarComponentes"),
  listarInstituciones: noImpl("listarInstituciones"),
  listarProcesos: noImpl("listarProcesos"),
  crearProceso: noImpl("crearProceso"),
  listarEventos: noImpl("listarEventos"),
  crearEvento: noImpl("crearEvento"),
  listarEjecuciones: noImpl("listarEjecuciones"),
  obtenerEjecucion: noImpl("obtenerEjecucion"),
  crearEjecucion: noImpl("crearEjecucion"),
  validarEjecucion: noImpl("validarEjecucion"),
  listarParticipaciones: noImpl("listarParticipaciones"),
  crearParticipacion: noImpl("crearParticipacion"),
  crearAgregada: noImpl("crearAgregada"),
  listarPersonas: noImpl("listarPersonas"),
  colaDuplicados: noImpl("colaDuplicados"),
  resolverDuplicado: noImpl("resolverDuplicado"),
  crearProducto: noImpl("crearProducto"),
  listarMetas: noImpl("listarMetas"),
  crearMeta: noImpl("crearMeta"),
  seguimientoMetas: noImpl("seguimientoMetas"),
  crearResultado: noImpl("crearResultado"),
  listarSolicitudes: noImpl("listarSolicitudes"),
  crearSolicitud: noImpl("crearSolicitud"),
  resolverSolicitud: noImpl("resolverSolicitud"),
  listarAuditoria: noImpl("listarAuditoria"),
  nombreEvidencia: noImpl("nombreEvidencia"),
  tablero: noImpl("tablero"),
};
