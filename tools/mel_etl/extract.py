"""Motor de extracción: lee hojas del Excel y escribe los CSV normalizados."""

import csv
import os

from . import normalize
from .sheets import DIMENSIONES, PROCESOS, EVENTOS, EJECUCIONES, PARTICIPACIONES, AGREGADAS

_TRANSFORMS = {
    "": normalize.limpiar_celda,
    "a_entero": normalize.a_entero,
    "solo_fecha": normalize.solo_fecha,
    "sexo": normalize.normalizar_sexo,
    "estatus_dim": lambda v: normalize.normalizar_enum(v, {}, ["activo", "inactivo"]),
    "tipo_per": lambda v: (normalize.limpiar_celda(v).upper()
                           if normalize.limpiar_celda(v).upper() in ("P", "E", "R") else ""),
    "estatus_proc": lambda v: normalize.normalizar_enum(v, {}, ["activo", "concluido", "cancelado"]),
    "estatus_evento": lambda v: normalize.normalizar_enum(v, {}, ["programado", "ejecutado", "cancelado", "reprogramado"]),
    "estatus_ejec": lambda v: normalize.normalizar_enum(v, {}, ["ejecutada", "suspendida", "parcial"]),
}


def leer_hoja(wb, nombre_hoja, fila_cabecera=3):
    """Devuelve las filas de datos como lista de dict {columna_excel: valor_crudo}."""
    ws = wb[nombre_hoja]
    filas_iter = ws.iter_rows(min_row=fila_cabecera, values_only=True)
    cabecera = [str(c).strip() if c is not None else "" for c in next(filas_iter)]
    out = []
    for fila in filas_iter:
        if all(c is None for c in fila):
            continue
        registro = {cabecera[i]: fila[i] if i < len(fila) else None for i in range(len(cabecera))}
        out.append(registro)
    return out


def _aplicar(col_def, registro):
    """Aplica la transform de una col_def (excel, csv, transform) a un registro."""
    excel_col, _csv_col, transform = col_def
    fn = _TRANSFORMS.get(transform, normalize.limpiar_celda)
    return fn(registro.get(excel_col))


def escribir_csv(path, cabeceras, filas):
    """Escribe un CSV con cabecera. `filas` = lista de dict {csv_col: valor}. Devuelve nº filas."""
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, "w", newline="", encoding="utf-8") as fh:
        w = csv.DictWriter(fh, fieldnames=cabeceras)
        w.writeheader()
        n = 0
        for fila in filas:
            w.writerow(fila)
            n += 1
    return n


def _extraer_tabla(wb, definicion, outdir, archivo):
    """Lee una hoja según `definicion` y escribe su CSV. Descarta filas con clave vacía."""
    registros = leer_hoja(wb, definicion["hoja"])
    cabeceras = [c[1] for c in definicion["cols"]]
    clave = definicion["clave"]
    filas = []
    for reg in registros:
        salida = {c[1]: _aplicar(c, reg) for c in definicion["cols"]}
        if salida.get(clave, "") == "":
            continue  # fila-plantilla
        filas.append(salida)
    return escribir_csv(os.path.join(outdir, archivo), cabeceras, filas)


def extraer_dimensiones(wb, outdir):
    """Genera ejes/instituciones/lineas/componentes/actividades .csv. Devuelve conteos."""
    conteos = {}
    for nombre, definicion in DIMENSIONES.items():
        conteos[nombre] = _extraer_tabla(wb, definicion, outdir, f"{nombre}.csv")
    return conteos


def reasignar_ids(filas, col_id):
    """Mapa id_string→entero secuencial 1..N en orden de aparición, ignorando vacíos/duplicados."""
    mapa = {}
    siguiente = 1
    for fila in filas:
        sid = normalize.limpiar_celda(fila.get(col_id))
        if sid == "" or sid in mapa:
            continue
        mapa[sid] = siguiente
        siguiente += 1
    return mapa


def _tipo_prog(v):
    s = normalize.limpiar_celda(v).upper()
    return s if s in ("SESION_UNICA", "MULTI_SESION_PROGRAMADA", "PROCESO_CONTINUO") else ""


# tipo_programacion conserva MAYÚSCULAS, por eso usa _tipo_prog y no normalizar_enum.
_TRANSFORMS["tipo_prog"] = _tipo_prog


