#!/usr/bin/env python3
"""
import_excel.py — Genera apps/web/src/lib/mock/db.json a partir del Excel
real del sistema MEL (CPJ_MEL_v1_9_seguimiento_actualizado).

Mapea cada hoja de datos a la tabla equivalente del mock, renombrando
columnas a los nombres EXACTOS de types.ts, reconciliando IDs de enlace y
coercionando enums/tipos a los catálogos permitidos (ver MAPAS abajo).

Uso:
    /tmp/xlsxenv/bin/python tools/import_excel.py

Es una herramienta de generación de una pasada. La verificación real es
`npm run typecheck && npm run build` + recorrido del demo en runtime.
"""
import json
import datetime as dt
from pathlib import Path

import openpyxl

ROOT = Path(__file__).resolve().parent.parent
XLSX = ROOT / "CPJ_MEL_v1_9_seguimiento_actualizado (12).xlsx"
DB = ROOT / "apps" / "web" / "src" / "lib" / "mock" / "db.json"

# ---------------------------------------------------------------------------
# Helpers de coerción
# ---------------------------------------------------------------------------
EXCEL_EPOCH = dt.datetime(1899, 12, 30)
MESES_POA = {f"M{n:02d}" for n in range(1, 19)}


def s(v):
    """Cadena limpia o None. Quita '.0' de floats que llegan como texto."""
    if v is None:
        return None
    if isinstance(v, float) and v.is_integer():
        v = int(v)
    out = str(v).strip()
    if out == "" or out in ("None", "#REF!", "nan"):
        return None
    return out


def num(v):
    """Número o None."""
    if v is None:
        return None
    if isinstance(v, (int, float)):
        return v
    t = str(v).strip().replace(",", "")
    if t == "" or t in ("None", "#REF!"):
        return None
    try:
        f = float(t)
        return int(f) if f.is_integer() else f
    except ValueError:
        return None


def to_int(v):
    n = num(v)
    return int(n) if n is not None else None


def id_num(v):
    """'EVP_00007' / 'EJE_00001' / 'PAR_00012' / '1.0' -> int. None si no hay dígitos."""
    if v is None:
        return None
    digits = "".join(ch for ch in str(v) if ch.isdigit())
    return int(digits) if digits else None


def to_date(v):
    """datetime, serial de Excel o texto -> 'YYYY-MM-DD'. None si vacío."""
    if v is None:
        return None
    if isinstance(v, (dt.datetime, dt.date)):
        return v.strftime("%Y-%m-%d")
    if isinstance(v, (int, float)):
        try:
            return (EXCEL_EPOCH + dt.timedelta(days=float(v))).strftime("%Y-%m-%d")
        except (OverflowError, ValueError):
            return None
    t = str(v).strip()
    if t == "" or t in ("None", "#REF!"):
        return None
    # 'YYYY-MM-DD HH:MM:SS' -> fecha
    return t.split(" ")[0]


def to_dt(v):
    """datetime -> 'YYYY-MM-DD HH:MM:SS' (para campos de fecha+hora)."""
    if v is None:
        return None
    if isinstance(v, (dt.datetime, dt.date)):
        return v.strftime("%Y-%m-%d %H:%M:%S")
    t = str(v).strip()
    return None if t in ("", "None", "#REF!") else t


def to_time(v):
    if v is None:
        return None
    if isinstance(v, dt.time):
        return v.strftime("%H:%M:%S")
    if isinstance(v, dt.datetime):
        return v.strftime("%H:%M:%S")
    t = str(v).strip()
    return None if t in ("", "None", "#REF!") else t


def mes_poa(v):
    t = s(v)
    if not t:
        return None
    t = t.upper().replace("-2026", "").replace("-2025", "").strip()
    return t if t in MESES_POA else None


def mapea(v, tabla, default=None):
    t = s(v)
    if t is None:
        return default
    return tabla.get(t.upper(), tabla.get(t, default))


# ---------------------------------------------------------------------------
# MAPAS de enum (Excel -> types.ts). Ver hallazgos del brainstorming.
# ---------------------------------------------------------------------------
ESTATUS = {"ACTIVA": "activo", "ACTIVO": "activo", "INACTIVA": "inactivo", "INACTIVO": "inactivo"}
# El Excel usa M=Mujer, H=Hombre, N=otro. types: F|M|X. (Confirmado: 'LIDIA LUZ'=M.)
SEXO = {"M": "F", "H": "M", "N": "X", "F": "F", "X": "X"}
TIPO_PART = {"NOMINAL": "Nominal", "AGREGADA": "Agregado", "AGREGADO": "Agregado", "MIXTA": "Mixta"}
EST_EVENTO = {"PROGRAMADO": "programado", "EJECUTADO": "ejecutado", "EN_EJECUCION": "ejecutado",
              "CANCELADO": "cancelado", "REPROGRAMADO": "reprogramado"}
