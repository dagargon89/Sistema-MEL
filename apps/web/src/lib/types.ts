/* =====================================================================
 * types.ts — Tipos ESPEJO del Modelo de Datos (doc 03) y del contrato API
 * (doc 05). Toda forma de dato del demo nace aquí: las entidades replican
 * columnas, enums y nullabilidad del DDL; los *Input replican los request
 * del doc 05; los *Resp / *Dashboard replican las respuestas.
 *
 * Regla de fidelidad (Demo-First v2 §4): si una columna del DDL cambia,
 * cambia aquí y en db.json a la vez. Nada en las pantallas conoce otra forma.
 * ===================================================================== */

/* ---------------------------------------------------------------------
 * 0. Enums del DDL (doc 03 §3–§4)
 * ------------------------------------------------------------------- */
export type Estatus = "activo" | "inactivo";
export type TipoRegistro = "P" | "E" | "R";
export type CasoExcepcional = "A" | "B" | "C" | "D";
export type TipoProgramacion =
  | "SESION_UNICA"
  | "MULTI_SESION_PROGRAMADA"
  | "PROCESO_CONTINUO";
export type EstatusProceso = "activo" | "concluido" | "cancelado";
export type EstatusEvento =
  | "programado"
  | "ejecutado"
  | "cancelado"
  | "reprogramado";
export type EstatusEjecucion = "ejecutada" | "suspendida" | "parcial";
export type TipoParticipacion = "Nominal" | "Agregado" | "Mixta";
export type Sexo = "F" | "M" | "X";
export type AlertaDuplicado = "OK" | "DUPLICADO_EN_CAPTURA";

/** Máquina de estados del registro operativo (SRS §4). */
export type ControlRegistro =
  | "CAPTURADO"
  | "INCOMPLETO"
  | "REVISAR"
  | "OK"
  | "AGREGADO";
export type ControlParticipacion = "CAPTURADO" | "INCOMPLETO" | "REVISAR" | "OK";
export type ControlAutomatico = "OK" | "INCOMPLETO" | "REVISAR";
export type ControlProducto = "CAPTURADO" | "INCOMPLETO" | "OK";
export type ControlAgregada = "AGREGADO" | "INCOMPLETO";

export type EstatusProducto = "en_proceso" | "entregado" | "cancelado";
export type RolClave = "capturista" | "coordinacion" | "direccion" | "administrador";
export type TipoSolicitud = "correccion" | "mejora" | "ajuste";
export type NivelCriticidad = "BAJA" | "MEDIA" | "ALTA";
export type EstadoSolicitud = "en_revision" | "en_proceso" | "resuelta" | "descartada";
export type AccionAuditoria =
  | "alta"
  | "edicion"
  | "baja"
  | "reclasificacion"
  | "validacion";

/** Periodos POA M01..M18 (doc 03 — metas_mensuales, periodo_corte). */
export type MesPOA =
  | "M01" | "M02" | "M03" | "M04" | "M05" | "M06"
  | "M07" | "M08" | "M09" | "M10" | "M11" | "M12"
  | "M13" | "M14" | "M15" | "M16" | "M17" | "M18";

/* ---------------------------------------------------------------------
 * 1. Catálogos / dimensiones (doc 03 §3.1)
 * ------------------------------------------------------------------- */
export interface Eje {
  id_eje: string;
  num_eje_original: number | null;
  clave_eje_corto: string | null;
  nombre: string;
  orden_visualizacion: number;
}

export interface Linea {
  id_linea: string;
  num_linea: number | null;
  clave_linea_corta: string | null;
  nombre: string;
  id_eje: string;
  orden_visualizacion: number;
  estatus: Estatus;
}

export interface Institucion {
  id_institucion: string;
  num_institucion_original: number | null;
  nombre: string;
  estatus: Estatus;
  orden_visualizacion: number;
}