def extraer_cadena_programada(wb, outdir):
    """Escribe procesos.csv y eventos.csv con ids enteros y FK remapeadas."""
    # Procesos: id propio string→int.
    proc_filas = leer_hoja(wb, PROCESOS["hoja"])
    mapa_procesos = reasignar_ids(proc_filas, PROCESOS["clave_excel"])
    cab_proc = ["id_proceso"] + [c[1] for c in PROCESOS["cols"]]
    out_proc = []
    for reg in proc_filas:
        sid = normalize.limpiar_celda(reg.get(PROCESOS["clave_excel"]))
        if sid not in mapa_procesos:
            continue
        fila = {"id_proceso": mapa_procesos[sid]}
        for excel_col, csv_col, transform in PROCESOS["cols"]:
            fn = _TRANSFORMS.get(transform, normalize.limpiar_celda)
            fila[csv_col] = fn(reg.get(excel_col))
        out_proc.append(fila)
    n_proc = escribir_csv(os.path.join(outdir, "procesos.csv"), cab_proc, out_proc)

    # Eventos: id propio string→int + FK id_proceso remapeada.
    ev_filas = leer_hoja(wb, EVENTOS["hoja"])
    mapa_eventos = reasignar_ids(ev_filas, EVENTOS["clave_excel"])
    cab_ev = ["id_evento_programado", "id_proceso"] + [c[1] for c in EVENTOS["cols"]]
    out_ev = []
    for reg in ev_filas:
        sid = normalize.limpiar_celda(reg.get(EVENTOS["clave_excel"]))
        if sid not in mapa_eventos:
            continue
        sproc = normalize.limpiar_celda(reg.get(EVENTOS["fk_proceso_excel"]))
        fila = {
            "id_evento_programado": mapa_eventos[sid],
            "id_proceso": mapa_procesos.get(sproc, ""),
        }
        for excel_col, csv_col, transform in EVENTOS["cols"]:
            fn = _TRANSFORMS.get(transform, normalize.limpiar_celda)
            fila[csv_col] = fn(reg.get(excel_col))
        out_ev.append(fila)
    n_ev = escribir_csv(os.path.join(outdir, "eventos.csv"), cab_ev, out_ev)

    return {"procesos": n_proc, "eventos": n_ev, "mapa_procesos": mapa_procesos, "mapa_eventos": mapa_eventos}


def _fila_cols(reg, cols):
    out = {}
    for excel_col, csv_col, transform in cols:
        fn = _TRANSFORMS.get(transform, normalize.limpiar_celda)
        out[csv_col] = fn(reg.get(excel_col))
    return out


def extraer_ejecuciones_y_participacion(wb, outdir, mapa_eventos):
    """Escribe ejecuciones/participaciones/agregadas .csv con FK enteras remapeadas."""
    # Ejecuciones: id propio string→int + FK id_evento_programado.
    ej_filas = leer_hoja(wb, EJECUCIONES["hoja"])
    mapa_ejecuciones = reasignar_ids(ej_filas, EJECUCIONES["clave_excel"])
    cab_ej = ["id_ejecucion", "id_evento_programado"] + [c[1] for c in EJECUCIONES["cols"]]
    out_ej = []
    for reg in ej_filas:
        sid = normalize.limpiar_celda(reg.get(EJECUCIONES["clave_excel"]))
        if sid not in mapa_ejecuciones:
            continue
        sev = normalize.limpiar_celda(reg.get(EJECUCIONES["fk_evento_excel"]))
        fila = {"id_ejecucion": mapa_ejecuciones[sid], "id_evento_programado": mapa_eventos.get(sev, "")}
        fila.update(_fila_cols(reg, EJECUCIONES["cols"]))
        out_ej.append(fila)
    n_ej = escribir_csv(os.path.join(outdir, "ejecuciones.csv"), cab_ej, out_ej)

    # Participaciones: FK id_ejecucion; descarta filas sin ejecución o sin nombre.
    par_filas = leer_hoja(wb, PARTICIPACIONES["hoja"])
    cab_par = ["id_ejecucion"] + [c[1] for c in PARTICIPACIONES["cols"]]
    out_par = []
    for reg in par_filas:
        sej = normalize.limpiar_celda(reg.get(PARTICIPACIONES["fk_ejecucion_excel"]))
        idej = mapa_ejecuciones.get(sej, "")
        fila = {"id_ejecucion": idej}
        fila.update(_fila_cols(reg, PARTICIPACIONES["cols"]))
        if idej == "" or fila.get("nombres", "") == "" or fila.get("apellido_paterno", "") == "":
            continue
        out_par.append(fila)
    n_par = escribir_csv(os.path.join(outdir, "participaciones.csv"), cab_par, out_par)

    # Agregadas: FK id_ejecucion.
    agr_filas = leer_hoja(wb, AGREGADAS["hoja"])
    cab_agr = ["id_ejecucion"] + [c[1] for c in AGREGADAS["cols"]]
    out_agr = []
    for reg in agr_filas:
        sej = normalize.limpiar_celda(reg.get(AGREGADAS["fk_ejecucion_excel"]))
        idej = mapa_ejecuciones.get(sej, "")
        if idej == "":
            continue
        fila = {"id_ejecucion": idej}
        fila.update(_fila_cols(reg, AGREGADAS["cols"]))
        out_agr.append(fila)
    n_agr = escribir_csv(os.path.join(outdir, "agregadas.csv"), cab_agr, out_agr)

    return {"ejecuciones": n_ej, "participaciones": n_par, "agregadas": n_agr,
            "mapa_ejecuciones": mapa_ejecuciones}
