/* =====================================================================
 * api.mock.ts — Implementación MOCK del contrato ApiClient.
 *
 * Lee db.json (espejo del DDL) y SIMULA el comportamiento server-side del
 * doc 05: herencia estratégica, deduplicación determinista, máquina de
 * estados de control_registro, KPIs sobre "vistas" (solo control=OK) y los
 * códigos de error del doc 05 §1.3. Las pantallas no saben que existe.
 * En Fase 2 se borra y se usa api.real.ts con la misma firma.
 *
 * Los datos viven solo en memoria (clones del db.json). Las escrituras
 * persisten dentro de la sesión del navegador y se reinician al recargar:
 * el demo NO persiste nada en disco (orden seguro de la metodología).
 * ===================================================================== */
import type { ApiClient, FiltrosComunes, PageParams } from "./api";
import {
  ApiError,
  type Alianza,
  type AlianzaInput,
  type Compromiso,
  type CompromisoInput,
  type HitoIncidencia,
  type HitoIncidenciaInput,
  type OcupacionShelter,
  type OcupacionShelterInput,
  type ProcesoIncidencia,
  type ProcesoIncidenciaInput,
  type PropuestaIncidencia,
  type PropuestaIncidenciaInput,
  type SostenibilidadFinanciera,
  type SostenibilidadInput,
  type Actividad,
  type ActividadConHerencia,
  type Auditoria,
  type CasoExcepcional,
  type Componente,
  type DuplicadoEnCola,
  type Eje,
  type Ejecucion,
  type EjecucionCreada,
  type EjecucionInput,
  type EventoProgramado,
  type EventoProgramadoInput,
  type Institucion,
  type Linea,
  type LoginInput,
  type MesPOA,
  type Meta,
  type MetaInput,
  type MetaMensual,
  type Paged,
  type Participacion,
  type ParticipacionAgregada,
  type ParticipacionAgregadaInput,
  type ParticipacionCreada,
  type ParticipacionInput,
  type PerfilResp,
  type Persona,
  type Proceso,
  type ProcesoInput,
  type ProductoEntregable,
  type ProductoInput,
  type Resultado,
  type ResolucionDuplicadoInput,
  type Rol,
  type SeguimientoMeta,
  type Semaforo,
  type SesionResp,
  type Solicitud,
  type SolicitudInput,
  type SolicitudPatchInput,
  type TableroEjecutivo,
  type TipoRegistro,
  type TipoTablero,
  type Usuario,
  type UsuarioInstitucion,
  type ValidacionInput,
} from "./types";
import { coincide, demoMeta, norm, paginar, tabla } from "./mock/query";

/* ---------------------------------------------------------------------
 * Estado en memoria (clones mutables del db.json para esta sesión)
 * ------------------------------------------------------------------- */
const ejes = tabla<Eje>("ejes");
const lineas = tabla<Linea>("lineas");
const instituciones = tabla<Institucion>("instituciones");
const componentes = tabla<Componente>("componentes");
const actividades = tabla<Actividad>("actividades");
const procesos = tabla<Proceso>("procesos");
const eventos = tabla<EventoProgramado>("eventos_programados");
const ejecuciones = tabla<Ejecucion>("ejecuciones");
const participaciones = tabla<Participacion>("participaciones");
const agregadas = tabla<ParticipacionAgregada>("participaciones_agregadas");
const personas = tabla<Persona>("personas");
const productos = tabla<ProductoEntregable>("productos_entregables");
const metas = tabla<Meta>("metas");
const metasMensuales = tabla<MetaMensual>("metas_mensuales");
const resultados = tabla<Resultado>("resultados");
const roles = tabla<Rol>("roles");
const usuarios = tabla<Usuario>("usuarios");
const usuarioInstitucion = tabla<UsuarioInstitucion>("usuario_institucion");
const solicitudes = tabla<Solicitud>("solicitudes");
const auditoria = tabla<Auditoria>("auditoria");
const propuestasIncidencia = tabla<PropuestaIncidencia>("propuestas_incidencia");
const procesosIncidencia = tabla<ProcesoIncidencia>("procesos_incidencia");
const compromisos = tabla<Compromiso>("compromisos");
const alianzas = tabla<Alianza>("alianzas");
const hitosIncidencia = tabla<HitoIncidencia>("hitos_incidencia");
const ocupacionShelter = tabla<OcupacionShelter>("ocupacion_shelter");
const sostenibilidad = tabla<SostenibilidadFinanciera>("sostenibilidad_financiera");

/* ---------------------------------------------------------------------
 * Sesión simulada y utilidades
 * ------------------------------------------------------------------- */
const delay = (ms = 280) => new Promise((r) => setTimeout(r, ms));
const NOW: string = demoMeta.demo_now;
let sesionActual: { user_id: number; rol: Rol["clave"]; ambito: string[] } | null = null;

function err(status: number, message: string, errors?: Record<string, string>, code?: string): never {
  throw new ApiError(status, { code, message, errors });
}

function requiereSesion(): { user_id: number; rol: Rol["clave"]; ambito: string[] } {
  if (!sesionActual) err(401, "No autenticado.");
  return sesionActual;
}

function requiereRol(...permitidos: Rol["clave"][]): void {
  const s = requiereSesion();
  if (!permitidos.includes(s.rol)) err(403, "No tiene permiso para esta acción.");
}

const ambitoDe = (userId: number): string[] =>
  usuarioInstitucion.filter((ui) => ui.id_usuario === userId).map((ui) => ui.id_institucion);

const actividadDe = (id: string): Actividad | undefined =>
  actividades.find((a) => a.id_actividad === id);

/** Institución heredada de un registro vía su actividad (doc 03 §2). */
const institucionDeActividad = (idActividad: string): string | null =>
  actividadDe(idActividad)?.id_institucion ?? null;

/** Institución heredada de un proceso de incidencia vía su actividad. */
const institucionDeProcesoIncidencia = (id: number): string | null => {
  const pi = procesosIncidencia.find((x) => x.id_proceso_incidencia === id);
  return pi ? institucionDeActividad(pi.id_actividad) : null;
};

/** % de ocupación calculado (doc 03 §3.6). */
const conPctOcupacion = (o: OcupacionShelter): OcupacionShelter => ({
  ...o,
  pct_ocupacion: o.capacidad_instalada > 0 ? Math.round((o.ocupacion / o.capacidad_instalada) * 1000) / 10 : null,
});

/** Indicadores financieros calculados (doc 03 §3.6). */
const conIndicadoresFinancieros = (s: SostenibilidadFinanciera): SostenibilidadFinanciera => {
  const pct = s.meta_anual > 0 ? Math.round((s.ingresos_brutos / s.meta_anual) * 1000) / 10 : undefined;
  const semaforo: Semaforo =
    s.meta_anual <= 0 || pct === undefined ? "SIN_META" : pct >= 90 ? "VERDE" : pct >= 75 ? "AMARILLO" : "ROJO";
  return {
    ...s,
    utilidad_neta_mes: s.ingresos_brutos - s.costos_directos - s.costos_indirectos,
    recursos_totales_mes: s.recursos_efectivo + s.recursos_especie,
    pct_avance_anual: pct,
    semaforo,
  };
};

