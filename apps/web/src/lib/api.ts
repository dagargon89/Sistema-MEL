/* =====================================================================
 * api.ts — LA INTERFAZ ÚNICA (contrato del doc 05). ES LO QUE SE CONGELA.
 *
 * Las pantallas y los hooks consumen `api.*` y NO saben si detrás hay un
 * db.json (mock) o la API CI4 real. Un método por endpoint del doc 05.
 * Pasar de mock a real es cambiar UNA implementación, no las pantallas
 * (Demo-First v2 §4). Cuando se congela el contrato, este archivo no cambia
 * sin un cambio coordinado en SRS(01) + API(05).
 *
 * Mapa método -> endpoint (doc 05):
 *   login                 POST   /api/v1/auth/login
 *   logout                POST   /api/v1/auth/logout
 *   me                    GET    /api/v1/auth/me
 *   listarActividades     GET    /api/v1/catalogos/actividades
 *   crearActividad        POST   /api/v1/catalogos/actividades
 *   reclasificarActividad PATCH  /api/v1/catalogos/actividades/{id}/tipo-registro
 *   listarEjes            GET    /api/v1/catalogos/ejes
 *   listarLineas          GET    /api/v1/catalogos/lineas
 *   listarComponentes     GET    /api/v1/catalogos/componentes
 *   listarInstituciones   GET    /api/v1/catalogos/instituciones
 *   listarProcesos        GET    /api/v1/procesos
 *   crearProceso          POST   /api/v1/procesos
 *   listarEventos         GET    /api/v1/eventos-programados
 *   crearEvento           POST   /api/v1/eventos-programados
 *   listarEjecuciones     GET    /api/v1/ejecuciones
 *   obtenerEjecucion      GET    /api/v1/ejecuciones/{id}
 *   crearEjecucion        POST   /api/v1/ejecuciones
 *   validarEjecucion      PATCH  /api/v1/ejecuciones/{id}/validacion
 *   listarParticipaciones GET    /api/v1/ejecuciones/{id}/participaciones
 *   crearParticipacion    POST   /api/v1/participaciones
 *   crearAgregada         POST   /api/v1/participaciones-agregadas
 *   listarPersonas        GET    /api/v1/personas
 *   colaDuplicados        GET    /api/v1/personas/duplicados
 *   resolverDuplicado     PATCH  /api/v1/personas/duplicados/{id}
 *   crearProducto         POST   /api/v1/productos
 *   listarMetas           GET    /api/v1/metas
 *   crearMeta             POST   /api/v1/metas
 *   seguimientoMetas      GET    /api/v1/metas/seguimiento
 *   crearResultado        POST   /api/v1/resultados
 *   listarSolicitudes     GET    /api/v1/solicitudes
 *   crearSolicitud        POST   /api/v1/solicitudes
 *   resolverSolicitud     PATCH  /api/v1/solicitudes/{id}
 *   listarAuditoria       GET    /api/v1/auditoria
 *   nombreEvidencia       GET    /api/v1/evidencias/nombre
 *   tablero               GET    /api/v1/tableros/{tipo}
 * ===================================================================== */
import type {
  Actividad,
  ActividadConHerencia,
  Auditoria,
  CasoExcepcional,
  Componente,
  DuplicadoEnCola,
  Eje,
  Ejecucion,
  EjecucionCreada,
  EjecucionInput,
  EstadoSolicitud,
  EstatusEvento,
  EventoProgramado,
  EventoProgramadoInput,
  Institucion,
  Linea,
  LoginInput,
  MesPOA,
  Meta,
  MetaInput,
  Paged,
  Participacion,
  ParticipacionAgregada,
  ParticipacionAgregadaInput,
  ParticipacionCreada,
  ParticipacionInput,
  PerfilResp,
  Persona,
  Proceso,
  ProcesoInput,
  ProductoEntregable,
  ProductoInput,
  Resultado,
  ResolucionDuplicadoInput,
  SeguimientoMeta,
  SesionResp,
  Solicitud,
  SolicitudInput,
  SolicitudPatchInput,
  TableroEjecutivo,
  TipoRegistro,
  TipoTablero,
  ValidacionInput,
} from "./types";

/** Paginación común (doc 05 §1.6). */
export interface PageParams {
  page?: number;
  limit?: number;
}

/** Filtros comunes de listado, siempre acotados al ámbito (doc 05 §1.6). */
export interface FiltrosComunes {
  institucion?: string;
  actividad?: string;
  periodo?: MesPOA;
  estatus?: string;
  control?: string;
}

