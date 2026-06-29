"""Motor de extracción: lee hojas del Excel y escribe los CSV normalizados."""

import csv
import os

from . import normalize
from .sheets import DIMENSIONES

_TRANSFORMS = {
    "": normalize.limpiar_celda,
    "a_entero": normalize.a_entero,
    "solo_fecha": normalize.solo_fecha,
    "sexo": normalize.normalizar_sexo,
    "estatus_dim": lambda v: normalize.normalizar_enum(v, {}, ["activo", "inactivo"]),
    "tipo_per": lambda v: (normalize.limpiar_celda(v).upper()
                           if normalize.limpiar_celda(v).upper() in ("P", "E", "R") else ""),
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