export interface Componente {
  id_componente: string;
  num_componente: number | null;
  clave_componente: string | null;
  nombre: string;
  id_institucion: string;
  orden_visualizacion: number;
  estatus: Estatus;
}

export interface Actividad {
  id_actividad: string;
  num_actividad: number | null;
  nombre: string;
  id_eje: string;
  id_linea: string;
  id_componente: string;
  id_institucion: string;
  tipo_registro: TipoRegistro;
  caso_excepcional: CasoExcepcional | null;
}

/** Herencia estratégica resuelta (doc 05 §3 — campo `herencia`). RF-CAT-011. */
export interface HerenciaEstrategica {
  eje: string;
  linea: string;
  componente: string;
  institucion: string;
}

/** Actividad con su herencia resuelta para la UI (solo lectura). */
export interface ActividadConHerencia extends Actividad {
  herencia: HerenciaEstrategica;
}

/* ---------------------------------------------------------------------
 * 2. Núcleo transaccional — cadena referencial (doc 03 §3.2)
 * ------------------------------------------------------------------- */
export interface Proceso {
  id_proceso: number;
  nombre: string;
  tipo_programacion: TipoProgramacion;
  id_actividad: string;
  fecha_inicio: string | null;
  fecha_fin: string | null;
  total_sesiones_programadas: number | null;
  responsable: string | null;
  contacto: string | null;
  estatus: EstatusProceso;
  observaciones: string | null;
}

export interface EventoProgramado {
  id_evento_programado: number;
  id_actividad: string;
  id_proceso: number | null;
  tipo_programacion: TipoProgramacion;
  fecha_inicio: string;
  fecha_finalizacion: string;
  hora_inicio: string | null;
  hora_finalizacion: string | null;
  modalidad: string | null;
  lugar: string | null;
  calle_y_numero: string | null;
  colonia: string | null;
  responsable: string | null;
  contacto: string | null;
  estatus: EstatusEvento;
  num_sesion: number | null;
  total_sesiones: number | null;
  observaciones: string | null;
}

export interface Ejecucion {
  id_ejecucion: number;
  id_evento_programado: number;
  fecha_ejecucion_real: string | null;
  hora_inicio_real: string | null;
  hora_finalizacion_real: string | null;
  lugar_real: string | null;
  colonia_real: string | null;
  responsable_real: string | null;
  estatus_ejecucion: EstatusEjecucion | null;
  tipo_registro_participacion: TipoParticipacion;
  total_participantes: number | null;
  evidencia_url: string | null;
  nombre_archivo_evidencia: string | null;
  resumen_narrativo: string | null;
  control_registro: ControlRegistro;
  observaciones: string | null;
}

export interface Participacion {
  id_participacion: number;
  id_ejecucion: number;
  id_persona: string | null;
  nombres: string;
  apellido_paterno: string;
  apellido_materno: string | null;
  anio_nacimiento: number | null;
  sexo: Sexo;
  telefono: string;
  correo: string | null;
  colonia_persona: string;
  id_datosbeneficiario: string;
  alerta_duplicado: AlertaDuplicado;
  fecha_participacion: string | null;
  control_registro: ControlParticipacion;
  control_automatico: ControlAutomatico | null;
  decision_coordinacion: ControlAutomatico | null;
  detalle_validacion: string | null;
}

export interface ParticipacionAgregada {
  id_participacion_agregada: number;
  id_ejecucion: number;
  tipo_registro_participacion: "Agregado" | "Mixta";
  sexo_grupo: string | null;
  grupo_edad_aprox: string | null;
  cantidad_participantes: number;
  motivo_no_nominal: string | null;
  fuente_conteo: string | null;
  periodo_corte: MesPOA | null;
  evidencia_url: string | null;
  control_registro: ControlAgregada;
}

