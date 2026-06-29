# ETL del Excel v1.9 a CSV — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Construir un extractor Python que convierta las 10 hojas core del Excel v1.9 en los 10 CSV normalizados que consume `php spark mel:import`, y un runbook que cargue los datos reales en `sistema_mel` conciliando contra la línea base (236/279/988/762).

**Architecture:** Script Python en `tools/` con funciones puras de normalización (testeables), una definición declarativa por hoja (cabecera fila 3, mapa columna_excel→columna_csv), y un motor que lee el `.xlsx`, reasigna ids string→numérico para la cadena MEL, remapea FK con las columnas `id_*` que el Excel ya trae, y escribe los CSV en `apps/api/data/excel/`. La carga la hace `mel:import` (PHP, sin cambios).

**Tech Stack:** Python 3 (venv `/tmp/melenv` o `tools/.venv`), openpyxl, unittest (stdlib). Carga: CodeIgniter 4 `spark mel:import` (existente). MySQL 8 (Docker `mel-db`).

## Global Constraints

- El extractor NO modifica `apps/api` ni el esquema; solo produce CSV. La carga usa `MigracionService` tal cual.
- CSV de salida en `apps/api/data/excel/` (git-ignored, contienen PII). NO versionar CSV ni el `.xlsx`.
- Cabeceras de salida = nombres EXACTOS de columna que `MigracionService` indexa (ver tabla por hoja en cada task).
- Cabecera real de cada hoja del Excel está en la **fila 3**; datos desde la fila 4.
- Ids de dimensiones se conservan como CHAR del Excel (`EJE_001`, `INST_001`, `ACT_001`, `LIN_*`, `COMP_*`). Ids de la cadena (procesos/eventos/ejecuciones) se reasignan a enteros secuenciales 1..N y las FK se remapean.
- `sexo`: `M`→`F`, `H`→`M`, `N`→`X`. Celdas `#REF!`/`#N/A`/`#VALUE!`/vacío → vacío. Floats `1966.0`→`1966`. Fechas `YYYY-MM-DD HH:MM:SS`→`YYYY-MM-DD`.
- Enums de estatus/tipo se normalizan a minúsculas y se mapean a los valores del esquema; valor no reconocido → vacío (la BD aplica default) + log de advertencia.
- Línea base de conciliación (doc 06 §4): 236 actividades, 279 eventos, 988 participaciones, 762 personas (±15%).
- Spec de referencia: `docs/superpowers/specs/2026-06-29-etl-excel-a-csv-design.md`.
- Ruta del Excel: `CPJ_MEL_v1_9_seguimiento_actualizado (12).xlsx` (raíz del repo).

## File Structure

- `tools/requirements.txt` — `openpyxl`.
- `tools/mel_etl/__init__.py` — paquete.
- `tools/mel_etl/normalize.py` — funciones puras: `limpiar_celda`, `a_entero`, `solo_fecha`, `normalizar_sexo`, `normalizar_enum`.
- `tools/mel_etl/sheets.py` — definición declarativa de las 10 hojas (nombre, mapa de columnas, política de id).
- `tools/mel_etl/extract.py` — motor: `leer_hoja`, `reasignar_ids`, `escribir_csv`, `extraer_todo`.
- `tools/excel_to_csv.py` — CLI/main.
- `tools/test_etl.py` — tests unittest de `normalize` y del remapeo de ids.
- `tools/cargar_datos_reales.sh` — runbook de carga (recrear BD → migrate → extractor → mel:import → usuarios).

---

### Task 1: Funciones puras de normalización + scaffold

**Files:**
- Create: `tools/requirements.txt`
- Create: `tools/mel_etl/__init__.py`
- Create: `tools/mel_etl/normalize.py`
- Test: `tools/test_etl.py`

**Interfaces:**
- Produces: `limpiar_celda(v) -> str` (vacío para errores/None), `a_entero(v) -> str` (`'1966.0'`→`'1966'`, vacío si no numérico), `solo_fecha(v) -> str` (`'2026-05-02 00:00:00'`→`'2026-05-02'`), `normalizar_sexo(v) -> str` (`M/H/N`→`F/M/X`), `normalizar_enum(v, mapa, validos) -> str`.

- [ ] **Step 1: Escribir el test que falla**

Create `tools/test_etl.py`:

