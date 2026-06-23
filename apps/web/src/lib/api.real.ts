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
import {
  ApiError,
  type Actividad,
  type ActividadConHerencia,
  type Componente,
  type DuplicadoEnCola,
  type Eje,
  type Ejecucion,
  type EjecucionCreada,
  type ErrorPayload,
  type EventoProgramado,
  type Institucion,
  type Linea,
  type Meta,
  type PageMeta,
  type Participacion,
  type ParticipacionAgregada,
  type ParticipacionCreada,
  type PerfilResp,
  type Persona,
  type Proceso,
  type ProductoEntregable,
  type SeguimientoMeta,
  type SesionResp,
  type TableroEjecutivo,
} from "./types";

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

/** El backend emite el pager de CI4 (doc 05 §1.6); el contrato congelado usa `meta`. */
interface BackendPager {
  currentPage: number;
  pageCount: number;
  total: number;
  perPage: number;
}
function toMeta(p: BackendPager): PageMeta {
  return { page: p.currentPage, per_page: p.perPage, total: p.total, total_pages: p.pageCount };
}

/** PENDIENTE Fase 2: implementar cada método contra los endpoints del doc 05.
 *  Se exporta el mismo tipo ApiClient para que el interruptor en index.ts compile;
 *  invocarlo antes de implementarlo lanza un error explícito en vez de fallar silencioso. */
const noImpl = (m: string) => (): never => {
  throw new ApiError(501, { code: "NOT_IMPLEMENTED", message: `api.real.${m} pendiente (Fase 2).` });
};

export const apiReal: ApiClient = {
  /* ---- Auth (Sprint 1): desempaqueta el envelope { success, data } del doc 05 ---- */
  login: async (input) => {
    const { data } = await http.post<{ data: SesionResp }>("/auth/login", input);
    return data.data;
  },
  logout: async () => {
    await http.post("/auth/logout");
  },
  me: async () => {
    const { data } = await http.get<{ data: PerfilResp }>("/auth/me");
    return data.data;
  },
  /* ---- Catálogos (Sprint 2) ---- */
  listarActividades: async (p) => {
    const { data } = await http.get<{ data: ActividadConHerencia[]; pager: BackendPager }>(
      "/catalogos/actividades",
      { params: p },
    );
    return { data: data.data, meta: toMeta(data.pager) };
  },
  crearActividad: async (input) => {
    const { data } = await http.post<{ data: Actividad }>("/catalogos/actividades", input);
    return data.data;
  },
  reclasificarActividad: async (id, input) => {
    const { data } = await http.patch<{ data: Actividad }>(
      `/catalogos/actividades/${id}/tipo-registro`,
      input,
    );
    return data.data;
  },
  listarEjes: async () => (await http.get<{ data: Eje[] }>("/catalogos/ejes")).data.data,
  listarLineas: async (p) =>
    (await http.get<{ data: Linea[] }>("/catalogos/lineas", { params: p })).data.data,
  listarComponentes: async (p) =>
    (await http.get<{ data: Componente[] }>("/catalogos/componentes", { params: p })).data.data,
  listarInstituciones: async () =>
    (await http.get<{ data: Institucion[] }>("/catalogos/instituciones")).data.data,
  /* ---- Cadena MEL (Sprint 3): procesos → eventos → ejecuciones → participaciones ---- */
  listarProcesos: async (p) => {
    const { data } = await http.get<{ data: Proceso[]; pager: BackendPager }>("/procesos", { params: p });
    return { data: data.data, meta: toMeta(data.pager) };
  },
  crearProceso: async (input) => {
    const { data } = await http.post<{ data: Proceso }>("/procesos", input);
    return data.data;
  },
  listarEventos: async (p) => {
    const { data } = await http.get<{ data: EventoProgramado[]; pager: BackendPager }>("/eventos-programados", {
      params: p,
    });
    return { data: data.data, meta: toMeta(data.pager) };
  },
  crearEvento: async (input) => {
    const { data } = await http.post<{ data: EventoProgramado }>("/eventos-programados", input);
    return data.data;
  },
  listarEjecuciones: async (p) => {
    const { data } = await http.get<{ data: Ejecucion[]; pager: BackendPager }>("/ejecuciones", { params: p });
    return { data: data.data, meta: toMeta(data.pager) };
  },
  obtenerEjecucion: async (id) => (await http.get<{ data: Ejecucion }>(`/ejecuciones/${id}`)).data.data,
  crearEjecucion: async (input) => {
    const { data } = await http.post<{ data: EjecucionCreada }>("/ejecuciones", input);
    return data.data;
  },
  validarEjecucion: async (id, input) => {
    const { data } = await http.patch<{ data: Ejecucion }>(`/ejecuciones/${id}/validacion`, input);
    return data.data;
  },
  listarParticipaciones: async (idEjecucion, p) => {
    const { data } = await http.get<{ data: Participacion[]; pager: BackendPager }>(
      `/ejecuciones/${idEjecucion}/participaciones`,
      { params: p },
    );
    return { data: data.data, meta: toMeta(data.pager) };
  },
  crearParticipacion: async (input) => {
    const { data } = await http.post<{ data: ParticipacionCreada }>("/participaciones", input);
    return data.data;
  },
  crearAgregada: async (input) => {
    const { data } = await http.post<{ data: ParticipacionAgregada }>("/participaciones-agregadas", input);
    return data.data;
  },
  listarPersonas: async (p) => {
    const { data } = await http.get<{ data: Persona[]; pager: BackendPager }>("/personas", { params: p });
    return { data: data.data, meta: toMeta(data.pager) };
  },
  colaDuplicados: async (p) => {
    const { data } = await http.get<{ data: DuplicadoEnCola[]; pager: BackendPager }>("/personas/duplicados", {
      params: p,
    });
    return { data: data.data, meta: toMeta(data.pager) };
  },
  resolverDuplicado: async (idParticipacion, input) => {
    const { data } = await http.patch<{ data: Participacion }>(`/personas/duplicados/${idParticipacion}`, input);
    return data.data;
  },
  /* ---- Productos / metas / tableros (Fase 2 · Sprint 5) ---- */
  crearProducto: async (input) => {
    const { data } = await http.post<{ data: ProductoEntregable }>("/productos", input);
    return data.data;
  },
  listarMetas: async (p) => {
    const { data } = await http.get<{ data: Meta[]; pager: BackendPager }>("/metas", { params: p });
    return { data: data.data, meta: toMeta(data.pager) };
  },
  crearMeta: async (input) => {
    const { data } = await http.post<{ data: Meta }>("/metas", input);
    return data.data;
  },
  seguimientoMetas: async (p) =>
    (await http.get<{ data: SeguimientoMeta[] }>("/metas/seguimiento", { params: p })).data.data,
  crearResultado: noImpl("crearResultado"),
  listarSolicitudes: noImpl("listarSolicitudes"),
  crearSolicitud: noImpl("crearSolicitud"),
  resolverSolicitud: noImpl("resolverSolicitud"),
  listarAuditoria: noImpl("listarAuditoria"),
  nombreEvidencia: async (p) =>
    (await http.get<{ data: { nombre: string } }>("/evidencias/nombre", { params: p })).data.data,
  tablero: async (tipo, p) =>
    (await http.get<{ data: TableroEjecutivo }>(`/tableros/${tipo}`, { params: p })).data.data,
};