export interface ApiClient {
  /* ---- Auth / sesión (doc 05 §2) ---- */
  login(input: LoginInputRef): Promise<SesionResp>;
  logout(): Promise<void>;
  me(): Promise<PerfilResp>;

  /* ---- Catálogos y herencia (doc 05 §3) ---- */
  listarActividades(p?: PageParams & {
    tipo?: TipoRegistro;
    caso?: CasoExcepcional;
    institucion?: string;
  }): Promise<Paged<ActividadConHerencia>>;
  crearActividad(input: Omit<Actividad, "num_actividad"> & { num_actividad?: number | null }): Promise<Actividad>;
  reclasificarActividad(id: string, input: { tipo_registro: TipoRegistro; motivo: string }): Promise<Actividad>;
  listarEjes(): Promise<Eje[]>;
  listarLineas(p?: { id_eje?: string }): Promise<Linea[]>;
  listarComponentes(p?: { id_institucion?: string }): Promise<Componente[]>;
  listarInstituciones(): Promise<Institucion[]>;

  /* ---- Procesos y programación (doc 05 §4) ---- */
  listarProcesos(p?: PageParams & { id_actividad?: string }): Promise<Paged<Proceso>>;
  crearProceso(input: ProcesoInput): Promise<Proceso>;
  listarEventos(p?: PageParams & {
    id_actividad?: string;
    institucion?: string;
    responsable?: string;
    periodo?: string;
    estatus?: EstatusEvento;
  }): Promise<Paged<EventoProgramado>>;
  crearEvento(input: EventoProgramadoInput): Promise<EventoProgramado>;

  /* ---- Ejecuciones — máquina de estados (doc 05 §4) ---- */
  listarEjecuciones(p?: PageParams & {
    id_evento_programado?: number;
    control?: Ejecucion["control_registro"];
  }): Promise<Paged<Ejecucion>>;
  obtenerEjecucion(id: number): Promise<Ejecucion>;
  crearEjecucion(input: EjecucionInput): Promise<EjecucionCreada>;
  validarEjecucion(id: number, input: ValidacionInput): Promise<Ejecucion>;

  /* ---- Participación y dedup (doc 05 §4–§5) ---- */
  listarParticipaciones(idEjecucion: number, p?: PageParams & { q?: string }): Promise<Paged<Participacion>>;
  crearParticipacion(input: ParticipacionInput): Promise<ParticipacionCreada>;
  crearAgregada(input: ParticipacionAgregadaInput): Promise<ParticipacionAgregada>;
  listarPersonas(p?: PageParams & { control?: Persona["control_registro"]; q?: string }): Promise<Paged<Persona>>;
  colaDuplicados(p?: PageParams): Promise<Paged<DuplicadoEnCola>>;
  resolverDuplicado(idParticipacion: number, input: ResolucionDuplicadoInput): Promise<Participacion>;

  /* ---- Productos / entregables tipo E (doc 05 §6) ---- */
  crearProducto(input: ProductoInput): Promise<ProductoEntregable>;

  /* ---- Metas y seguimiento (doc 05 §7) ---- */
  listarMetas(p?: PageParams & { id_actividad?: string }): Promise<Paged<Meta>>;
  crearMeta(input: MetaInput): Promise<Meta>;
  seguimientoMetas(p?: { periodo?: MesPOA; institucion?: string; eje?: string }): Promise<SeguimientoMeta[]>;

  /* ---- Resultados tipo R (doc 05 §10) ---- */
  crearResultado(input: Omit<Resultado, "id_resultado">): Promise<Resultado>;

  /* ---- Gobernanza (doc 05 §11) ---- */
  listarSolicitudes(p?: PageParams & { estado?: EstadoSolicitud }): Promise<Paged<Solicitud>>;
  crearSolicitud(input: SolicitudInput): Promise<Solicitud>;
  resolverSolicitud(id: number, input: SolicitudPatchInput): Promise<Solicitud>;
  listarAuditoria(p?: PageParams & { entidad?: string }): Promise<Paged<Auditoria>>;
  nombreEvidencia(p: { id_evento: number; id_actividad: string; ext: string }): Promise<{ nombre: string }>;

  /* ---- Tableros (doc 05 §12) ---- */
  tablero(tipo: TipoTablero, p?: FiltrosComunes): Promise<TableroEjecutivo>;
}

/** Alias del input de login para no acoplar el orden de imports. */
export type LoginInputRef = LoginInput;