/** ¿La actividad está dentro del ámbito de la sesión? Coordinación/dirección/admin globales si su ámbito cubre todo. */
function enAmbito(idActividad: string): boolean {
  const s = requiereSesion();
  const inst = institucionDeActividad(idActividad);
  if (!inst) return false;
  return s.ambito.includes(inst);
}

const nextId = <T>(rows: T[], key: keyof T): number =>
  rows.reduce((max, r) => Math.max(max, Number(r[key]) || 0), 0) + 1;

/* ---------------------------------------------------------------------
 * Lógica de dominio simulada (espejo del backend)
 * ------------------------------------------------------------------- */

/** Clave de dedup determinista (RF-PART-041 / RN-060/061). Acento/espacio-insensible,
 *  rellena a CHAR(40) como el DDL. El backend la calcula igual antes de persistir. */
function calcularClaveDedup(p: {
  apellido_paterno: string;
  apellido_materno?: string | null;
  nombres: string;
  anio_nacimiento?: number | null;
  telefono: string;
}): string {
  const base =
    norm(p.apellido_paterno) +
    norm(p.apellido_materno) +
    norm(p.nombres) +
    (p.anio_nacimiento ?? "") +
    "_" +
    (p.telefono ?? "").replace(/\D/g, "");
  return (base + "_".repeat(40)).slice(0, 40);
}

/** Herencia estratégica resuelta para la UI (RF-CAT-011). */
function herenciaDe(a: Actividad): ActividadConHerencia {
  const eje = ejes.find((e) => e.id_eje === a.id_eje)?.nombre ?? a.id_eje;
  const linea = lineas.find((l) => l.id_linea === a.id_linea)?.nombre ?? a.id_linea;
  const componente = componentes.find((c) => c.id_componente === a.id_componente)?.nombre ?? a.id_componente;
  const institucion = instituciones.find((i) => i.id_institucion === a.id_institucion)?.nombre ?? a.id_institucion;
  return { ...a, herencia: { eje, linea, componente, institucion } };
}

/** Registra un evento de auditoría (RF-GOB-112). */
function auditar(
  entidad: string,
  idRegistro: string,
  accion: Auditoria["accion"],
  antes: Record<string, unknown> | null,
  despues: Record<string, unknown> | null,
): void {
  auditoria.unshift({
    id_evento: nextId(auditoria, "id_evento"),
    fecha_hora: NOW.replace("T", " ").slice(0, 19) + ".000000",
    id_usuario: sesionActual?.user_id ?? null,
    entidad,
    id_registro: idRegistro,
    accion,
    valor_antes: antes,
    valor_despues: despues,
  });
}

/** Nombre normalizado de evidencia (RF-GOB-113). */
function generarNombreEvidencia(idEvento: number | null, idActividad: string, ext: string): string {
  const fecha = NOW.slice(0, 10).replace(/-/g, "");
  const act = idActividad.replace("_", "");
  const ev = idEvento ?? "NA";
  return `CPJ_EVID_${fecha}_${ev}_${act}_001.${ext}`;
}

/** Transiciones legales de la máquina de estados (SRS §4.1). */
const TRANSICIONES: Record<string, Ejecucion["control_registro"][]> = {
  CAPTURADO: ["INCOMPLETO", "REVISAR", "OK", "AGREGADO"],
  INCOMPLETO: ["OK", "REVISAR"],
  REVISAR: ["OK", "INCOMPLETO"],
  OK: ["REVISAR"],
  AGREGADO: ["AGREGADO"],
};

/* ---------------------------------------------------------------------
 * Implementación del contrato
 * ------------------------------------------------------------------- */
