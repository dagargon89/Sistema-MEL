"""CLI: convierte el Excel v1.9 a los 10 CSV de mel:import."""

import argparse
import os
import sys

import openpyxl

sys.path.insert(0, os.path.dirname(__file__))
from mel_etl.extract import extraer_dimensiones, extraer_cadena_programada, extraer_ejecuciones_y_participacion  # noqa: E402

DEFAULT_XLSX = os.path.join(os.path.dirname(__file__), "..", "CPJ_MEL_v1_9_seguimiento_actualizado (12).xlsx")
DEFAULT_OUT = os.path.join(os.path.dirname(__file__), "..", "apps", "api", "data", "excel")


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--xlsx", default=DEFAULT_XLSX)
    ap.add_argument("--out", default=DEFAULT_OUT)
    args = ap.parse_args()

    wb = openpyxl.load_workbook(args.xlsx, read_only=True, data_only=True)
    conteos = extraer_dimensiones(wb, args.out)
    cad = extraer_cadena_programada(wb, args.out)
    cad_mel = extraer_ejecuciones_y_participacion(wb, args.out, cad["mapa_eventos"])
    for d in (conteos, {k: v for k, v in cad.items() if isinstance(v, int)},
              {k: v for k, v in cad_mel.items() if isinstance(v, int)}):
        for nombre, n in d.items():
            print(f"  {nombre:16} {n}")


if __name__ == "__main__":
    main()