```python
import unittest
from mel_etl.normalize import limpiar_celda, a_entero, solo_fecha, normalizar_sexo, normalizar_enum


class TestNormalize(unittest.TestCase):
    def test_limpiar_celda_errores_y_vacios(self):
        self.assertEqual(limpiar_celda(None), "")
        self.assertEqual(limpiar_celda("  "), "")
        self.assertEqual(limpiar_celda("#REF!"), "")
        self.assertEqual(limpiar_celda("#¡REF!"), "")
        self.assertEqual(limpiar_celda("#N/A"), "")
        self.assertEqual(limpiar_celda("  Hola  "), "Hola")

    def test_a_entero(self):
        self.assertEqual(a_entero("1966.0"), "1966")
        self.assertEqual(a_entero("6561566319.0"), "6561566319")
        self.assertEqual(a_entero(1966), "1966")
        self.assertEqual(a_entero(""), "")
        self.assertEqual(a_entero("abc"), "")

    def test_solo_fecha(self):
        self.assertEqual(solo_fecha("2026-05-02 00:00:00"), "2026-05-02")
        self.assertEqual(solo_fecha("2026-05-02"), "2026-05-02")
        self.assertEqual(solo_fecha(""), "")

    def test_normalizar_sexo(self):
        self.assertEqual(normalizar_sexo("M"), "F")   # Excel M = Mujer
        self.assertEqual(normalizar_sexo("H"), "M")   # Excel H = Hombre
        self.assertEqual(normalizar_sexo("N"), "X")
        self.assertEqual(normalizar_sexo("m"), "F")
        self.assertEqual(normalizar_sexo(""), "")

    def test_normalizar_enum(self):
        validos = ["programado", "ejecutado", "cancelado", "reprogramado"]
        self.assertEqual(normalizar_enum("EJECUTADO", {}, validos), "ejecutado")
        self.assertEqual(normalizar_enum("REPROGRAMADO", {}, validos), "reprogramado")
        self.assertEqual(normalizar_enum("desconocido", {}, validos), "")
        self.assertEqual(normalizar_enum("ACTIVO", {"activo": "activo"}, ["activo", "inactivo"]), "activo")


if __name__ == "__main__":
    unittest.main()
```

- [ ] **Step 2: Ejecutar el test para verificar que falla**

Run desde `tools/`: `/tmp/melenv/bin/python -m unittest test_etl -v`
Expected: FAIL — `ModuleNotFoundError: No module named 'mel_etl'`.

- [ ] **Step 3: Crear el scaffold y las funciones puras**

Create `tools/requirements.txt`:

```
openpyxl>=3.1
```

Create `tools/mel_etl/__init__.py` (vacío).

Create `tools/mel_etl/normalize.py`:

```python
"""Funciones puras de normalización para el ETL del Excel v1.9 → CSV de mel:import."""

_ERRORES_EXCEL = {"#REF!", "#¡REF!", "#N/A", "#VALUE!", "#¡VALOR!", "#DIV/0!"}


def limpiar_celda(v) -> str:
    """None/errores de Excel/espacios → ''. Resto → str recortado."""
    if v is None:
        return ""
    s = str(v).strip()
    if s == "" or s in _ERRORES_EXCEL:
        return ""
    return s


def a_entero(v) -> str:
    """'1966.0' → '1966'. No numérico → ''."""
    s = limpiar_celda(v)
    if s == "":
        return ""
    try:
        return str(int(float(s)))
    except (ValueError, TypeError):
        return ""


def solo_fecha(v) -> str:
    """'2026-05-02 00:00:00' → '2026-05-02'. Vacío → ''."""
    s = limpiar_celda(v)
    if s == "":
        return ""
    return s.split(" ")[0]


def normalizar_sexo(v) -> str:
    """Convención del Excel v1.9: M=Mujer, H=Hombre, N=otro → ENUM('F','M','X')."""
    s = limpiar_celda(v).upper()
    return {"M": "F", "H": "M", "N": "X"}.get(s, "")


def normalizar_enum(v, mapa: dict, validos: list) -> str:
    """Minúsculas; aplica `mapa` (clave en minúsculas); '' si no está en `validos`."""
    s = limpiar_celda(v).lower()
    if s == "":
        return ""
    s = mapa.get(s, s)
    return s if s in validos else ""
```

- [ ] **Step 4: Ejecutar el test para verificar que pasa**

Run desde `tools/`: `/tmp/melenv/bin/python -m unittest test_etl -v`
Expected: PASS (5 tests OK).

- [ ] **Step 5: Commit**

```bash
git add tools/requirements.txt tools/mel_etl/__init__.py tools/mel_etl/normalize.py tools/test_etl.py
git commit -m "feat(tools): funciones puras de normalización del ETL del Excel

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 2: Definición de hojas + motor de extracción + dimensiones

**Files:**
- Create: `tools/mel_etl/sheets.py`
- Create: `tools/mel_etl/extract.py`
- Create: `tools/excel_to_csv.py`
- Modify: `tools/test_etl.py` (añadir test de remapeo de ids)

**Interfaces:**
- Consumes: `tools/mel_etl/normalize.py` (Task 1).
- Produces: `leer_hoja(wb, nombre_hoja, fila_cabecera=3) -> list[dict]` (filas como dict por nombre de columna Excel, celdas crudas); `escribir_csv(path, cabeceras, filas) -> int` (devuelve nº de filas escritas); `extraer_dimensiones(wb, outdir) -> dict[str,int]`; mapa `HOJAS` en `sheets.py`.

- [ ] **Step 1: Escribir el test (lectura de hoja real + dimensiones)**

Añadir a `tools/test_etl.py`:

```python
import os
import tempfile
from mel_etl.extract import leer_hoja, escribir_csv, extraer_dimensiones
import openpyxl