EST_PROCESO = {"ACTIVO": "activo", "CERRADO": "concluido", "CONCLUIDO": "concluido",
               "BORRADOR": "activo", "CANCELADO": "cancelado"}
EST_EJECUCION = {"EJECUTADO": "ejecutada", "EJECUTADA": "ejecutada", "PARCIAL": "parcial",
                 "SUSPENDIDA": "suspendida"}  # CANCELADO/REPROGRAMADO -> None
EST_PRODUCTO = {"EN PROCESO": "en_proceso", "ENTREGADO": "entregado", "CANCELADO": "cancelado"}
ALERTA = {"OK": "OK", "DUPLICADO_EN_CAPTURA": "DUPLICADO_EN_CAPTURA"}
CTRL_AUTO = {"OK": "OK", "INCOMPLETO": "INCOMPLETO", "REVISAR": "REVISAR"}
CTRL_PART = {"OK": "OK", "INCOMPLETO": "INCOMPLETO", "REVISAR": "REVISAR", "CAPTURADO": "CAPTURADO", "0": "INCOMPLETO"}
CTRL_PERSONA = {"OK": "OK", "REVISAR": "REVISAR", "INCOMPLETO": "REVISAR"}
CTRL_PRODUCTO = {"OK": "OK", "INCOMPLETO": "INCOMPLETO", "CAPTURADO": "CAPTURADO"}
# ejecuciones: FALTA_VALIDACION -> INCOMPLETO; SELECCIONA_EVENTO = fila plantilla (se filtra)
CTRL_EJEC = {"OK": "OK", "FALTA_VALIDACION": "INCOMPLETO", "INCOMPLETO": "INCOMPLETO",
             "AGREGADO": "AGREGADO", "CAPTURADO": "CAPTURADO", "REVISAR": "REVISAR"}
TIPO_PROG = {"SESION_UNICA": "SESION_UNICA", "MULTI_SESION_PROGRAMADA": "MULTI_SESION_PROGRAMADA",
             "PROCESO_CONTINUO": "PROCESO_CONTINUO"}
TIPO_SOL = {"CORRECCIÓN": "correccion", "CORRECCION": "correccion", "MEJORA": "mejora", "AJUSTE": "ajuste"}
CRIT_SOL = {"BAJA": "BAJA", "MEDIA": "MEDIA", "ALTA": "ALTA"}
EST_SOL = {"EN_REVISION": "en_revision", "EN REVISIÓN": "en_revision", "EN_PROCESO": "en_proceso",
           "RESUELTA": "resuelta", "DESCARTADA": "descartada"}

# ---------------------------------------------------------------------------
# Lectura de hojas
# ---------------------------------------------------------------------------
wb = openpyxl.load_workbook(XLSX, data_only=True)


def hoja(nombre, anchor):
    """Devuelve (encabezados, filas_dict) anclando el header a la fila que
    contiene `anchor` como nombre de columna."""
    ws = wb[nombre]
    rows = list(ws.iter_rows(values_only=True))
    hi = None
    for i, r in enumerate(rows):
        cells = [str(c).strip() if c is not None else "" for c in r]
        if anchor in cells:
            hi = i
            break
    if hi is None:
        raise RuntimeError(f"No encontré la columna ancla '{anchor}' en {nombre}")
    header = [str(c).strip() if c is not None else None for c in rows[hi]]
    out = []
    for r in rows[hi + 1:]:
        if not any(c is not None and str(c).strip() != "" for c in r):
            continue
        d = {header[i]: r[i] for i in range(len(header)) if header[i]}
        out.append(d)
    return header, out


# ---------------------------------------------------------------------------
# Builders por tabla
# ---------------------------------------------------------------------------
def build_ejes():
    _, rows = hoja("🔵dim_ejes", "id_eje")
    out = []
    for r in rows:
        if not s(r.get("id_eje")):
            continue
        out.append({
            "id_eje": s(r.get("id_eje")),
            "num_eje_original": to_int(r.get("Num_eje_estrategico_original")),
            "clave_eje_corto": s(r.get("clave_eje_corto")),
            "nombre": s(r.get("nom_eje_estrategico")) or "",
            "orden_visualizacion": to_int(r.get("orden_visualizacion")) or 0,
        })
    return out