export interface Persona {
  id_persona: string;
  nombres: string | null;
  apellido_paterno: string | null;
  apellido_materno: string | null;
  nombre_completo: string | null;
  anio_nacimiento: number | null;
  sexo: Sexo | null;
  telefono: string | null;
  correo: string | null;
  colonia: string | null;
  id_datosbeneficiario: string;
  primera_participacion: string | null;
  total_participaciones: number;
  control_registro: "OK" | "REVISAR";
  decision_coordinacion: string | null;
}

export interface ProductoEntregable {
  id_producto: number;
  id_actividad: string;
  nombre_producto: string;
  tipo_producto: string | null;
  fecha_inicio: string | null;
  fecha_entrega: string | null;
  responsable: string | null;
  cantidad: number | null;
  unidad_medida: string | null;
  estatus: EstatusProducto;
  descripcion: string | null;
  evidencia_url: string | null;
  nombre_archivo_evidencia: string | null;
  control_registro: ControlProducto;
}

/* ---------------------------------------------------------------------
 * 3. Metas (doc 03 §3.3) y resultados (§3.4)
 * ------------------------------------------------------------------- */
export interface Meta {
  id_meta: number;
  id_actividad: string;
  unidad_meta: string | null;
  unidad_especifica: string | null;
  meta_anual_total: number | null;
  observaciones: string | null;
}

export interface MetaMensual {
  id_meta_mensual: number;
  id_meta: number;
  mes: MesPOA;
  valor: number;
}

export interface Resultado {
  id_resultado: number;
  id_actividad: string;
  indicador: string;
  linea_base: number | null;
  valor_medido: number | null;
  metodo_medicion: string | null;
  fecha_medicion: string | null;
  evidencia_url: string | null;
}

/* ---------------------------------------------------------------------
 * 4. Incidencia (doc 03 §3.5) y verticales (§3.6)
 * ------------------------------------------------------------------- */
export interface PropuestaIncidencia {
  id_propuesta: number;
  nombre_propuesta: string;
  promotor_colectivo: string | null;
  tipo_actor: string | null;
  fecha_inicio_asesoria: string | null;
  responsable_equipo: string | null;
  sesiones_documentadas: number | null;
  mejora_documentada: boolean;
  cambios_resultado_asesoria: string | null;
  evidencia_principal: string | null;
  alineada_proyectos_estrategicos: boolean;
  criterios_alineacion_nota: string | null;
  estatus: string;
  elegible_reporte: boolean;
  id_actividad: string;
  periodo_reporte: MesPOA | null;
  control_registro: string;
}

export interface ProcesoIncidencia {
  id_proceso_incidencia: number;
  nombre: string;
  criterios_elegibilidad: string | null;
  ultimo_hito_resumen: string | null;
  control_registro: string;
  id_actividad: string;
}

export interface Compromiso {
  id_compromiso: number;
  id_proceso_incidencia: number;
  identificacion: string | null;
  seguimiento_documentado: string | null;
  criterios_elegibilidad: string | null;
  control_registro: string;
}

export interface Alianza {
  id_alianza: number;
  nombre_alianza: string;
  datos_alianza: string | null;
  criterios_elegibilidad: string | null;
  id_actividad: string;
  control_registro: string;
}

export interface HitoIncidencia {
  id_hito: number;
  id_proceso_incidencia: number;
  fecha_hito: string | null;
  tipo_hito: string | null;
  descripcion_hito: string | null;
  evidencia_nombre_o_nota: string | null;
  registrado_por: number | null;
  observaciones: string | null;
}

export interface OcupacionShelter {
  id_ocupacion: number;
  id_actividad: string;
  mes_periodo: MesPOA;
  tipo_espacio: string | null;
  capacidad_instalada: number;
  ocupacion: number;
  /** Calculado en vista (doc 03 §3.6). El mock lo deriva. */
  pct_ocupacion?: number | null;
  fuente: string | null;
  control_registro: string;
}