export const apiMock: ApiClient = {
  /* ====== Auth ====== */
  async login(input: LoginInput): Promise<SesionResp> {
    await delay();
    const u = usuarios.find((x) => norm(x.email) === norm(input.email));
    if (!u || u.estatus !== "activo") err(401, "Credenciales inválidas.");
    const rol = roles.find((r) => r.id_rol === u.id_rol)!;
    const ambito = ambitoDe(u.id_usuario);
    sesionActual = { user_id: u.id_usuario, rol: rol.clave, ambito };
    return {
      token: `demo.${btoa(u.email)}.${Date.now()}`,
      user: { id: u.id_usuario, nombre: u.nombre, rol: rol.clave },
      ambito,
    };
  },

  async logout(): Promise<void> {
    await delay(120);
    sesionActual = null;
  },

  async me(): Promise<PerfilResp> {
    await delay(120);
    const s = requiereSesion();
    const u = usuarios.find((x) => x.id_usuario === s.user_id)!;
    return { user: { id: u.id_usuario, nombre: u.nombre, rol: s.rol }, rol: s.rol, ambito: s.ambito };
  },

  /* ====== Catálogos ====== */
  async listarActividades(p): Promise<Paged<ActividadConHerencia>> {
    await delay();
    const s = requiereSesion();
    const rows = actividades
      .filter((a) => s.ambito.includes(a.id_institucion))
      .filter((a) => (p?.tipo ? a.tipo_registro === p.tipo : true))
      .filter((a) => (p?.caso ? a.caso_excepcional === p.caso : true))
      .filter((a) => (p?.institucion ? a.id_institucion === p.institucion : true))
      .map(herenciaDe);
    return paginar(rows, p?.page, p?.limit);
  },

  async crearActividad(input): Promise<Actividad> {
    await delay();
    requiereRol("coordinacion", "administrador");
    const nueva: Actividad = {
      ...input,
      num_actividad: input.num_actividad ?? null,
      caso_excepcional: input.caso_excepcional ?? null,
    };
    actividades.push(nueva);
    auditar("actividades", nueva.id_actividad, "alta", null, { ...nueva });
    return nueva;
  },

  async reclasificarActividad(id, input): Promise<Actividad> {
    await delay();
    requiereRol("coordinacion");
    const a = actividadDe(id);
    if (!a) err(404, "Actividad inexistente.");
    const tieneEjecuciones = eventos
      .filter((e) => e.id_actividad === id)
      .some((e) => ejecuciones.some((ej) => ej.id_evento_programado === e.id_evento_programado));
    if (input.tipo_registro === "E" && tieneEjecuciones)
      err(409, "No se puede reclasificar a tipo E: la actividad ya tiene ejecuciones nominales.");
    const antes = { tipo_registro: a.tipo_registro };
    a.tipo_registro = input.tipo_registro;
    auditar("actividades", id, "reclasificacion", antes, { tipo_registro: a.tipo_registro, motivo: input.motivo });
    return { ...a };
  },

  async listarEjes(): Promise<Eje[]> {
    await delay(120);
    requiereSesion();
    return [...ejes].sort((a, b) => a.orden_visualizacion - b.orden_visualizacion);
  },

  async listarLineas(p): Promise<Linea[]> {
    await delay(120);
    requiereSesion();
    return lineas.filter((l) => (p?.id_eje ? l.id_eje === p.id_eje : true));
  },

  async listarComponentes(p): Promise<Componente[]> {
    await delay(120);
    requiereSesion();
    return componentes.filter((c) => (p?.id_institucion ? c.id_institucion === p.id_institucion : true));
  },

  async listarInstituciones(): Promise<Institucion[]> {
    await delay(120);
    const s = requiereSesion();
    return instituciones.filter((i) => s.ambito.includes(i.id_institucion));
  },

  /* ====== Procesos y eventos ====== */
  async listarProcesos(p): Promise<Paged<Proceso>> {
    await delay();
    const s = requiereSesion();
    const rows = procesos
      .filter((pr) => s.ambito.includes(institucionDeActividad(pr.id_actividad) ?? ""))
      .filter((pr) => (p?.id_actividad ? pr.id_actividad === p.id_actividad : true));
    return paginar(rows, p?.page, p?.limit);
  },

  async crearProceso(input: ProcesoInput): Promise<Proceso> {
    await delay();
    requiereSesion();
    if (!actividadDe(input.id_actividad)) err(422, "Datos inválidos.", { id_actividad: "Actividad inexistente." });
    if (!enAmbito(input.id_actividad)) err(403, "Fuera de su ámbito de institución.");
    const nuevo: Proceso = {
      id_proceso: nextId(procesos, "id_proceso"),
      nombre: input.nombre,
      tipo_programacion: input.tipo_programacion,
      id_actividad: input.id_actividad,
      fecha_inicio: input.fecha_inicio ?? null,
      fecha_fin: input.fecha_fin ?? null,
      total_sesiones_programadas: input.total_sesiones_programadas ?? null,
      responsable: input.responsable ?? null,
      contacto: input.contacto ?? null,
      estatus: "activo",
      observaciones: input.observaciones ?? null,
    };
    procesos.push(nuevo);
    auditar("procesos", String(nuevo.id_proceso), "alta", null, { ...nuevo });
    return nuevo;
  },

  async listarEventos(p): Promise<Paged<EventoProgramado>> {
    await delay();
    const s = requiereSesion();
    const rows = eventos
      .filter((e) => s.ambito.includes(institucionDeActividad(e.id_actividad) ?? ""))
      .filter((e) => (p?.id_actividad ? e.id_actividad === p.id_actividad : true))
      .filter((e) => (p?.institucion ? institucionDeActividad(e.id_actividad) === p.institucion : true))
      .filter((e) => (p?.responsable ? coincide(e.responsable, p.responsable) : true))
      .filter((e) => (p?.estatus ? e.estatus === p.estatus : true));
    return paginar(rows, p?.page, p?.limit);
  },

  async crearEvento(input: EventoProgramadoInput): Promise<EventoProgramado> {
    await delay();
    requiereSesion();
    if (!actividadDe(input.id_actividad)) err(422, "Datos inválidos.", { id_actividad: "Actividad inexistente." });
    if (!enAmbito(input.id_actividad)) err(403, "Fuera de su ámbito de institución.");
    // RF-PROG-021: proceso obligatorio en multisesión.
    if (input.tipo_programacion !== "SESION_UNICA" && !input.id_proceso)
      err(422, "Datos inválidos.", { id_proceso: "El proceso es obligatorio en programación multisesión." });
    // RF-PROG-022: fechas no invertidas.
    if (input.fecha_finalizacion < input.fecha_inicio)
      err(422, "Datos inválidos.", { fecha_finalizacion: "La fecha de finalización no puede ser anterior a la de inicio." });
    const nuevo: EventoProgramado = {
      id_evento_programado: nextId(eventos, "id_evento_programado"),
      id_actividad: input.id_actividad,
      id_proceso: input.id_proceso ?? null,
      tipo_programacion: input.tipo_programacion,
      fecha_inicio: input.fecha_inicio,
      fecha_finalizacion: input.fecha_finalizacion,
      hora_inicio: input.hora_inicio ?? null,
      hora_finalizacion: input.hora_finalizacion ?? null,
      modalidad: input.modalidad ?? null,
      lugar: input.lugar ?? null,
      calle_y_numero: input.calle_y_numero ?? null,
      colonia: input.colonia ?? null,
      responsable: input.responsable ?? null,
      contacto: input.contacto ?? null,
      estatus: "programado",
      num_sesion: null,
      total_sesiones: null,
      observaciones: null,
    };
    eventos.push(nuevo);
    auditar("eventos_programados", String(nuevo.id_evento_programado), "alta", null, { ...nuevo });
    return nuevo;
  },

  /* ====== Ejecuciones — máquina de estados ====== */
  async listarEjecuciones(p): Promise<Paged<Ejecucion>> {
    await delay();
    const s = requiereSesion();
    const enAmbitoEjec = (e: Ejecucion) => {
      const ev = eventos.find((x) => x.id_evento_programado === e.id_evento_programado);
      return ev ? s.ambito.includes(institucionDeActividad(ev.id_actividad) ?? "") : false;
    };
    const rows = ejecuciones
      .filter(enAmbitoEjec)
      .filter((e) => (p?.id_evento_programado ? e.id_evento_programado === p.id_evento_programado : true))
      .filter((e) => (p?.control ? e.control_registro === p.control : true));
    return paginar(rows, p?.page, p?.limit);
  },

  async obtenerEjecucion(id: number): Promise<Ejecucion> {
    await delay(150);
    requiereSesion();
    const e = ejecuciones.find((x) => x.id_ejecucion === id);
    if (!e) err(404, "Ejecución inexistente.");
    return { ...e };
  },

  async crearEjecucion(input: EjecucionInput): Promise<EjecucionCreada> {
    await delay();
    requiereSesion();
    // RF-EJEC-030 / RN-001: solo sobre un evento existente y no cancelado.
    const ev = eventos.find((x) => x.id_evento_programado === input.id_evento_programado);
    if (!ev) err(422, "Datos inválidos.", { id_evento_programado: "El evento programado no existe." });
    if (!enAmbito(ev.id_actividad)) err(403, "Fuera de su ámbito de institución.");
    if (ev.estatus === "cancelado") err(409, "El evento está cancelado; no admite ejecución.");
    // RF-EJEC-032 / RN-021: bloquear actividades tipo E.
    const act = actividadDe(ev.id_actividad)!;
    if (act.tipo_registro === "E")
      err(422, "Datos inválidos.", { id_actividad: "Las actividades tipo E no se ejecutan; usa productos/entregables." });
    // RF-EJEC-031: estado calculado en servidor según completitud.
    const tieneDatosValidacion =
      !!input.fecha_ejecucion_real &&
      !!input.resumen_narrativo &&
      input.resumen_narrativo.trim().length >= 15 &&
      !!input.evidencia_url;
    let control: Ejecucion["control_registro"];
    if (input.tipo_registro_participacion === "Agregado") control = "AGREGADO";
    else control = tieneDatosValidacion ? "OK" : "INCOMPLETO";

    const nombreArchivo = input.evidencia_url
      ? generarNombreEvidencia(ev.id_evento_programado, ev.id_actividad, "pdf")
      : null;
    const nueva: Ejecucion = {
      id_ejecucion: nextId(ejecuciones, "id_ejecucion"),
      id_evento_programado: input.id_evento_programado,
      fecha_ejecucion_real: input.fecha_ejecucion_real ?? null,
      hora_inicio_real: input.hora_inicio_real ?? null,
      hora_finalizacion_real: input.hora_finalizacion_real ?? null,
      lugar_real: input.lugar_real ?? null,
      colonia_real: input.colonia_real ?? null,
      responsable_real: input.responsable_real ?? null,
      estatus_ejecucion: input.estatus_ejecucion ?? "ejecutada",
      tipo_registro_participacion: input.tipo_registro_participacion,
      total_participantes: null,
      evidencia_url: input.evidencia_url ?? null,
      nombre_archivo_evidencia: nombreArchivo,
      resumen_narrativo: input.resumen_narrativo ?? null,
      control_registro: control,
      observaciones: input.observaciones ?? null,
    };
    ejecuciones.push(nueva);
    if (ev.estatus === "programado") ev.estatus = "ejecutado";
    auditar("ejecuciones", String(nueva.id_ejecucion), "alta", null, { control_registro: control });
    return { id_ejecucion: nueva.id_ejecucion, control_registro: control, nombre_archivo_evidencia: nombreArchivo };
  },

  async validarEjecucion(id: number, input: ValidacionInput): Promise<Ejecucion> {
    await delay();
    const s = requiereSesion();
    const e = ejecuciones.find((x) => x.id_ejecucion === id);
    if (!e) err(404, "Ejecución inexistente.");
    const destino = input.control_registro;
    if (!(TRANSICIONES[e.control_registro] ?? []).includes(destino))
      err(409, `Transición ilegal: ${e.control_registro} → ${destino}.`);
    // REVISAR→OK solo coordinación (SRS §4.2).
    if (e.control_registro === "REVISAR" && destino === "OK" && s.rol !== "coordinacion")
      err(403, "Solo coordinación puede validar un registro en REVISAR.");
    const antes = { control_registro: e.control_registro };
    e.control_registro = destino;
    auditar("ejecuciones", String(id), "validacion", antes, { control_registro: destino, detalle: input.detalle ?? null });
    return { ...e };
  },

  /* ====== Participación y dedup ====== */
  async listarParticipaciones(idEjecucion: number, p?: PageParams): Promise<Paged<Participacion>> {
    await delay();
    requiereSesion();
    const rows = participaciones.filter((x) => x.id_ejecucion === idEjecucion);
    return paginar(rows, p?.page, p?.limit);
  },

  async crearParticipacion(input: ParticipacionInput): Promise<ParticipacionCreada> {
    await delay();
    requiereSesion();
    // RF-PART-040 / RN-002: solo sobre una ejecución existente.
    const ej = ejecuciones.find((x) => x.id_ejecucion === input.id_ejecucion);
    if (!ej) err(422, "Datos inválidos.", { id_ejecucion: "La ejecución no existe." });

    // RF-PART-045: validación de campos obligatorios.
    const faltantes: Record<string, string> = {};
    if (!input.apellido_paterno?.trim()) faltantes.apellido_paterno = "El apellido paterno es obligatorio.";
    if (!input.sexo) faltantes.sexo = "El sexo es obligatorio.";
    if (!input.telefono?.trim()) faltantes.telefono = "El teléfono es obligatorio.";
    if (!input.colonia_persona?.trim()) faltantes.colonia_persona = "La colonia es obligatoria.";
    if (input.anio_nacimiento != null && (input.anio_nacimiento < 1900 || input.anio_nacimiento > 2026))
      faltantes.anio_nacimiento = "El año de nacimiento debe estar entre 1900 y 2026.";
    const incompleto = Object.keys(faltantes).length > 0;
    if (incompleto && (!input.apellido_paterno || !input.telefono))
      err(422, "Datos inválidos.", faltantes);

    // RF-PART-041/042: clave de dedup y asignación de id_persona en servidor.
    const clave = calcularClaveDedup(input);
    const personaExistente = personas.find((pp) => pp.id_datosbeneficiario === clave);

    // Telefono compartido con persona de clave distinta -> sospecha de duplicado (RN-044/063).
    const telDigits = input.telefono.replace(/\D/g, "");
    const choquePorTelefono = personas.find(
      (pp) => (pp.telefono ?? "").replace(/\D/g, "") === telDigits && pp.id_datosbeneficiario !== clave,
    );

    let idPersona: string | null = null;
    let alerta: Participacion["alerta_duplicado"] = "OK";
    let control: Participacion["control_registro"];
    let detalle: string | null = null;

    if (incompleto) {
      control = "INCOMPLETO";
      detalle = Object.values(faltantes).join(" ");
    } else if (personaExistente) {
      idPersona = personaExistente.id_persona; // misma persona, consolida
      personaExistente.total_participaciones += 1;
      control = "OK";
    } else if (choquePorTelefono) {
      alerta = "DUPLICADO_EN_CAPTURA";
      control = "REVISAR";
      detalle = `Mismo teléfono que ${choquePorTelefono.id_persona} (${choquePorTelefono.nombre_completo}). Revisar.`;
    } else {
      // clave nueva -> nace una persona
      const idNuevo = `PER_${String(nextId(personas.map((x) => ({ n: Number(x.id_persona.slice(4)) })), "n")).padStart(5, "0")}`;
      idPersona = idNuevo;
      personas.push({
        id_persona: idNuevo,
        nombres: input.nombres,
        apellido_paterno: input.apellido_paterno,
        apellido_materno: input.apellido_materno ?? null,
        nombre_completo: `${input.nombres} ${input.apellido_paterno} ${input.apellido_materno ?? ""}`.trim(),
        anio_nacimiento: input.anio_nacimiento ?? null,
        sexo: input.sexo,
        telefono: input.telefono,
        correo: input.correo ?? null,
        colonia: input.colonia_persona,
        id_datosbeneficiario: clave,
        primera_participacion: ej.fecha_ejecucion_real,
        total_participaciones: 1,
        control_registro: "OK",
        decision_coordinacion: null,
      });
      control = "OK";
    }

    const nueva: Participacion = {
      id_participacion: nextId(participaciones, "id_participacion"),
      id_ejecucion: input.id_ejecucion,
      id_persona: idPersona,
      nombres: input.nombres,
      apellido_paterno: input.apellido_paterno,
      apellido_materno: input.apellido_materno ?? null,
      anio_nacimiento: input.anio_nacimiento ?? null,
      sexo: input.sexo,
      telefono: input.telefono,
      correo: input.correo ?? null,
      colonia_persona: input.colonia_persona,
      id_datosbeneficiario: clave,
      alerta_duplicado: alerta,
      fecha_participacion: ej.fecha_ejecucion_real,
      control_registro: control,
      control_automatico: control === "REVISAR" ? "REVISAR" : control === "INCOMPLETO" ? "INCOMPLETO" : "OK",
      decision_coordinacion: null,
      detalle_validacion: detalle,
    };
    participaciones.push(nueva);
    auditar("participaciones", String(nueva.id_participacion), "alta", null, {
      id_persona: idPersona,
      control_registro: control,
      alerta_duplicado: alerta,
    });
    return { id_participacion: nueva.id_participacion, id_persona: idPersona, control_registro: control, alerta_duplicado: alerta };
  },

  async crearAgregada(input: ParticipacionAgregadaInput): Promise<ParticipacionAgregada> {
    await delay();
    requiereSesion();
    const ej = ejecuciones.find((x) => x.id_ejecucion === input.id_ejecucion);
    if (!ej) err(422, "Datos inválidos.", { id_ejecucion: "La ejecución no existe." });
    const ev = eventos.find((x) => x.id_evento_programado === ej.id_evento_programado)!;
    const act = actividadDe(ev.id_actividad)!;
    // RF-AGRE-051: periodo_corte obligatorio en casos A/B.
    if ((act.caso_excepcional === "A" || act.caso_excepcional === "B") && !input.periodo_corte)
      err(422, "Datos inválidos.", { periodo_corte: "El periodo de corte es obligatorio en casos A/B." });
    if (input.cantidad_participantes < 0)
      err(422, "Datos inválidos.", { cantidad_participantes: "La cantidad no puede ser negativa." });
    const nueva: ParticipacionAgregada = {
      id_participacion_agregada: nextId(agregadas, "id_participacion_agregada"),
      id_ejecucion: input.id_ejecucion,
      tipo_registro_participacion: "Agregado",
      sexo_grupo: input.sexo_grupo ?? null,
      grupo_edad_aprox: input.grupo_edad_aprox ?? null,
      cantidad_participantes: input.cantidad_participantes,
      motivo_no_nominal: input.motivo_no_nominal ?? null,
      fuente_conteo: input.fuente_conteo ?? null,
      periodo_corte: input.periodo_corte ?? null,
      evidencia_url: null,
      control_registro: "AGREGADO",
    };
    agregadas.push(nueva);
    auditar("participaciones_agregadas", String(nueva.id_participacion_agregada), "alta", null, { cantidad: nueva.cantidad_participantes });
    return nueva;
  },

  async listarPersonas(p): Promise<Paged<Persona>> {
    await delay();
    requiereRol("coordinacion", "administrador"); // RF-PART (GET /personas solo coordinación)
    const rows = personas.filter((x) => (p?.control ? x.control_registro === p.control : true));
    return paginar(rows, p?.page, p?.limit);
  },

  async colaDuplicados(p): Promise<Paged<DuplicadoEnCola>> {
    await delay();
    requiereRol("coordinacion");
    const rows: DuplicadoEnCola[] = participaciones
      .filter((x) => x.alerta_duplicado === "DUPLICADO_EN_CAPTURA" || x.control_registro === "REVISAR")
      .map((x) => {
        const telDigits = (x.telefono ?? "").replace(/\D/g, "");
        const sug = personas.find((pp) => (pp.telefono ?? "").replace(/\D/g, "") === telDigits);
        return {
          id_participacion: x.id_participacion,
          nombres: x.nombres,
          apellido_paterno: x.apellido_paterno,
          id_persona_sugerida: sug?.id_persona ?? null,
          score_similitud: sug ? 0.94 : 0.6,
          motivo: x.detalle_validacion ?? "Posible duplicado en captura.",
        };
      })
      .sort((a, b) => b.score_similitud - a.score_similitud);
    return paginar(rows, p?.page, p?.limit);
  },

  async resolverDuplicado(idParticipacion: number, input: ResolucionDuplicadoInput): Promise<Participacion> {
    await delay();
    requiereRol("coordinacion");
    const par = participaciones.find((x) => x.id_participacion === idParticipacion);
    if (!par) err(404, "Participación inexistente.");
    if (input.accion === "fusionar") {
      if (!input.id_persona_destino || !personas.some((pp) => pp.id_persona === input.id_persona_destino))
        err(409, "La persona destino no existe.");
      par.id_persona = input.id_persona_destino!;
    } else {
      // confirmar como nueva persona
      const idNuevo = `PER_${String(nextId(personas.map((x) => ({ n: Number(x.id_persona.slice(4)) })), "n")).padStart(5, "0")}`;
      par.id_persona = idNuevo;
      personas.push({
        id_persona: idNuevo,
        nombres: par.nombres,
        apellido_paterno: par.apellido_paterno,
        apellido_materno: par.apellido_materno,
        nombre_completo: `${par.nombres} ${par.apellido_paterno}`.trim(),
        anio_nacimiento: par.anio_nacimiento,
        sexo: par.sexo,
        telefono: par.telefono,
        correo: par.correo,
        colonia: par.colonia_persona,
        id_datosbeneficiario: par.id_datosbeneficiario,
        primera_participacion: par.fecha_participacion,
        total_participaciones: 1,
        control_registro: "OK",
        decision_coordinacion: input.motivo,
      });
    }
    const antes = { control_registro: par.control_registro, alerta_duplicado: par.alerta_duplicado };
    par.alerta_duplicado = "OK";
    par.control_registro = "OK";
    par.decision_coordinacion = "OK";
    par.detalle_validacion = input.motivo;
    auditar("participaciones", String(idParticipacion), "validacion", antes, {
      accion: input.accion,
      id_persona: par.id_persona,
      control_registro: "OK",
    });
    return { ...par };
  },

  /* ====== Productos / entregables ====== */
  async crearProducto(input: ProductoInput): Promise<ProductoEntregable> {
    await delay();
    requiereSesion();
    const act = actividadDe(input.id_actividad);
    if (!act) err(422, "Datos inválidos.", { id_actividad: "Actividad inexistente." });
    // RF-PROD-060 / RN-020: solo actividades tipo E.
    if (act.tipo_registro !== "E")
      err(422, "Datos inválidos.", { id_actividad: "Solo las actividades tipo E admiten productos/entregables." });
    const completo = !!input.nombre_producto && !!input.estatus && !!input.evidencia_url;
    const nuevo: ProductoEntregable = {
      id_producto: nextId(productos, "id_producto"),
      id_actividad: input.id_actividad,
      nombre_producto: input.nombre_producto,
      tipo_producto: input.tipo_producto ?? null,
      fecha_inicio: input.fecha_inicio ?? null,
      fecha_entrega: input.fecha_entrega ?? null,
      responsable: input.responsable ?? null,
      cantidad: input.cantidad ?? null,
      unidad_medida: input.unidad_medida ?? null,
      estatus: input.estatus ?? "en_proceso",
      descripcion: input.descripcion ?? null,
      evidencia_url: input.evidencia_url ?? null,
      nombre_archivo_evidencia: input.evidencia_url ? generarNombreEvidencia(null, input.id_actividad, "pdf") : null,
      control_registro: completo ? "OK" : "INCOMPLETO",
    };
    productos.push(nuevo);
    auditar("productos_entregables", String(nuevo.id_producto), "alta", null, { control_registro: nuevo.control_registro });
    return nuevo;
  },

  /* ====== Metas y seguimiento ====== */
  async listarMetas(p): Promise<Paged<Meta>> {
    await delay();
    requiereSesion();
    const rows = metas.filter((m) => (p?.id_actividad ? m.id_actividad === p.id_actividad : true));
    return paginar(rows, p?.page, p?.limit);
  },

  async crearMeta(input: MetaInput): Promise<Meta> {
    await delay();
    requiereRol("coordinacion");
    if (!actividadDe(input.id_actividad)) err(422, "Datos inválidos.", { id_actividad: "Actividad inexistente." });
    const nueva: Meta = {
      id_meta: nextId(metas, "id_meta"),
      id_actividad: input.id_actividad,
      unidad_meta: input.unidad_meta ?? null,
      unidad_especifica: input.unidad_especifica ?? null,
      meta_anual_total: input.meta_anual_total ?? null,
      observaciones: input.observaciones ?? null,
    };
    metas.push(nueva);
    input.mensuales.forEach((mm) =>
      metasMensuales.push({ id_meta_mensual: nextId(metasMensuales, "id_meta_mensual"), id_meta: nueva.id_meta, mes: mm.mes, valor: mm.valor }),
    );
    auditar("metas", String(nueva.id_meta), "alta", null, { ...nueva });
    return nueva;
  },

  async seguimientoMetas(p): Promise<SeguimientoMeta[]> {
    await delay();
    requiereSesion();
    // Espejo de vw_seguimiento_metas (doc 03 §4.1): avance real solo control=OK.
    const rows: SeguimientoMeta[] = [];
    for (const m of metas) {
      const act = actividadDe(m.id_actividad);
      if (!act) continue;
      if (p?.eje && act.id_eje !== p.eje) continue;
      if (p?.institucion && act.id_institucion !== p.institucion) continue;
      const mmRows = metasMensuales.filter((mm) => mm.id_meta === m.id_meta).filter((mm) => (p?.periodo ? mm.mes === p.periodo : true));
      for (const mm of mmRows) {
        const avance = avanceRealActividadMes(m.id_actividad, mm.mes);
        rows.push({
          id_actividad: m.id_actividad,
          tipo_registro: act.tipo_registro,
          caso_excepcional: act.caso_excepcional,
          mes: mm.mes,
          meta_mes: mm.valor,
          avance_mes: avance,
          porcentaje: mm.valor === 0 ? null : Math.round((avance / mm.valor) * 1000) / 10,
          semaforo: calcularSemaforo(act.tipo_registro, act.caso_excepcional, mm.valor, avance),
        });
      }
    }
    return rows;
  },

  /* ====== Resultados ====== */
  async crearResultado(input): Promise<Resultado> {
    await delay();
    requiereSesion();
    const act = actividadDe(input.id_actividad);
    if (!act) err(422, "Datos inválidos.", { id_actividad: "Actividad inexistente." });
    if (act.tipo_registro !== "R")
      err(422, "Datos inválidos.", { id_actividad: "Solo las actividades tipo R admiten resultados." });
    const nuevo: Resultado = { id_resultado: nextId(resultados, "id_resultado"), ...input };
    resultados.push(nuevo);
    auditar("resultados", String(nuevo.id_resultado), "alta", null, { indicador: nuevo.indicador });
    return nuevo;
  },

  /* ====== Gobernanza ====== */
  async listarSolicitudes(p): Promise<Paged<Solicitud>> {
    await delay();
    requiereSesion();
    const rows = solicitudes.filter((s) => (p?.estado ? s.estado === p.estado : true));
    return paginar(rows, p?.page, p?.limit);
  },

  async crearSolicitud(input: SolicitudInput): Promise<Solicitud> {
    await delay();
    const s = requiereSesion();
    const u = usuarios.find((x) => x.id_usuario === s.user_id)!;
    const nueva: Solicitud = {
      id_solicitud: nextId(solicitudes, "id_solicitud"),
      fecha_solicitud: NOW.replace("T", " ").slice(0, 19),
      id_solicitante: s.user_id,
      rol_solicitante: s.rol,
      entidad_afectada: input.entidad_afectada ?? null,
      descripcion: input.descripcion,
      tipo_solicitud: input.tipo_solicitud,
      nivel_criticidad: input.nivel_criticidad ?? "MEDIA",
      impacto: input.impacto ?? null,
      estado: "en_revision",
      responsable_atencion: null,
      fecha_resolucion: null,
      comentarios: null,
    };
    void u;
    solicitudes.push(nueva);
    auditar("solicitudes", String(nueva.id_solicitud), "alta", null, { estado: "en_revision" });
    return nueva;
  },

  async resolverSolicitud(id: number, input: SolicitudPatchInput): Promise<Solicitud> {
    await delay();
    requiereRol("coordinacion");
    const sol = solicitudes.find((x) => x.id_solicitud === id);
    if (!sol) err(404, "Solicitud inexistente.");
    const antes = { estado: sol.estado };
    sol.estado = input.estado;
    sol.responsable_atencion = input.responsable_atencion ?? sol.responsable_atencion;
    sol.comentarios = input.comentarios ?? sol.comentarios;
    sol.fecha_resolucion = input.estado === "resuelta" ? NOW.replace("T", " ").slice(0, 19) : sol.fecha_resolucion;
    auditar("solicitudes", String(id), "edicion", antes, { estado: sol.estado });
    return { ...sol };
  },

  async listarAuditoria(p): Promise<Paged<Auditoria>> {
    await delay();
    requiereRol("coordinacion", "administrador", "direccion");
    const rows = auditoria.filter((a) => (p?.entidad ? a.entidad === p.entidad : true));
    return paginar(rows, p?.page, p?.limit);
  },

  async nombreEvidencia(p): Promise<{ nombre: string }> {
    await delay(100);
    requiereSesion();
    return { nombre: generarNombreEvidencia(p.id_evento, p.id_actividad, p.ext) };
  },

  /* ====== Tableros ====== */
  async tablero(tipo: TipoTablero, _p?: FiltrosComunes): Promise<TableroEjecutivo> {
    await delay();
    const s = requiereSesion();
    void tipo;
    const ambito = s.ambito;
    const actEnAmbito = (idAct: string) => ambito.includes(institucionDeActividad(idAct) ?? "");
    const eventoDe = (idEjec: number) => {
      const ej = ejecuciones.find((x) => x.id_ejecucion === idEjec);
      return ej ? eventos.find((e) => e.id_evento_programado === ej.id_evento_programado) : undefined;
    };

    // Beneficiarios únicos: personas OK referidas por participaciones OK en ámbito (vw_beneficiarios_unicos).
    const personasOk = new Set<string>();
    let nominales = 0;
    participaciones
      .filter((par) => par.control_registro === "OK" && par.id_persona)
      .filter((par) => {
        const ev = eventoDe(par.id_ejecucion);
        return ev ? actEnAmbito(ev.id_actividad) : false;
      })
      .forEach((par) => {
        nominales += 1;
        const per = personas.find((pp) => pp.id_persona === par.id_persona && pp.control_registro === "OK");
        if (per) personasOk.add(per.id_persona);
      });

    const agregadasTotal = agregadas
      .filter((a) => {
        const ev = eventoDe(a.id_ejecucion);
        return ev ? actEnAmbito(ev.id_actividad) : false;
      })
      .reduce((acc, a) => acc + a.cantidad_participantes, 0);

    const eventosAmbito = eventos.filter((e) => actEnAmbito(e.id_actividad));
    const ejecucionesReales = ejecuciones.filter((e) => {
      const ev = eventoDe(e.id_ejecucion);
      return ev && actEnAmbito(ev.id_actividad) && e.fecha_ejecucion_real != null;
    });

    const eventosProgramados = eventosAmbito.length;
    const ejecCount = ejecucionesReales.length;
    return {
      beneficiarios_unicos: personasOk.size,
      participaciones_nominales: nominales,
      participaciones_agregadas: agregadasTotal,
      cobertura_total: nominales + agregadasTotal,
      eventos_programados: eventosProgramados,
      ejecuciones: ejecCount,
      cumplimiento_ejecucion: eventosProgramados === 0 ? 0 : Math.round((ejecCount / eventosProgramados) * 100) / 100,
    };
  },

  /* ====== Incidencia (Fase 3) ====== */
  async listarPropuestasIncidencia(p): Promise<Paged<PropuestaIncidencia>> {
    await delay();
    const s = requiereSesion();
    const rows = propuestasIncidencia.filter((x) => s.ambito.includes(institucionDeActividad(x.id_actividad) ?? ""));
    return paginar(rows, p?.page, p?.limit);
  },

  async crearPropuestaIncidencia(input: PropuestaIncidenciaInput): Promise<PropuestaIncidencia> {
    await delay();
    requiereSesion();
    if (!actividadDe(input.id_actividad)) err(422, "Datos inválidos.", { id_actividad: "Actividad inexistente." });
    if (!enAmbito(input.id_actividad)) err(403, "Fuera de su ámbito de institución.");
    const nueva: PropuestaIncidencia = {
      id_propuesta: nextId(propuestasIncidencia, "id_propuesta"),
      nombre_propuesta: input.nombre_propuesta,
      promotor_colectivo: input.promotor_colectivo ?? null,
      tipo_actor: input.tipo_actor ?? null,
      fecha_inicio_asesoria: input.fecha_inicio_asesoria ?? null,
      responsable_equipo: input.responsable_equipo ?? null,
      sesiones_documentadas: input.sesiones_documentadas ?? null,
      mejora_documentada: input.mejora_documentada ?? false,
      cambios_resultado_asesoria: input.cambios_resultado_asesoria ?? null,
      evidencia_principal: input.evidencia_principal ?? null,
      alineada_proyectos_estrategicos: input.alineada_proyectos_estrategicos ?? false,
      criterios_alineacion_nota: input.criterios_alineacion_nota ?? null,
      estatus: input.estatus ?? "activo",
      elegible_reporte: input.elegible_reporte ?? false,
      id_actividad: input.id_actividad,
      periodo_reporte: input.periodo_reporte ?? null,
      control_registro: "CAPTURADO",
    };
    propuestasIncidencia.push(nueva);
    auditar("propuestas_incidencia", String(nueva.id_propuesta), "alta", null, { control_registro: nueva.control_registro });
    return nueva;
  },

  async listarProcesosIncidencia(p): Promise<Paged<ProcesoIncidencia>> {
    await delay();
    const s = requiereSesion();
    const rows = procesosIncidencia.filter((x) => s.ambito.includes(institucionDeActividad(x.id_actividad) ?? ""));
    return paginar(rows, p?.page, p?.limit);
  },

  async crearProcesoIncidencia(input: ProcesoIncidenciaInput): Promise<ProcesoIncidencia> {
    await delay();
    requiereSesion();
    if (!actividadDe(input.id_actividad)) err(422, "Datos inválidos.", { id_actividad: "Actividad inexistente." });
    if (!enAmbito(input.id_actividad)) err(403, "Fuera de su ámbito de institución.");
    const nuevo: ProcesoIncidencia = {
      id_proceso_incidencia: nextId(procesosIncidencia, "id_proceso_incidencia"),
      nombre: input.nombre,
      criterios_elegibilidad: input.criterios_elegibilidad ?? null,
      ultimo_hito_resumen: input.ultimo_hito_resumen ?? null,
      control_registro: "CAPTURADO",
      id_actividad: input.id_actividad,
    };
    procesosIncidencia.push(nuevo);
    auditar("procesos_incidencia", String(nuevo.id_proceso_incidencia), "alta", null, { control_registro: nuevo.control_registro });
    return nuevo;
  },

  async listarCompromisos(p): Promise<Paged<Compromiso>> {
    await delay();
    const s = requiereSesion();
    const rows = compromisos.filter((c) => s.ambito.includes(institucionDeProcesoIncidencia(c.id_proceso_incidencia) ?? ""));
    return paginar(rows, p?.page, p?.limit);
  },

  async crearCompromiso(input: CompromisoInput): Promise<Compromiso> {
    await delay();
    const s = requiereSesion();
    const inst = institucionDeProcesoIncidencia(input.id_proceso_incidencia);
    if (!inst) err(422, "Datos inválidos.", { id_proceso_incidencia: "El proceso de incidencia no existe." });
    if (!s.ambito.includes(inst)) err(403, "Fuera de su ámbito de institución.");
    const nuevo: Compromiso = {
      id_compromiso: nextId(compromisos, "id_compromiso"),
      id_proceso_incidencia: input.id_proceso_incidencia,
      identificacion: input.identificacion ?? null,
      seguimiento_documentado: input.seguimiento_documentado ?? null,
      criterios_elegibilidad: input.criterios_elegibilidad ?? null,
      control_registro: "CAPTURADO",
    };
    compromisos.push(nuevo);
    auditar("compromisos", String(nuevo.id_compromiso), "alta", null, { control_registro: nuevo.control_registro });
    return nuevo;
  },

  async listarAlianzas(p): Promise<Paged<Alianza>> {
    await delay();
    const s = requiereSesion();
    const rows = alianzas.filter((a) => s.ambito.includes(institucionDeActividad(a.id_actividad) ?? ""));
    return paginar(rows, p?.page, p?.limit);
  },

  async crearAlianza(input: AlianzaInput): Promise<Alianza> {
    await delay();
    requiereSesion();
    if (!actividadDe(input.id_actividad)) err(422, "Datos inválidos.", { id_actividad: "Actividad inexistente." });
    if (!enAmbito(input.id_actividad)) err(403, "Fuera de su ámbito de institución.");
    const nueva: Alianza = {
      id_alianza: nextId(alianzas, "id_alianza"),
      nombre_alianza: input.nombre_alianza,
      datos_alianza: input.datos_alianza ?? null,
      criterios_elegibilidad: input.criterios_elegibilidad ?? null,
      id_actividad: input.id_actividad,
      control_registro: "CAPTURADO",
    };
    alianzas.push(nueva);
    auditar("alianzas", String(nueva.id_alianza), "alta", null, { control_registro: nueva.control_registro });
    return nueva;
  },

  async listarHitos(p): Promise<Paged<HitoIncidencia>> {
    await delay();
    const s = requiereSesion();
    const rows = hitosIncidencia.filter((h) => s.ambito.includes(institucionDeProcesoIncidencia(h.id_proceso_incidencia) ?? ""));
    return paginar(rows, p?.page, p?.limit);
  },

  async crearHito(input: HitoIncidenciaInput): Promise<HitoIncidencia> {
    await delay();
    const s = requiereSesion();
    const inst = institucionDeProcesoIncidencia(input.id_proceso_incidencia);
    if (!inst) err(422, "Datos inválidos.", { id_proceso_incidencia: "El proceso de incidencia no existe." });
    if (!s.ambito.includes(inst)) err(403, "Fuera de su ámbito de institución.");
    const nuevo: HitoIncidencia = {
      id_hito: nextId(hitosIncidencia, "id_hito"),
      id_proceso_incidencia: input.id_proceso_incidencia,
      fecha_hito: input.fecha_hito ?? null,
      tipo_hito: input.tipo_hito ?? null,
      descripcion_hito: input.descripcion_hito ?? null,
      evidencia_nombre_o_nota: input.evidencia_nombre_o_nota ?? null,
      registrado_por: sesionActual?.user_id ?? null,
      observaciones: input.observaciones ?? null,
    };
    hitosIncidencia.push(nuevo);
    auditar("hitos_incidencia", String(nuevo.id_hito), "alta", null, { tipo_hito: nuevo.tipo_hito });
    return nuevo;
  },

  /* ====== Verticales (Fase 3) ====== */
  async listarOcupacionShelter(p): Promise<Paged<OcupacionShelter>> {
    await delay();
    const s = requiereSesion();
    const rows = ocupacionShelter
      .filter((o) => s.ambito.includes(institucionDeActividad(o.id_actividad) ?? ""))
      .map(conPctOcupacion);
    return paginar(rows, p?.page, p?.limit);
  },

  async crearOcupacionShelter(input: OcupacionShelterInput): Promise<OcupacionShelter> {
    await delay();
    requiereSesion();
    if (!actividadDe(input.id_actividad)) err(422, "Datos inválidos.", { id_actividad: "Actividad inexistente." });
    if (!enAmbito(input.id_actividad)) err(403, "Fuera de su ámbito de institución.");
    const nueva: OcupacionShelter = {
      id_ocupacion: nextId(ocupacionShelter, "id_ocupacion"),
      id_actividad: input.id_actividad,
      mes_periodo: input.mes_periodo,
      tipo_espacio: input.tipo_espacio ?? null,
      capacidad_instalada: input.capacidad_instalada,
      ocupacion: input.ocupacion,
      fuente: input.fuente ?? null,
      control_registro: "AGREGADO",
    };
    ocupacionShelter.push(nueva);
    auditar("ocupacion_shelter", String(nueva.id_ocupacion), "alta", null, { control_registro: "AGREGADO" });
    return conPctOcupacion(nueva);
  },

  async listarSostenibilidad(p): Promise<Paged<SostenibilidadFinanciera>> {
    await delay();
    const s = requiereSesion();
    const rows = sostenibilidad
      .filter((x) => s.ambito.includes(institucionDeActividad(x.id_actividad) ?? ""))
      .map(conIndicadoresFinancieros);
    return paginar(rows, p?.page, p?.limit);
  },

  async crearSostenibilidad(input: SostenibilidadInput): Promise<SostenibilidadFinanciera> {
    await delay();
    requiereSesion();
    if (!actividadDe(input.id_actividad)) err(422, "Datos inválidos.", { id_actividad: "Actividad inexistente." });
    if (!enAmbito(input.id_actividad)) err(403, "Fuera de su ámbito de institución.");
    const nuevo: SostenibilidadFinanciera = {
      id_registro: nextId(sostenibilidad, "id_registro"),
      id_actividad: input.id_actividad,
      mes_periodo: input.mes_periodo,
      ingresos_brutos: input.ingresos_brutos ?? 0,
      costos_directos: input.costos_directos ?? 0,
      costos_indirectos: input.costos_indirectos ?? 0,
      recursos_efectivo: input.recursos_efectivo ?? 0,
      recursos_especie: input.recursos_especie ?? 0,
      fuente_datos: input.fuente_datos ?? null,
      meta_anual: input.meta_anual ?? 0,
      control_registro: "AGREGADO",
    };
    sostenibilidad.push(nuevo);
    auditar("sostenibilidad_financiera", String(nuevo.id_registro), "alta", null, { control_registro: "AGREGADO" });
    return conIndicadoresFinancieros(nuevo);
  },
};