XLSX = os.path.join(os.path.dirname(__file__), "..", "CPJ_MEL_v1_9_seguimiento_actualizado (12).xlsx")


class TestDimensiones(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.wb = openpyxl.load_workbook(XLSX, read_only=True, data_only=True)

    def test_leer_hoja_dim_ejes(self):
        filas = leer_hoja(self.wb, "🔵dim_ejes")
        self.assertGreaterEqual(len(filas), 3)           # ≥3 ejes reales
        self.assertIn("id_eje", filas[0])
        self.assertTrue(filas[0]["id_eje"].startswith("EJE_"))

    def test_extraer_dimensiones_genera_5_csv(self):
        with tempfile.TemporaryDirectory() as d:
            conteos = extraer_dimensiones(self.wb, d)
            for nombre in ["ejes", "instituciones", "lineas", "componentes", "actividades"]:
                self.assertTrue(os.path.exists(os.path.join(d, f"{nombre}.csv")), f"falta {nombre}.csv")
                self.assertGreater(conteos[nombre], 0, f"{nombre} vacío")
            self.assertEqual(conteos["instituciones"], 5)
            # cabecera de actividades.csv = columnas del esquema
            with open(os.path.join(d, "actividades.csv")) as fh:
                cab = fh.readline().strip().split(",")
            self.assertEqual(cab[:3], ["id_actividad", "num_actividad", "nombre"])
```

- [ ] **Step 2: Ejecutar el test para verificar que falla**

Run desde `tools/`: `/tmp/melenv/bin/python -m unittest test_etl -v`
Expected: FAIL — `ModuleNotFoundError: No module named 'mel_etl.extract'`.

- [ ] **Step 3: Crear `sheets.py` (definición declarativa de dimensiones)**

Create `tools/mel_etl/sheets.py`:

```python
"""Definición declarativa de las 10 hojas core: hoja Excel → CSV destino.

Cada entrada: nombre de hoja, archivo CSV, y `cols` como lista de tuplas
(columna_excel, columna_csv, transform). `transform` es el nombre de una
función de normalize aplicada a la celda ('' = limpiar_celda por defecto).
"""

# Mapas de enum del Excel → valores del esquema.
ESTATUS_DIM = ({"activo": "activo", "inactivo": "inactivo"}, ["activo", "inactivo"])

DIMENSIONES = {
    "ejes": {
        "hoja": "🔵dim_ejes",
        "clave": "id_eje",
        "cols": [
            ("id_eje", "id_eje", ""),
            ("Num_eje_estrategico_original", "num_eje_original", "a_entero"),
            ("clave_eje_corto", "clave_eje_corto", ""),
            ("nom_eje_estrategico", "nombre", ""),
            ("orden_visualizacion", "orden_visualizacion", "a_entero"),
        ],
    },
    "instituciones": {
        "hoja": "🟢dim_instituciones",
        "clave": "id_institucion",
        "cols": [
            ("id_institucion", "id_institucion", ""),
            ("Num_institucion_original", "num_institucion_original", "a_entero"),
            ("nom_institucion", "nombre", ""),
            ("estatus_institucion", "estatus", "estatus_dim"),
            ("orden_visualizacion", "orden_visualizacion", "a_entero"),
        ],
    },
    "lineas": {
        "hoja": "🟣dim_lineas",
        "clave": "id_linea",
        "cols": [
            ("id_linea", "id_linea", ""),
            ("Num_linea_de_accion", "num_linea", "a_entero"),
            ("clave_linea_corta", "clave_linea_corta", ""),
            ("nom_linea", "nombre", ""),
            ("id_eje", "id_eje", ""),
            ("orden_visualizacion", "orden_visualizacion", "a_entero"),
            ("estatus_linea", "estatus", "estatus_dim"),
        ],
    },
    "componentes": {
        "hoja": "🟡dim_componentes",
        "clave": "id_componente",
        "cols": [
            ("id_componente", "id_componente", ""),
            ("Num_componente", "num_componente", "a_entero"),
            ("clave_componente", "clave_componente", ""),
            ("nom_componente", "nombre", ""),
            ("id_institucion", "id_institucion", ""),
            ("orden_visualizacion", "orden_visualizacion", "a_entero"),
            ("estatus_componente", "estatus", "estatus_dim"),
        ],
    },
    "actividades": {
        "hoja": "🟠tabla_actividades",
        "clave": "id_actividad",
        "cols": [
            ("id_actividad", "id_actividad", ""),
            ("num_actividad", "num_actividad", "a_entero"),
            ("nom_actividad", "nombre", ""),
            ("id_eje", "id_eje", ""),
            ("id_linea", "id_linea", ""),
            ("id_componente", "id_componente", ""),
            ("id_institucion", "id_institucion", ""),
            ("tipo_registro_actividad", "tipo_registro", "tipo_per"),
            ("caso_excepcional", "caso_excepcional", ""),
        ],
    },
}
```

> Nota: el valor `"tipo_pe r"` es un marcador; el motor lo trata como passthrough en mayúsculas y valida contra `{P,E,R}`. Si la columna `caso_excepcional` no existe en la hoja, el motor deja la celda vacía (es nullable).

- [ ] **Step 4: Crear `extract.py` (motor + dimensiones)**

Create `tools/mel_etl/extract.py`:

```python
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
```

Create `tools/excel_to_csv.py`:

```python
"""CLI: convierte el Excel v1.9 a los 10 CSV de mel:import."""

import argparse
import os
import sys

import openpyxl

sys.path.insert(0, os.path.dirname(__file__))
from mel_etl.extract import extraer_dimensiones  # noqa: E402

DEFAULT_XLSX = os.path.join(os.path.dirname(__file__), "..", "CPJ_MEL_v1_9_seguimiento_actualizado (12).xlsx")
DEFAULT_OUT = os.path.join(os.path.dirname(__file__), "..", "apps", "api", "data", "excel")


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--xlsx", default=DEFAULT_XLSX)
    ap.add_argument("--out", default=DEFAULT_OUT)
    args = ap.parse_args()

    wb = openpyxl.load_workbook(args.xlsx, read_only=True, data_only=True)
    conteos = extraer_dimensiones(wb, args.out)
    for nombre, n in conteos.items():
        print(f"  {nombre:16} {n}")


if __name__ == "__main__":
    main()
```

- [ ] **Step 5: Ejecutar el test para verificar que pasa**

Run desde `tools/`: `/tmp/melenv/bin/python -m unittest test_etl -v`
Expected: PASS (tests de dimensiones OK; `instituciones`=5).

- [ ] **Step 6: Verificar el CLI sobre el Excel real**

Run desde la raíz: `/tmp/melenv/bin/python tools/excel_to_csv.py --out /tmp/etl_out`
Expected: imprime conteos; `actividades` ≈ 236.
Run: `head -1 /tmp/etl_out/actividades.csv` → `id_actividad,num_actividad,nombre,id_eje,id_linea,id_componente,id_institucion,tipo_registro,caso_excepcional`

- [ ] **Step 7: Commit**

```bash
git add tools/mel_etl/sheets.py tools/mel_etl/extract.py tools/excel_to_csv.py tools/test_etl.py
git commit -m "feat(tools): motor de extracción + dimensiones del ETL

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 3: Cadena programada (procesos + eventos) con reasignación de ids

**Files:**
- Modify: `tools/mel_etl/sheets.py` (añadir `procesos`, `eventos`)
- Modify: `tools/mel_etl/extract.py` (añadir reasignación de ids + `extraer_cadena_programada`)
- Modify: `tools/excel_to_csv.py` (llamar a la nueva extracción)
- Modify: `tools/test_etl.py` (test de reasignación de ids y FK)

**Interfaces:**
- Consumes: `leer_hoja`, `escribir_csv` (Task 2).
- Produces: `reasignar_ids(filas, col_id) -> dict[str,int]` (mapa id_string→entero 1..N, en orden de aparición, ignorando vacíos); `extraer_cadena_programada(wb, outdir) -> dict` con conteos y los mapas `mapa_procesos`, `mapa_eventos` para Task 4.

- [ ] **Step 1: Escribir el test de reasignación de ids**

Añadir a `tools/test_etl.py`:

```python
from mel_etl.extract import reasignar_ids


class TestReasignarIds(unittest.TestCase):
    def test_reasignar_ids_secuencial(self):
        filas = [{"id": "PROC_00001"}, {"id": "PROC_00005"}, {"id": ""}, {"id": "PROC_00009"}]
        mapa = reasignar_ids(filas, "id")
        self.assertEqual(mapa, {"PROC_00001": 1, "PROC_00005": 2, "PROC_00009": 3})

    def test_reasignar_ids_sin_duplicar(self):
        filas = [{"id": "EVP_1"}, {"id": "EVP_1"}, {"id": "EVP_2"}]
        mapa = reasignar_ids(filas, "id")
        self.assertEqual(mapa, {"EVP_1": 1, "EVP_2": 2})
```

- [ ] **Step 2: Ejecutar el test para verificar que falla**

Run desde `tools/`: `/tmp/melenv/bin/python -m unittest test_etl -v`
Expected: FAIL — `ImportError: cannot import name 'reasignar_ids'`.

- [ ] **Step 3: Añadir `procesos` y `eventos` a `sheets.py`**

Añadir al final de `DIMENSIONES`... no: crear un dict nuevo `CADENA_PROGRAMADA` en `tools/mel_etl/sheets.py`:

```python
# Mapas de enum de la cadena.
TIPO_PROG = ({}, ["SESION_UNICA", "MULTI_SESION_PROGRAMADA", "PROCESO_CONTINUO"])
ESTATUS_PROC = ({}, ["activo", "concluido", "cancelado"])
ESTATUS_EVENTO = ({}, ["programado", "ejecutado", "cancelado", "reprogramado"])

# procesos y eventos llevan id propio (string→int) y FK a remapear.
PROCESOS = {
    "hoja": "🧭cat_procesos_programados",
    "clave_excel": "id_proceso",          # PROC_xxxxx → entero
    "cols": [
        ("nombre_proceso / grupo", "nombre", ""),
        ("tipo_actividad_calendario", "tipo_programacion", "tipo_prog"),
        ("id_actividad", "id_actividad", ""),
        ("fecha_inicio_proceso", "fecha_inicio", "solo_fecha"),
        ("fecha_fin_proceso", "fecha_fin", "solo_fecha"),
        ("total_sesiones_programadas", "total_sesiones_programadas", "a_entero"),
        ("responsable_actividad", "responsable", ""),
        ("contacto", "contacto", ""),
        ("estatus_proceso", "estatus", "estatus_proc"),
        ("observaciones", "observaciones", ""),
    ],
}

EVENTOS = {
    "hoja": "📅calendario_programacion",
    "clave_excel": "id_evento_programado",   # EVP_xxxxx → entero
    "fk_proceso_excel": "id_proceso",         # PROC_xxxxx → mapa_procesos
    "cols": [
        ("id_actividad", "id_actividad", ""),
        ("tipo_actividad_calendario", "tipo_programacion", "tipo_prog"),
        ("fecha_inicio", "fecha_inicio", "solo_fecha"),
        ("fecha_finalizacion", "fecha_finalizacion", "solo_fecha"),
        ("hora_inicio", "hora_inicio", ""),
        ("hora_finalizacion", "hora_finalizacion", ""),
        ("modalidad", "modalidad", ""),
        ("lugar_actividad", "lugar", ""),
        ("calle_y_numero", "calle_y_numero", ""),
        ("colonia", "colonia", ""),
        ("responsable_actividad", "responsable", ""),
        ("contacto", "contacto", ""),
        ("estatus", "estatus", "estatus_evento"),
        ("num_sesion", "num_sesion", "a_entero"),
        ("total_sesiones", "total_sesiones", "a_entero"),
        ("observaciones", "observaciones", ""),
    ],
}
```

Añadir las entradas de transform a `_TRANSFORMS` en `extract.py` (solo los estatus en minúsculas; `tipo_prog` se registra en Step 4 porque necesita preservar MAYÚSCULAS):

```python
    "estatus_proc": lambda v: normalize.normalizar_enum(v, {}, ["activo", "concluido", "cancelado"]),
    "estatus_evento": lambda v: normalize.normalizar_enum(v, {}, ["programado", "ejecutado", "cancelado", "reprogramado"]),
```

- [ ] **Step 4: Añadir `reasignar_ids` y `extraer_cadena_programada` a `extract.py`**

Añadir a `tools/mel_etl/extract.py`:

```python
from .sheets import PROCESOS, EVENTOS


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
```

Modificar `tools/excel_to_csv.py` `main()` para llamar también a `extraer_cadena_programada(wb, args.out)` e imprimir sus conteos (importar la función arriba).

- [ ] **Step 5: Ejecutar tests + CLI**

Run desde `tools/`: `/tmp/melenv/bin/python -m unittest test_etl -v` → PASS.
Run desde la raíz: `/tmp/melenv/bin/python tools/excel_to_csv.py --out /tmp/etl_out`
Expected: `eventos` ≈ 279. `head -1 /tmp/etl_out/eventos.csv` empieza con `id_evento_programado,id_proceso,id_actividad,...`.

- [ ] **Step 6: Commit**

```bash
git add tools/mel_etl/sheets.py tools/mel_etl/extract.py tools/excel_to_csv.py tools/test_etl.py
git commit -m "feat(tools): extracción de procesos y eventos con reasignación de ids y FK

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 4: Ejecuciones + participaciones + agregadas

**Files:**
- Modify: `tools/mel_etl/sheets.py` (añadir `EJECUCIONES`, `PARTICIPACIONES`, `AGREGADAS`)
- Modify: `tools/mel_etl/extract.py` (añadir `extraer_ejecuciones_y_participacion`)
- Modify: `tools/excel_to_csv.py` (orquestar todo)
- Modify: `tools/test_etl.py` (test de los 3 CSV finales + sexo aplicado)

**Interfaces:**
- Consumes: `mapa_eventos` (Task 3), `reasignar_ids`, `leer_hoja`, `escribir_csv`.
- Produces: `extraer_ejecuciones_y_participacion(wb, outdir, mapa_eventos) -> dict` con conteos; `ejecuciones.csv`, `participaciones.csv`, `agregadas.csv`. Mapa `mapa_ejecuciones` (id_evento_ejecutado string→int) usado para las FK de participaciones y agregadas.

- [ ] **Step 1: Escribir el test (3 CSV + sexo correcto)**

Añadir a `tools/test_etl.py`:

```python
from mel_etl.extract import extraer_cadena_programada, extraer_ejecuciones_y_participacion


class TestCadenaMEL(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.wb = openpyxl.load_workbook(XLSX, read_only=True, data_only=True)

    def test_participaciones_y_sexo(self):
        with tempfile.TemporaryDirectory() as d:
            cad = extraer_cadena_programada(self.wb, d)
            res = extraer_ejecuciones_y_participacion(self.wb, d, cad["mapa_eventos"])
            for nombre in ["ejecuciones", "participaciones", "agregadas"]:
                self.assertTrue(os.path.exists(os.path.join(d, f"{nombre}.csv")))
            self.assertGreater(res["participaciones"], 800)   # línea base ≈988
            # sexo normalizado: solo F/M/X o vacío
            import csv as _csv
            with open(os.path.join(d, "participaciones.csv")) as fh:
                sexos = {row["sexo"] for row in _csv.DictReader(fh)}
            self.assertTrue(sexos <= {"F", "M", "X", ""}, f"sexo sin normalizar: {sexos}")
            # id_ejecucion es entero
            with open(os.path.join(d, "participaciones.csv")) as fh:
                primera = next(_csv.DictReader(fh))
            self.assertTrue(primera["id_ejecucion"].isdigit() or primera["id_ejecucion"] == "")
```

- [ ] **Step 2: Ejecutar el test para verificar que falla**

Run desde `tools/`: `/tmp/melenv/bin/python -m unittest test_etl -v`
Expected: FAIL — `cannot import name 'extraer_ejecuciones_y_participacion'`.

- [ ] **Step 3: Añadir definiciones a `sheets.py`**

Añadir a `tools/mel_etl/sheets.py`:

```python
ESTATUS_EJEC = ({}, ["ejecutada", "suspendida", "parcial"])
CONTROL_EJEC = ({}, ["CAPTURADO", "INCOMPLETO", "REVISAR", "OK", "AGREGADO"])

EJECUCIONES = {
    "hoja": "✅actividades_ejecutadas",
    "clave_excel": "id_evento_ejecutado",     # EJE_xxxxx (ejecución) → entero
    "fk_evento_excel": "id_evento_programado",  # EVP_xxxxx → mapa_eventos
    "cols": [
        ("fecha_ejecucion_real", "fecha_ejecucion_real", "solo_fecha"),
        ("hora_inicio_real", "hora_inicio_real", ""),
        ("hora_finalizacion_real", "hora_finalizacion_real", ""),
        ("lugar_actividad_real", "lugar_real", ""),
        ("colonia_real", "colonia_real", ""),
        ("responsable_actividad_real", "responsable_real", ""),
        ("estatus_ejecucion", "estatus_ejecucion", "estatus_ejec"),
        ("total_participantes", "total_participantes", "a_entero"),
        ("evidencia_url", "evidencia_url", ""),
        ("nombre_archivo_evidencia", "nombre_archivo_evidencia", ""),
        ("resumen_narrativo_actividad", "resumen_narrativo", ""),
        ("observaciones", "observaciones", ""),
    ],
}

PARTICIPACIONES = {
    "hoja": "🤝participacion_actividad",
    "fk_ejecucion_excel": "id_evento_ejecutado",  # → mapa_ejecuciones
    "cols": [
        ("nombres", "nombres", ""),
        ("apellido_paterno", "apellido_paterno", ""),
        ("apellido_materno", "apellido_materno", ""),
        ("anio_nacimiento", "anio_nacimiento", "a_entero"),
        ("sexo", "sexo", "sexo"),
        ("telefono", "telefono", "a_entero"),
        ("correo", "correo", ""),
        ("colonia_persona", "colonia_persona", ""),
        ("fecha_participacion", "fecha_participacion", "solo_fecha"),
    ],
}

AGREGADAS = {
    "hoja": "👥participacion_agregada",
    "fk_ejecucion_excel": "id_evento_ejecutado",  # → mapa_ejecuciones
    "cols": [
        ("tipo_registro_participacion", "tipo_registro_participacion", ""),
        ("sexo_grupo", "sexo_grupo", ""),
        ("grupo_edad_aprox", "grupo_edad_aprox", ""),
        ("cantidad_participantes", "cantidad_participantes", "a_entero"),
        ("motivo_no_nominal", "motivo_no_nominal", ""),
        ("fuente_conteo", "fuente_conteo", ""),
        ("periodo_corte", "periodo_corte", ""),
        ("evidencia_url_opcional", "evidencia_url", ""),
    ],
}
```

Añadir transform `"estatus_ejec"` a `_TRANSFORMS` en `extract.py`:

```python
    "estatus_ejec": lambda v: normalize.normalizar_enum(v, {}, ["ejecutada", "suspendida", "parcial"]),
```

- [ ] **Step 4: Añadir `extraer_ejecuciones_y_participacion` a `extract.py`**

Añadir a `tools/mel_etl/extract.py`:

```python
from .sheets import EJECUCIONES, PARTICIPACIONES, AGREGADAS


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
```

Actualizar `tools/excel_to_csv.py` `main()` para orquestar las tres fases en orden y imprimir todos los conteos:

```python
    conteos = extraer_dimensiones(wb, args.out)
    cad = extraer_cadena_programada(wb, args.out)
    cad_mel = extraer_ejecuciones_y_participacion(wb, args.out, cad["mapa_eventos"])
    for d in (conteos, {k: v for k, v in cad.items() if isinstance(v, int)},
              {k: v for k, v in cad_mel.items() if isinstance(v, int)}):
        for nombre, n in d.items():
            print(f"  {nombre:16} {n}")
```

(importar `extraer_cadena_programada` y `extraer_ejecuciones_y_participacion`).

- [ ] **Step 5: Ejecutar tests + CLI completo**

Run desde `tools/`: `/tmp/melenv/bin/python -m unittest test_etl -v` → PASS (todas las clases).
Run desde la raíz: `/tmp/melenv/bin/python tools/excel_to_csv.py --out /tmp/etl_out`
Expected: 10 CSV; `participaciones` ≈ 988, `eventos` ≈ 279, `actividades` ≈ 236.
Run: `ls /tmp/etl_out/*.csv | wc -l` → `10`.

- [ ] **Step 6: Commit**

```bash
git add tools/mel_etl/sheets.py tools/mel_etl/extract.py tools/excel_to_csv.py tools/test_etl.py
git commit -m "feat(tools): extracción de ejecuciones, participaciones (PII/sexo) y agregadas

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

### Task 5: Runbook de carga + usuarios con ámbito real + conciliación

**Files:**
- Create: `tools/cargar_datos_reales.sh`
- Create: `apps/api/app/Database/Seeds/UsuariosRealesSeeder.php`
- Test: verificación de integración (conteos en MySQL vs línea base)

**Interfaces:**
- Consumes: `tools/excel_to_csv.py` (Tasks 2-4), `php spark mel:import` (existente), `App\Database\Seeds\Sprint1Seeder` (patrón de usuarios Shield).
- Produces: runbook reproducible; `UsuariosRealesSeeder` que crea los 4 usuarios Shield y los liga a instituciones reales del Excel (`INST_001..005`).

- [ ] **Step 1: Crear el seeder de usuarios con ámbito real**

Create `apps/api/app/Database/Seeds/UsuariosRealesSeeder.php` (basado en `Sprint1Seeder`, pero ligando `usuario_institucion` a instituciones reales ya cargadas por `mel:import`):

```php
<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

/**
 * Crea los 4 usuarios demo (Shield) sobre datos reales ya migrados (mel:import).
 * Liga capturista/coordinación a instituciones reales del Excel; dirección/admin sin ámbito.
 */
class UsuariosRealesSeeder extends Seeder
{
    public const DEV_PASSWORD = 'MelDemo2026!';

    public function run(): void
    {
        $roles = $this->db->table('roles')->get()->getResultArray();
        $idRol = [];
        foreach ($roles as $r) {
            $idRol[$r['clave']] = (int) $r['id_rol'];
        }

        $usuarios = [
            ['email' => 'capturista@demo.test',   'nombre' => 'Capturista Demo',   'rol' => 'capturista',    'inst' => 'INST_001'],
            ['email' => 'coordinacion@demo.test', 'nombre' => 'Coordinación Demo', 'rol' => 'coordinacion',  'inst' => 'INST_001'],
            ['email' => 'direccion@demo.test',    'nombre' => 'Dirección Demo',    'rol' => 'direccion',     'inst' => null],
            ['email' => 'admin@demo.test',        'nombre' => 'Admin Sistema',     'rol' => 'administrador', 'inst' => null],
        ];

        $provider = new UserModel();
        foreach ($usuarios as $u) {
            if ($this->db->table('usuarios')->where('email', $u['email'])->countAllResults() > 0) {
                continue;
            }
            $entity = new User(['username' => null, 'email' => $u['email']]);
            $provider->save($entity);
            $saved = $provider->findById($provider->getInsertID());
            $saved->createEmailIdentity(['email' => $u['email'], 'password' => self::DEV_PASSWORD]);

            $idUsuario = $saved->id;
            $this->db->table('usuarios')->insert([
                'id_usuario' => $idUsuario,
                'nombre'     => $u['nombre'],
                'email'      => $u['email'],
                'id_rol'     => $idRol[$u['rol']],
                'estatus'    => 'activo',
            ]);
            if ($u['inst'] !== null) {
                $this->db->table('usuario_institucion')->insert([
                    'id_usuario'     => $idUsuario,
                    'id_institucion' => $u['inst'],
                ]);
            }
        }
    }
}
```

- [ ] **Step 2: Crear el runbook**

Create `tools/cargar_datos_reales.sh`:

```bash
#!/usr/bin/env bash
# Carga los datos reales del Excel v1.9 en sistema_mel (cadena MEL core).
# Pre-requisito: Docker mel-db arriba, venv en /tmp/melenv con openpyxl.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
API="$ROOT/apps/api"

echo "==> 1. Recrear sistema_mel vacía"
docker exec mel-db mysql -uroot -proot_secret -e \
  "DROP DATABASE IF EXISTS sistema_mel; CREATE DATABASE sistema_mel CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci; \
   GRANT ALL PRIVILEGES ON sistema_mel.* TO 'mel'@'%'; FLUSH PRIVILEGES;"

echo "==> 2. Migrar esquema (App + Shield + Settings)"
( cd "$API" && php spark migrate --all )

echo "==> 3. Extraer Excel -> 10 CSV"
/tmp/melenv/bin/python "$ROOT/tools/excel_to_csv.py" --out "$API/data/excel"

echo "==> 4. Importar y conciliar"
( cd "$API" && php spark mel:import )

echo "==> 5. Sembrar usuarios reales (Shield)"
( cd "$API" && php spark db:seed UsuariosRealesSeeder )

echo "==> Listo. Login: admin@demo.test / MelDemo2026!"
```

Run: `chmod +x tools/cargar_datos_reales.sh`

- [ ] **Step 3: Ejecutar el runbook completo**

Run desde la raíz: `bash tools/cargar_datos_reales.sh`
Expected: la tabla de conciliación de `mel:import` muestra conteos cercanos a la línea base; los 4 `conciliar` imprimen `✓` (dentro de ±15%). El seeder crea 4 usuarios.

- [ ] **Step 4: Verificar conteos en MySQL vs línea base**

Run:
```bash
docker exec mel-db mysql -umel -pmel_secret -e "SELECT
 (SELECT COUNT(*) FROM sistema_mel.actividades) AS actividades,
 (SELECT COUNT(*) FROM sistema_mel.eventos_programados) AS eventos,
 (SELECT COUNT(*) FROM sistema_mel.participaciones) AS participaciones,
 (SELECT COUNT(*) FROM sistema_mel.personas) AS personas,
 (SELECT COUNT(*) FROM sistema_mel.usuarios) AS usuarios;"
```
Expected: actividades≈236, eventos≈279, participaciones≈988, personas≈762 (±15%), usuarios=4.

- [ ] **Step 5: Verificar login real sobre datos reales**

Run:
```bash
curl -s -X POST http://localhost:8080/api/v1/auth/login -H "Content-Type: application/json" \
  -d '{"email":"coordinacion@demo.test","password":"MelDemo2026!"}' | head -c 300
```
Expected: `"success": true` con token y rol `coordinacion`.

- [ ] **Step 6: Commit**

```bash
git add tools/cargar_datos_reales.sh apps/api/app/Database/Seeds/UsuariosRealesSeeder.php
git commit -m "feat: runbook de carga de datos reales + seeder de usuarios con ámbito real

Co-Authored-By: Claude Opus 4.8 (1M context) <noreply@anthropic.com>"
```

---

## Notas de verificación final

- `.gitignore` ya excluye `apps/api/data/excel/` (verificar; si no, añadirlo — los CSV tienen PII).
- Si la conciliación de `personas` queda fuera de ±15%, revisar la normalización de teléfono/nombre frente a `DeduplicacionService` (la clave de dedup usa esos campos).
- El extractor depende del venv `/tmp/melenv`; para reproducibilidad a futuro, considerar `tools/.venv` documentado en `tools/requirements.txt` (fuera del alcance de este plan).