def build_lineas():
    _, rows = hoja("🟣dim_lineas", "id_linea")
    out = []
    for r in rows:
        if not s(r.get("id_linea")):
            continue
        out.append({
            "id_linea": s(r.get("id_linea")),
            "num_linea": to_int(r.get("Num_linea_de_accion")),
            "clave_linea_corta": s(r.get("clave_linea_corta")),
            "nombre": s(r.get("nom_linea")) or "",
            "id_eje": s(r.get("id_eje")) or "",
            "orden_visualizacion": to_int(r.get("orden_visualizacion")) or 0,
            "estatus": mapea(r.get("estatus_linea"), ESTATUS, "activo"),
        })
    return out


def build_instituciones():
    _, rows = hoja("🟢dim_instituciones", "id_institucion")
    out = []
    for r in rows:
        if not s(r.get("id_institucion")):
            continue
        out.append({
            "id_institucion": s(r.get("id_institucion")),
            "num_institucion_original": to_int(r.get("Num_institucion_original")),
            "nombre": s(r.get("nom_institucion")) or "",
            "estatus": mapea(r.get("estatus_institucion"), ESTATUS, "activo"),
            "orden_visualizacion": to_int(r.get("orden_visualizacion")) or 0,
        })
    return out


def build_componentes():
    _, rows = hoja("🟡dim_componentes", "id_componente")
    out = []
    for r in rows:
        if not s(r.get("id_componente")):
            continue
        out.append({
            "id_componente": s(r.get("id_componente")),
            "num_componente": to_int(r.get("Num_componente")),
            "clave_componente": s(r.get("clave_componente")),
            "nombre": s(r.get("nom_componente")) or "",
            "id_institucion": s(r.get("id_institucion")) or "",
            "orden_visualizacion": to_int(r.get("orden_visualizacion")) or 0,
            "estatus": mapea(r.get("estatus_componente"), ESTATUS, "activo"),
        })
    return out


def build_actividades():
    _, rows = hoja("🟠tabla_actividades", "id_actividad")
    out = []
    for r in rows:
        if not s(r.get("id_actividad")):
            continue
        tipo = (s(r.get("tipo_registro_actividad")) or "P").upper()
        out.append({
            "id_actividad": s(r.get("id_actividad")),
            "num_actividad": to_int(r.get("num_actividad")),
            "nombre": s(r.get("nom_actividad")) or "",
            "id_eje": s(r.get("id_eje")) or "",
            "id_linea": s(r.get("id_linea")) or "",
            "id_componente": s(r.get("id_componente")) or "",
            "id_institucion": s(r.get("id_institucion")) or "",
            "tipo_registro": tipo if tipo in ("P", "E", "R") else "P",
            "caso_excepcional": None,
        })
    return out


def build_procesos():
    _, rows = hoja("🧭cat_procesos_programados", "id_proceso")
    out = []
    for r in rows:
        idp = id_num(r.get("id_proceso"))
        if idp is None:
            continue
        out.append({
            "id_proceso": idp,
            "nombre": s(r.get("nombre_proceso / grupo")) or "",
            "tipo_programacion": mapea(r.get("tipo_actividad_calendario"), TIPO_PROG, "SESION_UNICA"),
            "id_actividad": s(r.get("id_actividad")) or "",
            "fecha_inicio": to_date(r.get("fecha_inicio_proceso")),
            "fecha_fin": to_date(r.get("fecha_fin_proceso")),
            "total_sesiones_programadas": to_int(r.get("total_sesiones_programadas")),
            "responsable": s(r.get("responsable_actividad")),
            "contacto": s(r.get("contacto")),
            "estatus": mapea(r.get("estatus_proceso"), EST_PROCESO, "activo"),
            "observaciones": s(r.get("observaciones")),
        })
    return out