/* ---------------------------------------------------------------------
 * Helpers de seguimiento de metas (fuera del objeto para legibilidad)
 * ------------------------------------------------------------------- */

/** Avance real de una actividad en un mes POA (solo control=OK), nominales + agregadas. */
function avanceRealActividadMes(idActividad: string, mes: MesPOA): number {
  const evIds = eventos.filter((e) => e.id_actividad === idActividad).map((e) => e.id_evento_programado);
  const ejecIds = ejecuciones.filter((e) => evIds.includes(e.id_evento_programado)).map((e) => e.id_ejecucion);
  const mesNum = Number(mes.slice(1)); // M06 -> 6 (mapeo simple demo; el backend mapea al ciclo POA real)
  const nominal = participaciones
    .filter((par) => ejecIds.includes(par.id_ejecucion) && par.control_registro === "OK")
    .filter((par) => par.fecha_participacion && new Date(par.fecha_participacion).getUTCMonth() + 1 === mesNum).length;
  const agregado = agregadas
    .filter((a) => ejecIds.includes(a.id_ejecucion) && a.periodo_corte === mes)
    .reduce((acc, a) => acc + a.cantidad_participantes, 0);
  return nominal + agregado;
}

/** Semáforo (espejo del CASE de vw_seguimiento_metas). RF-META-071/072, RF-AGRE-053. */
function calcularSemaforo(
  tipo: TipoRegistro,
  caso: CasoExcepcional | null,
  metaMes: number,
  avance: number,
): Semaforo {
  if (tipo === "R") return "FASE_3";
  if (metaMes === 0) return "SIN_META";
  if ((caso === "C" || caso === "D") && avance === 0) return "CORTE_AL_CIERRE";
  const ratio = avance / metaMes;
  if (ratio >= 0.9) return "VERDE";
  if (ratio >= 0.75) return "AMARILLO";
  return "ROJO";
}