export interface SostenibilidadFinanciera {
  id_registro: number;
  id_actividad: string;
  mes_periodo: MesPOA;
  ingresos_brutos: number;
  costos_directos: number;
  costos_indirectos: number;
  recursos_efectivo: number;
  recursos_especie: number;
  fuente_datos: string | null;
  meta_anual: number;
  control_registro: string;
  /** Calculados en vista; el mock los deriva. */
  utilidad_neta_mes?: number;
  recursos_totales_mes?: number;
  pct_avance_anual?: number;
  semaforo?: Semaforo;
}

/* ---------------------------------------------------------------------
 * 5. Gobernanza y soporte (doc 03 §3.7)
 * ------------------------------------------------------------------- */
export interface Rol {
  id_rol: number;
  clave: RolClave;
  nombre: string;
  descripcion: string | null;
}

export interface Usuario {
  id_usuario: number;
  nombre: string;
  email: string;
  id_rol: number;
  estatus: Estatus;
}

export interface UsuarioInstitucion {
  id: number;
  id_usuario: number;
  id_institucion: string;
}

export interface Solicitud {
  id_solicitud: number;
  fecha_solicitud: string;
  id_solicitante: number;
  rol_solicitante: string | null;
  entidad_afectada: string | null;
  descripcion: string;
  tipo_solicitud: TipoSolicitud;
  nivel_criticidad: NivelCriticidad;
  impacto: string | null;
  estado: EstadoSolicitud;
  responsable_atencion: number | null;
  fecha_resolucion: string | null;
  comentarios: string | null;
}

export interface Auditoria {
  id_evento: number;
  fecha_hora: string;
  id_usuario: number | null;
  entidad: string;
  id_registro: string;
  accion: AccionAuditoria;
  valor_antes: Record<string, unknown> | null;
  valor_despues: Record<string, unknown> | null;
}

/* ---------------------------------------------------------------------
 * 6. Respuestas de autenticación y sesión (doc 05 §2)
 * ------------------------------------------------------------------- */
export interface LoginInput {
  email: string;
  password: string;
}

export interface SesionUsuario {
  id: number;
  nombre: string;
  rol: RolClave;
}

export interface SesionResp {
  token: string;
  user: SesionUsuario;
  ambito: string[];
}

export interface PerfilResp {
  user: SesionUsuario;
  rol: RolClave;
  ambito: string[];
}

/* ---------------------------------------------------------------------
 * 7. Inputs de escritura (doc 05 §4–§11)
 * ------------------------------------------------------------------- */
export interface ProcesoInput {
  nombre: string;
  tipo_programacion: TipoProgramacion;
  id_actividad: string;
  fecha_inicio?: string | null;
  fecha_fin?: string | null;
  total_sesiones_programadas?: number | null;
  responsable?: string | null;
  contacto?: string | null;
  observaciones?: string | null;
}

export interface EventoProgramadoInput {
  id_actividad: string;
  id_proceso?: number | null;
  tipo_programacion: TipoProgramacion;
  fecha_inicio: string;
  fecha_finalizacion: string;
  hora_inicio?: string | null;
  hora_finalizacion?: string | null;
  modalidad?: string | null;
  lugar?: string | null;
  calle_y_numero?: string | null;
  colonia?: string | null;
  responsable?: string | null;
  contacto?: string | null;
}

export interface EjecucionInput {
  id_evento_programado: number;
  fecha_ejecucion_real?: string | null;
  hora_inicio_real?: string | null;
  hora_finalizacion_real?: string | null;
  lugar_real?: string | null;
  colonia_real?: string | null;
  responsable_real?: string | null;
  estatus_ejecucion?: EstatusEjecucion | null;
  tipo_registro_participacion: TipoParticipacion;
  evidencia_url?: string | null;
  resumen_narrativo?: string | null;
  observaciones?: string | null;
}