def build_eventos():
    _, rows = hoja("📅calendario_programacion", "id_evento_programado")
    out = []
    for r in rows:
        ide = id_num(r.get("id_evento_programado"))
        if ide is None:
            continue
        fi = to_date(r.get("fecha_inicio"))
        out.append({
            "id_evento_programado": ide,
            "id_actividad": s(r.get("id_actividad")) or "",
            "id_proceso": id_num(r.get("id_proceso")),
            "tipo_programacion": mapea(r.get("tipo_actividad_calendario"), TIPO_PROG, "SESION_UNICA"),
            "fecha_inicio": fi or "",
            "fecha_finalizacion": to_date(r.get("fecha_finalizacion")) or fi or "",
            "hora_inicio": to_time(r.get("hora_inicio")),
            "hora_finalizacion": to_time(r.get("hora_finalizacion")),
            "modalidad": s(r.get("modalidad")),
            "lugar": s(r.get("lugar_actividad")),
            "calle_y_numero": s(r.get("calle_y_numero")),
            "colonia": s(r.get("colonia")),
            "responsable": s(r.get("responsable_actividad")),
            "contacto": s(r.get("contacto")),
            "estatus": mapea(r.get("estatus"), EST_EVENTO, "programado"),
            "num_sesion": to_int(r.get("num_sesion")),
            "total_sesiones": to_int(r.get("total_sesiones")),
            "observaciones": s(r.get("observaciones")),
        })
    return out


def build_ejecuciones():
    _, rows = hoja("✅actividades_ejecutadas", "id_evento_ejecutado")
    out = []
    for r in rows:
        ctrl_raw = (s(r.get("control_registro")) or "").upper()
        # Filas plantilla del Excel: no son ejecuciones reales.
        if ctrl_raw == "SELECCIONA_EVENTO" or not ctrl_raw:
            continue
        idej = id_num(r.get("id_evento_ejecutado"))
        if idej is None:
            continue
        out.append({
            "id_ejecucion": idej,
            "id_evento_programado": id_num(r.get("id_evento_programado")) or 0,
            "fecha_ejecucion_real": to_date(r.get("fecha_ejecucion_real")),
            "hora_inicio_real": to_time(r.get("hora_inicio_real")),
            "hora_finalizacion_real": to_time(r.get("hora_finalizacion_real")),
            "lugar_real": s(r.get("lugar_actividad_real")),
            "colonia_real": s(r.get("colonia_real")),
            "responsable_real": s(r.get("responsable_actividad_real")),
            "estatus_ejecucion": mapea(r.get("estatus_ejecucion"), EST_EJECUCION, None),
            "tipo_registro_participacion": mapea(
                r.get("tipo_registro_participacion_evento"), TIPO_PART, "Nominal"),
            "total_participantes": to_int(r.get("total_participantes")),
            "evidencia_url": s(r.get("evidencia_url")),
            "nombre_archivo_evidencia": s(r.get("nombre_archivo_evidencia")),
            "resumen_narrativo": s(r.get("resumen_narrativo_actividad")),
            "control_registro": mapea(r.get("control_registro"), CTRL_EJEC, "INCOMPLETO"),
            "observaciones": s(r.get("observaciones")),
        })
    return out


def build_participaciones():
    _, rows = hoja("🤝participacion_actividad", "id_participacion")
    out = []
    for r in rows:
        idp = id_num(r.get("id_participacion"))
        if idp is None:
            continue
        out.append({
            "id_participacion": idp,
            "id_ejecucion": id_num(r.get("id_evento_ejecutado")) or 0,
            "id_persona": s(r.get("id_persona")),
            "nombres": s(r.get("nombres")) or "",
            "apellido_paterno": s(r.get("apellido_paterno")) or "",
            "apellido_materno": s(r.get("apellido_materno")),
            "anio_nacimiento": to_int(r.get("anio_nacimiento")),
            "sexo": mapea(r.get("sexo"), SEXO, "X"),
            "telefono": s(r.get("telefono")) or "",
            "correo": s(r.get("correo")),
            "colonia_persona": s(r.get("colonia_persona")) or "",
            "id_datosbeneficiario": s(r.get("id_datosbeneficiario")) or "",
            "alerta_duplicado": mapea(r.get("alerta_duplicado"), ALERTA, "OK"),
            "fecha_participacion": to_date(r.get("fecha_participacion")),
            "control_registro": mapea(r.get("control_registro"), CTRL_PART, "OK"),
            "control_automatico": mapea(r.get("control_automatico"), CTRL_AUTO, None),
            "decision_coordinacion": mapea(r.get("decision_coordinacion"), CTRL_AUTO, None),
            "detalle_validacion": s(r.get("detalle_validacion")),
        })
    return out


