"""Motor de extracción: lee hojas del Excel y escribe los CSV normalizados."""

import csv
import os

from . import normalize
from .sheets import DIMENSIONES, PROCESOS, EVENTOS

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