/** Resultado del alta de ejecución (doc 05 §4 — POST /ejecuciones 201). */
export interface EjecucionCreada {
  id_ejecucion: number;
  control_registro: ControlRegistro;
  nombre_archivo_evidencia: string | null;
}

/** Transición de la máquina de estados (doc 05 — PATCH /ejecuciones/{id}/validacion). */
export interface ValidacionInput {
  control_registro: ControlRegistro;
  detalle?: string | null;
}

/** Alta de participación nominal. NOTA: el cliente NO envía id_persona ni
 *  control_registro; el servidor los calcula (doc 05 §4, RF-PART-041/042). */
export interface ParticipacionInput {
  id_ejecucion: number;
  nombres: string;
  apellido_paterno: string;
  apellido_materno?: string | null;
  anio_nacimiento?: number | null;
  sexo: Sexo;
  telefono: string;
  correo?: string | null;
  colonia_persona: string;
}

/** Resultado del alta de participación con dedup (doc 05 §4 — 201). */
export interface ParticipacionCreada {
  id_participacion: number;
  id_persona: string | null;
  control_registro: ControlParticipacion;
  alerta_duplicado: AlertaDuplicado;
}

export interface ParticipacionAgregadaInput {
  id_ejecucion: number;
  cantidad_participantes: number;
  sexo_grupo?: string | null;
  grupo_edad_aprox?: string | null;
  motivo_no_nominal?: string | null;
  fuente_conteo?: string | null;
  periodo_corte?: MesPOA | null;
}

/** Item de la cola de duplicados (doc 05 §5 — GET /personas/duplicados). */
export interface DuplicadoEnCola {
  id_participacion: number;
  nombres: string;
  apellido_paterno: string;
  id_persona_sugerida: string | null;
  score_similitud: number;
  motivo: string;
}

/** Resolución de un duplicado por coordinación (doc 05 §5 — PATCH). */
export interface ResolucionDuplicadoInput {
  accion: "fusionar" | "confirmar_nueva";
  id_persona_destino?: string | null;
  motivo: string;
}

export interface ProductoInput {
  id_actividad: string;
  nombre_producto: string;
  tipo_producto?: string | null;
  fecha_inicio?: string | null;
  fecha_entrega?: string | null;
  responsable?: string | null;
  cantidad?: number | null;
  unidad_medida?: string | null;
  estatus?: EstatusProducto;
  descripcion?: string | null;
  evidencia_url?: string | null;
}

export interface MetaInput {
  id_actividad: string;
  meta_anual_total?: number | null;
  unidad_meta?: string | null;
  unidad_especifica?: string | null;
  observaciones?: string | null;
  mensuales: { mes: MesPOA; valor: number }[];
}

export interface SolicitudInput {
  entidad_afectada?: string | null;
  descripcion: string;
  tipo_solicitud: TipoSolicitud;
  nivel_criticidad?: NivelCriticidad;
  impacto?: string | null;
}

export interface SolicitudPatchInput {
  estado: EstadoSolicitud;
  responsable_atencion?: number | null;
  comentarios?: string | null;
}

/* ---------------------------------------------------------------------
 * 7b. Inputs de incidencia y verticales (doc 05 §8–§9, extensión Fase 3)
 * ------------------------------------------------------------------- */
export interface PropuestaIncidenciaInput {
  id_actividad: string;
  nombre_propuesta: string;
  promotor_colectivo?: string | null;
  tipo_actor?: string | null;
  fecha_inicio_asesoria?: string | null;
  responsable_equipo?: string | null;
  sesiones_documentadas?: number | null;
  mejora_documentada?: boolean;
  cambios_resultado_asesoria?: string | null;
  evidencia_principal?: string | null;
  alineada_proyectos_estrategicos?: boolean;
  criterios_alineacion_nota?: string | null;
  estatus?: string;
  elegible_reporte?: boolean;
  periodo_reporte?: MesPOA | null;
}

