import os
import tempfile
import unittest
from mel_etl.normalize import limpiar_celda, a_entero, solo_fecha, normalizar_sexo, normalizar_enum
from mel_etl.extract import leer_hoja, escribir_csv, extraer_dimensiones
import openpyxl

XLSX = os.path.join(os.path.dirname(__file__), "..", "CPJ_MEL_v1_9_seguimiento_actualizado (12).xlsx")


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


if __name__ == "__main__":
    unittest.main()
