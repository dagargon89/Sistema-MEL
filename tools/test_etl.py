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