export interface ProcesoIncidenciaInput {
  id_actividad: string;
  nombre: string;
  criterios_elegibilidad?: string | null;
  ultimo_hito_resumen?: string | null;
}

export interface CompromisoInput {
  id_proceso_incidencia: number;
  identificacion?: string | null;
  seguimiento_documentado?: string | null;
  criterios_elegibilidad?: string | null;
}

export interface AlianzaInput {
  id_actividad: string;
  nombre_alianza: string;
  datos_alianza?: string | null;
  criterios_elegibilidad?: string | null;
}

export interface HitoIncidenciaInput {
  id_proceso_incidencia: number;
  fecha_hito?: string | null;
  tipo_hito?: string | null;
  descripcion_hito?: string | null;
  evidencia_nombre_o_nota?: string | null;
  observaciones?: string | null;
}

export interface OcupacionShelterInput {
  id_actividad: string;
  mes_periodo: MesPOA;
  tipo_espacio?: string | null;
  capacidad_instalada: number;
  ocupacion: number;
  fuente?: string | null;
}

export interface SostenibilidadInput {
  id_actividad: string;
  mes_periodo: MesPOA;
  ingresos_brutos?: number;
  costos_directos?: number;
  costos_indirectos?: number;
  recursos_efectivo?: number;
  recursos_especie?: number;
  fuente_datos?: string | null;
  meta_anual?: number;
}

/* ---------------------------------------------------------------------
 * 8. Seguimiento de metas y tableros (doc 05 §7, §12)
 * ------------------------------------------------------------------- */
export type Semaforo =
  | "VERDE"
  | "AMARILLO"
  | "ROJO"
  | "SIN_META"
  | "CORTE_AL_CIERRE"
  | "FASE_3";

export interface SeguimientoMeta {
  id_actividad: string;
  tipo_registro?: TipoRegistro;
  caso_excepcional?: CasoExcepcional | null;
  mes: MesPOA;
  meta_mes: number;
  avance_mes: number;
  porcentaje: number | null;
  semaforo: Semaforo;
}

export type TipoTablero =
  | "operativo"
  | "coordinacion"
  | "ejecutivo"
  | "analitico"
  | "shelter";

/** KPIs del tablero ejecutivo (doc 05 §12). Calculados sobre control=OK. */
export interface TableroEjecutivo {
  beneficiarios_unicos: number;
  participaciones_nominales: number;
  participaciones_agregadas: number;
  cobertura_total: number;
  eventos_programados: number;
  ejecuciones: number;
  cumplimiento_ejecucion: number;
}

/** Reporte agregado para FECHAC (doc 05 §12, extensión Fase 4). Sobre control=OK. */
export interface ReporteFechac {
  generado: string;
  periodo: string | null;
  beneficiarios_unicos: number;
  participaciones_nominales: number;
  participaciones_agregadas: number;
  cobertura_total: number;
  eventos_programados: number;
  ejecuciones: number;
  cumplimiento_ejecucion: number;
  actividades: { P: number; E: number; R: number; total: number };
  resultados_reportados: number;
}

/* ---------------------------------------------------------------------
 * 9. Paginación y errores (doc 05 §1.4–§1.6)
 * ------------------------------------------------------------------- */
export interface PageMeta {
  page: number;
  per_page: number;
  total: number;
  total_pages: number;
}

export interface Paged<T> {
  data: T[];
  meta: PageMeta;
}

export interface ErrorPayload {
  code?: string;
  message: string;
  errors?: Record<string, string>;
}

/** Error tipado que el mock lanza con el código HTTP del doc 05 §1.3.
 *  api.real.ts lo construirá desde la respuesta real con el mismo shape. */
export class ApiError extends Error {
  readonly status: number;
  readonly payload: ErrorPayload;
  constructor(status: number, payload: ErrorPayload) {
    super(payload.message);
    this.name = "ApiError";
    this.status = status;
    this.payload = payload;
  }
}
