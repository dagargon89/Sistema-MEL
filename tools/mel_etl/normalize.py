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
