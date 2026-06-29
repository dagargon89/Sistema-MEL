# Diseño — ETL del Excel v1.9 a los CSV de `mel:import`

| | |
|---|---|
| **Fecha** | 2026-06-29 |
| **Objetivo** | Convertir el Excel v1.9 (44 hojas) en los 10 CSV normalizados que consume `php spark mel:import`, para poblar `sistema_mel` con los datos reales (cadena MEL core) y conciliar contra la línea base. |
| **App** | Extractor en `tools/` (Python); carga vía `apps/api` (`spark mel:import`, ya existente). |
| **Fuente** | `CPJ_MEL_v1_9_seguimiento_actualizado (12).xlsx` (raíz del repo). |
| **Consumidor** | `App\Services\MigracionService` (contrato de los 10 CSV). |

## 1. Alcance

**Dentro:** las 10 entidades que `mel:import` ya soporta —dimensiones (`ejes`, `lineas`, `componentes`, `instituciones`, `actividades`) y cadena MEL (`procesos`, `eventos_programados`, `ejecuciones`, `participaciones`, `participaciones_agregadas`)— a partir de sus 10 hojas core del Excel.

**Fuera (fase posterior, requeriría extender `MigracionService`):** `productos_entregables`, `metas`/`metas_mensuales`, `resultados`, incidencia (`propuestas/procesos/compromisos/alianzas/hitos`), verticales (`ocupacion_shelter`, `sostenibilidad_financiera`). Las hojas de resúmenes, dashboards e instrucciones del Excel nunca se migran (son cálculos/documentación).

## 2. Arquitectura y flujo

Script Python independiente que lee el `.xlsx` y emite los 10 CSV en `apps/api/data/excel/` (git-ignored, PII); luego `mel:import` los carga.

```
.xlsx (44 hojas) ──[tools/excel_to_csv.py]──> 10 CSV ──[php spark mel:import]──> MySQL + conciliación
```

Separación: Python extrae/normaliza (openpyxl); PHP carga transaccional + regenera `personas` por dedup (código existente, sin modificar).

## 3. El extractor (`tools/excel_to_csv.py`)

Se ejecuta con el venv `/tmp/melenv` (o uno documentado en `tools/`). Por cada hoja core:
1. Salta el banner: la **cabecera real está en la fila 3**; los datos en fila 4+.
2. Aplica un **mapa de columnas Excel→esquema** declarado por hoja.
3. Escribe el CSV con las cabeceras EXACTAS que `MigracionService` indexa.

Mapas de columnas (hoja → CSV), nombres de columna destino = los que espera `MigracionService`:

| Hoja Excel | CSV | Columnas destino (orden de `MigracionService`) |
|---|---|---|
| `🔵dim_ejes` | `ejes.csv` | id_eje, num_eje_original, clave_eje_corto, nombre, orden_visualizacion |
| `🟢dim_instituciones` | `instituciones.csv` | id_institucion, num_institucion_original, nombre, estatus, orden_visualizacion |
| `🟣dim_lineas` | `lineas.csv` | id_linea, num_linea, clave_linea_corta, nombre, id_eje, orden_visualizacion, estatus |
| `🟡dim_componentes` | `componentes.csv` | id_componente, num_componente, clave_componente, nombre, id_institucion, orden_visualizacion, estatus |
| `🟠tabla_actividades` | `actividades.csv` | id_actividad, num_actividad, nombre, id_eje, id_linea, id_componente, id_institucion, tipo_registro, caso_excepcional |
| `🧭cat_procesos_programados` | `procesos.csv` | id_proceso, nombre, tipo_programacion, id_actividad, fecha_inicio, fecha_fin, total_sesiones_programadas, responsable, contacto, estatus, observaciones |
| `📅calendario_programacion` | `eventos.csv` | id_evento_programado, id_actividad, id_proceso, tipo_programacion, fecha_inicio, fecha_finalizacion, hora_inicio, hora_finalizacion, modalidad, lugar, calle_y_numero, colonia, responsable, contacto, estatus, num_sesion, total_sesiones, observaciones |
| `✅actividades_ejecutadas` | `ejecuciones.csv` | id_ejecucion, id_evento_programado, fecha_ejecucion_real, hora_inicio_real, hora_finalizacion_real, lugar_real, colonia_real, responsable_real, estatus_ejecucion, tipo_registro_participacion, total_participantes, evidencia_url, nombre_archivo_evidencia, resumen_narrativo, control_registro, observaciones |
| `🤝participacion_actividad` | `participaciones.csv` | id_ejecucion, nombres, apellido_paterno, apellido_materno, anio_nacimiento, sexo, telefono, correo, colonia_persona, fecha_participacion |
| `👥participacion_agregada` | `agregadas.csv` | id_ejecucion, tipo_registro_participacion, sexo_grupo, grupo_edad_aprox, cantidad_participantes, motivo_no_nominal, fuente_conteo, periodo_corte, evidencia_url, control_registro |

