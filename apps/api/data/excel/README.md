# Datos de migración — `spark mel:import`

Este directorio recibe los **CSV exportados del Excel v1.9** para la carga inicial
(Sprint 4, doc 06 §4). Los archivos contienen **PII de beneficiarios**, por lo que
**no se versionan** (ver `.gitignore`): se copian aquí solo en el servidor de staging
y se ejecuta la migración contra **MySQL real**.

```bash
# 1) Exportar cada hoja relevante del Excel a CSV con estas cabeceras (columnas = DDL doc 03).
# 2) Colocar los CSV en este directorio.
# 3) Ejecutar:
php spark mel:import                 # usa apps/api/data/excel por defecto
php spark mel:import --dir /ruta/csv # u otra ruta
```

## Archivos esperados (los ausentes se omiten)

| Archivo | Tabla | Clave (filas sin ella se descartan como plantilla) |
|---|---|---|
| `ejes.csv` | `ejes` | `id_eje` |
| `instituciones.csv` | `instituciones` | `id_institucion` |
| `lineas.csv` | `lineas` | `id_linea` |
| `componentes.csv` | `componentes` | `id_componente` |
| `actividades.csv` | `actividades` | `id_actividad` + `tipo_registro` |
| `procesos.csv` | `procesos` | `id_proceso` |
| `eventos.csv` | `eventos_programados` | `id_evento_programado` |
| `ejecuciones.csv` | `ejecuciones` | `id_ejecucion` |
| `participaciones.csv` | `participaciones` | `id_ejecucion` + `nombres` + `apellido_paterno` |
| `agregadas.csv` | `participaciones_agregadas` | `id_ejecucion` |

> La cabecera de cada CSV debe coincidir con los nombres de columna del DDL (doc 03).
> Las celdas `#REF!` / `#N/A` / `#VALUE!` se limpian a `NULL` automáticamente.

## Qué hace el comando

1. Carga dimensiones → catálogo → cadena MEL en orden de dependencias (FK RESTRICT).
2. Limpia `#REF!` y descarta filas-plantilla del Excel.
3. **Regenera `personas` por deduplicación** (ADR-003) — nunca las copia del Excel
   congelado; los sospechosos por teléfono entran a la cola (`control_registro = REVISAR`).
4. Imprime la **conciliación** y la contrasta con la línea base verificada
   (≈ **988** participaciones / **762** personas / **279** eventos / **132** ejecuciones,
   **236** actividades 174 P / 42 E / 20 R). Ningún tablero debe quedar en 1000/100%.

El mecanismo se verifica en CI con un fixture representativo
(`tests/_support/fixtures/excel/`, `tests/database/MigracionTest.php`).