def build_agregadas():
    _, rows = hoja("👥participacion_agregada", "id_participacion_agregada")
    out = []
    for r in rows:
        idp = id_num(r.get("id_participacion_agregada"))
        if idp is None:
            continue
        tipo = mapea(r.get("tipo_registro_participacion"), TIPO_PART, "Agregado")
        out.append({
            "id_participacion_agregada": idp,
            "id_ejecucion": id_num(r.get("id_evento_ejecutado")) or 0,
            "tipo_registro_participacion": tipo if tipo in ("Agregado", "Mixta") else "Agregado",
            "sexo_grupo": s(r.get("sexo_grupo")),
            "grupo_edad_aprox": s(r.get("grupo_edad_aprox")),
            "cantidad_participantes": to_int(r.get("cantidad_participantes")) or 0,
            "motivo_no_nominal": s(r.get("motivo_no_nominal")),
            "fuente_conteo": s(r.get("fuente_conteo")),
            "periodo_corte": mes_poa(r.get("periodo_corte")),
            "evidencia_url": s(r.get("evidencia_url_opcional")),
            "control_registro": "AGREGADO",
        })
    return out


def build_personas():
    _, rows = hoja("👤personas", "id_persona")
    out = []
    for r in rows:
        idp = s(r.get("id_persona"))
        if not idp:
            continue
        out.append({
            "id_persona": idp,
            "nombres": s(r.get("nombres")),
            "apellido_paterno": s(r.get("apellido_paterno")),
            "apellido_materno": s(r.get("apellido_materno")),
            "nombre_completo": s(r.get("nombre_completo")),
            "anio_nacimiento": to_int(r.get("anio_nacimiento")),
            "sexo": mapea(r.get("sexo"), SEXO, None),
            "telefono": s(r.get("telefono")),
            "correo": s(r.get("correo")),
            "colonia": s(r.get("colonia")),
            "id_datosbeneficiario": s(r.get("id_datosbeneficiario")) or "",
            "primera_participacion": to_date(r.get("primera_participacion")),
            "total_participaciones": to_int(r.get("total_participaciones")) or 0,
            "control_registro": mapea(r.get("control_registro"), CTRL_PERSONA, "OK"),
            "decision_coordinacion": s(r.get("decision_coordinacion")),
        })
    return out


def build_productos():
    _, rows = hoja("📦productos_entregables", "id_producto")
    out = []
    for r in rows:
        idp = id_num(r.get("id_producto"))
        if idp is None:
            continue
        out.append({
            "id_producto": idp,
            "id_actividad": s(r.get("id_actividad")) or "",
            "nombre_producto": s(r.get("nombre_producto")) or "",
            "tipo_producto": s(r.get("tipo_producto")),
            "fecha_inicio": to_date(r.get("fecha_inicio")),
            "fecha_entrega": to_date(r.get("fecha_entrega")),
            "responsable": s(r.get("responsable")),
            "cantidad": num(r.get("cantidad")),
            "unidad_medida": s(r.get("unidad_medida")),
            "estatus": mapea(r.get("estatus"), EST_PRODUCTO, "en_proceso"),
            "descripcion": s(r.get("descripcion")),
            "evidencia_url": s(r.get("evidencia_url")),
            "nombre_archivo_evidencia": s(r.get("nombre_archivo_evidencia")),
            "control_registro": mapea(r.get("control_registro"), CTRL_PRODUCTO, "OK"),
        })
    return out


def build_metas():
    """Hoja metas_actividades -> metas + metas_mensuales (columnas meta_M01..M18)."""
    _, rows = hoja("📊metas_actividades", "id_actividad")
    metas, mensuales = [], []
    id_meta = 0
    id_mm = 0
    for r in rows:
        ida = s(r.get("id_actividad"))
        if not ida:
            continue
        id_meta += 1
        metas.append({
            "id_meta": id_meta,
            "id_actividad": ida,
            "unidad_meta": s(r.get("unidad_meta")),
            "unidad_especifica": s(r.get("unidad_especifica")),
            "meta_anual_total": num(r.get("meta_anual_total")),
            "observaciones": s(r.get("observaciones")),
        })
        for n in range(1, 19):
            mes = f"M{n:02d}"
            val = num(r.get(f"meta_{mes}"))
            if val is None:
                continue
            id_mm += 1
            mensuales.append({"id_meta_mensual": id_mm, "id_meta": id_meta, "mes": mes, "valor": val})
    return metas, mensuales


def build_resultados(actividades):
    """No hay hoja de indicadores; se deriva una fila por actividad tipo R."""
    out = []
    i = 0
    for a in actividades:
        if a["tipo_registro"] != "R":
            continue
        i += 1
        out.append({
            "id_resultado": i,
            "id_actividad": a["id_actividad"],
            "indicador": a["nombre"],
            "linea_base": None,
            "valor_medido": None,
            "metodo_medicion": None,
            "fecha_medicion": None,
            "evidencia_url": None,
        })
    return out