> `participaciones.csv` NO incluye `id_persona`/`id_datosbeneficiario`: `MigracionService` regenera `personas` por dedup (ADR-003). El vínculo a ejecución sale de la columna `id_evento_ejecutado` del Excel → `id_ejecucion`.

## 4. Normalización (el núcleo del ETL)

- **Ids de la cadena → numéricos.** El Excel usa strings (`PROC_00001`, `EVP_00001`, `EJE_00007` para ejecuciones, `PAG_00001`); el esquema usa `BIGINT AUTO_INCREMENT`. El extractor asigna enteros secuenciales por entidad y mantiene mapas `string→int` para **remapear las FK** usando las columnas `id_*` que el Excel ya trae resueltas:
  - `eventos.id_proceso` ← mapa de procesos; `eventos.id_actividad` directo (CHAR).
  - `ejecuciones.id_evento_programado` ← mapa de eventos (columna `id_evento_programado` del Excel).
  - `participaciones.id_ejecucion` ← mapa de ejecuciones (columna `id_evento_ejecutado`).
  - `agregadas.id_ejecucion` ← mapa de ejecuciones (columna `id_evento_ejecutado`).
- **Ids de dimensiones**: se conservan como CHAR del Excel (`EJE_001`, `INST_001`, `ACT_001`, `COMP_*`, `LIN_*`) — verificados consistentes entre hojas. No se reformatean (las columnas del esquema son CHAR y aceptan estos valores).
- **`sexo`**: `M`→`F`, `H`→`M`, `N`→`X` (el Excel usa M=Mujer, H=Hombre; el esquema `ENUM('F','M','X')`). Sin este mapeo el sexo quedaría invertido.
- **Limpieza de celdas**: floats a entero (`1966.0`→`1966`, teléfonos `6561566319.0`→`6561566319`); fechas `YYYY-MM-DD HH:MM:SS`→`YYYY-MM-DD`; horas a `HH:MM:SS`; `#REF!`/`#N/A`/`#VALUE!`/vacío→celda vacía.
- **`tipo_programacion`, `estatus`, `control_registro`, `tipo_registro_participacion`**: se pasan tal cual cuando ya coinciden con los ENUM del esquema; si un valor del Excel no encaja, el extractor lo deja vacío (la BD aplica su default) y lo registra en un log de advertencias.

## 5. Flujo de carga e integración con login

`mel:import` asume BD migrada y **vacía**. Runbook documentado (script `tools/cargar_datos_reales.sh` o pasos en el spec):

1. Recrear `sistema_mel` vacía + `php spark migrate --all` (esquema + Shield + Settings).
2. `python tools/excel_to_csv.py` → genera los 10 CSV en `apps/api/data/excel/`.
3. `php spark mel:import` → carga dimensiones + cadena, regenera `personas`, imprime conciliación.
4. Sembrar **solo los 4 usuarios Shield** (`Sprint1Seeder`), ajustando `usuario_institucion` a las instituciones reales del Excel (`INST_001..005`). **No** correr `InitialSeeder` ni `Sprint3/5/6` (meterían datos de muestra que duplican/colisionan con los del Excel).

> `Sprint1Seeder` hoy toma instituciones de `db.json`. Si esos ids no son `INST_001..005`, su `usuario_institucion` apuntaría a instituciones inexistentes. El plan ajustará el ámbito de los usuarios a las instituciones reales (o dejará a dirección/admin sin restricción de ámbito).

## 6. Conciliación y verificación

- `mel:import` ya reporta y concilia contra la línea base: **236** actividades, **279** eventos, **988** participaciones, **762** personas (±15%).
- **Pruebas Python** (pytest o unittest del stdlib en `tools/`): funciones puras de normalización con casos tomados del Excel real — `sexo` (M/H/N → F/M/X), floats (`1966.0`→`1966`), fechas, remapeo de ids string→int y FK.
- **Verificación de integración**: correr el runbook completo y confirmar que los 4 conteos caen dentro de la tolerancia de la línea base; ningún tablero en 1000/100%.

## 7. Riesgos

- **`personas`=762 depende del dedup**, no del CSV: si la normalización de nombre/teléfono difiere de `DeduplicacionService`, el conteo varía (la tolerancia ±15% lo absorbe; si se sale, revisar la clave de dedup).
- **Filas-plantilla**: `actividades_ejecutadas` tiene ~1000 filas (con plantillas/`#REF!`); el filtro de clave vacía de `MigracionService` las descarta — verificar que el conteo neto de ejecuciones es razonable.
- **Ámbito de usuarios**: mapear los 4 usuarios demo a instituciones reales del Excel; se resuelve en el seeder de usuarios.
- **Valores de enum fuera de catálogo**: si el Excel trae estatus/tipos no contemplados, se vacían y se registran en el log de advertencias del extractor para revisión.

## 8. Fuera de alcance

- Extender `MigracionService` para productos/metas/incidencia/verticales/resultados.
- Modificar el esquema o `DeduplicacionService`.
- Versionar los CSV o el `.xlsx` con PII.