def build_solicitudes():
    _, rows = hoja("🧾bitacora_solicitudes", "id_solicitud")
    out = []
    for r in rows:
        idp = id_num(r.get("id_solicitud"))
        if idp is None:
            continue
        out.append({
            "id_solicitud": idp,
            "fecha_solicitud": to_dt(r.get("fecha_solicitud")) or "",
            "id_solicitante": 13,  # Excel guarda el nombre, no el id; cuenta demo coordinación.
            "rol_solicitante": s(r.get("rol_solicitante")),
            "entidad_afectada": s(r.get("pestaña_afectada")),
            "descripcion": s(r.get("descripcion_solicitud")) or "",
            "tipo_solicitud": mapea(r.get("tipo_solicitud"), TIPO_SOL, "ajuste"),
            "nivel_criticidad": mapea(r.get("nivel_criticidad"), CRIT_SOL, "MEDIA"),
            "impacto": s(r.get("impacto")),
            "estado": mapea(r.get("estado_solicitud"), EST_SOL, "en_revision"),
            "responsable_atencion": 13 if s(r.get("responsable_atencion")) else None,
            "fecha_resolucion": to_dt(r.get("fecha_resolucion")),
            "comentarios": s(r.get("comentarios_seguimiento")),
        })
    return out


def build_usuario_institucion(instituciones):
    """Reasigna el ámbito de las cuentas demo a instituciones REALES.
    capturista(12): primera institución; coordinación(13) y dirección(14): todas."""
    ids = [i["id_institucion"] for i in instituciones]
    filas = []
    pk = 0
    def add(uid, inst):
        nonlocal pk
        pk += 1
        filas.append({"id": pk, "id_usuario": uid, "id_institucion": inst})
    if ids:
        add(12, ids[0])
    for inst in ids:
        add(13, inst)
        add(14, inst)
    return filas


# ---------------------------------------------------------------------------
# Ensamble
# ---------------------------------------------------------------------------
def main():
    prev = json.loads(DB.read_text(encoding="utf-8"))

    actividades = build_actividades()
    instituciones = build_instituciones()
    metas, mensuales = build_metas()

    # _demo: conservar metadata, actualizar nota de conciliación.
    demo = prev["_demo"]
    demo["linea_base_conciliacion"] = {
        "nota": "db.json generado desde el Excel real v1.9 (tools/import_excel.py). "
                "Contiene PII real de beneficiarios por decisión del responsable de datos. "
                "Las ejecuciones plantilla (control_registro=SELECCIONA_EVENTO) se filtran."
    }

    db = {"_demo": demo}
    db["ejes"] = build_ejes()
    db["lineas"] = build_lineas()
    db["instituciones"] = instituciones
    db["componentes"] = build_componentes()
    db["actividades"] = actividades
    db["procesos"] = build_procesos()
    db["eventos_programados"] = build_eventos()
    db["ejecuciones"] = build_ejecuciones()
    db["personas"] = build_personas()
    db["participaciones"] = build_participaciones()
    db["participaciones_agregadas"] = build_agregadas()
    db["productos_entregables"] = build_productos()
    db["metas"] = metas
    db["metas_mensuales"] = mensuales
    db["resultados"] = build_resultados(actividades)

    # Incidencia / shelter / sostenibilidad: NO los consume api.mock.ts todavía.
    # Se conservan las muestras existentes para que las claves existan y nada rompa.
    for k in ("propuestas_incidencia", "procesos_incidencia", "compromisos", "alianzas",
              "hitos_incidencia", "ocupacion_shelter", "sostenibilidad_financiera"):
        db[k] = prev.get(k, [])

    # Gobernanza: cuentas demo (Excel no trae usuarios del sistema).
    db["roles"] = prev["roles"]
    db["usuarios"] = prev["usuarios"]
    db["usuario_institucion"] = build_usuario_institucion(instituciones)
    db["solicitudes"] = build_solicitudes()
    db["auditoria"] = prev.get("auditoria", [])

    DB.write_text(json.dumps(db, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")

    # Resumen
    print("db.json generado:")
    for k, v in db.items():
        if isinstance(v, list):
            print(f"  {k:28} {len(v)}")
    print(f"\nArchivo: {DB}")


if __name__ == "__main__":
    main()
