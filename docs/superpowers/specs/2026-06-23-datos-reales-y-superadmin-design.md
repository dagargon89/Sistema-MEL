# Diseño — Datos reales del Excel v1.9 en el mock + superadmin

**Fecha:** 2026-06-23
**Autor:** dgarcia@planjuarez.org (con Claude Code)
**Estado:** aprobado

## Objetivo

1. Poblar el mock del demo (`apps/web/src/lib/mock/db.json`) con los **datos reales**
   del sistema MEL actual (`CPJ_MEL_v1_9_seguimiento_actualizado (12).xlsx`, 44 hojas),
   en lugar de la muestra sintética actual.
2. Que el rol **`administrador` funcione como superadmin**: vea las 19 pantallas sin
   importar los guards de rol por pantalla.

## Decisiones tomadas (brainstorming)

- **PII:** se importan los datos reales **tal cual** (nombres, teléfonos, correos de
  beneficiarios) y se **commitean** al repo. Es una decisión explícita del responsable
  de los datos (Plan Juárez). Queda registrado el riesgo de protección de datos
  (LFPDPPP) y que contradice el principio original del mock ("sin PII real").
- **Superadmin:** se **extiende el rol `administrador`** existente; no se crea un rol
  nuevo. Los otros 3 roles (capturista, coordinación, dirección) quedan intactos.

## Arquitectura

El mock carga `db.json` en arreglos tipados (`tabla<T>()`), uno por tabla del DDL,
cuyos nombres de clave deben coincidir exactamente con los que lee `api.mock.ts`.
`api.mock.ts` simula server-side (herencia, dedup, máquina de estados, KPIs, semáforos)
sobre esos arreglos. La fidelidad de campos/enums contra `types.ts` es obligatoria: un
valor fuera de catálogo o un campo faltante puede romper el typecheck o el runtime.

### Componente 1 — Importador `tools/import_excel.py`

Script Python (venv en `/tmp/xlsxenv`, openpyxl) que:

- Lee las 44 hojas; detecta la fila de encabezado real (los datos empiezan en fila 4).
- Mapea cada hoja de datos a su tabla del `db.json`, **renombrando columnas a los
  nombres exactos de `types.ts`** y reconciliando IDs de enlace (p. ej. el
  `id_evento_ejecutado` del Excel → `id_ejecucion` que espera `Participacion`).
- **Coerciona enums** (`control_registro`, `tipo_registro`, `alerta_duplicado`,
  `estatus*`, `sexo`, `mes`/`periodo_corte`, etc.) al conjunto permitido por `types.ts`;
  valores desconocidos caen a un default seguro documentado en el script.
- Coerciona tipos: números a `number`, fechas/horas a string ISO, vacíos a `null`
  respetando la nullabilidad de cada campo.
- **Conserva el bloque `_demo`** (metadata + casos QA), actualizando la nota de
  conciliación a "dataset real v1.9" y recalculando `demo_now`/`periodo_actual`.
- Mantiene `roles`, `usuarios`, `usuario_institucion` (las 4 cuentas demo) — el Excel
  no contiene usuarios del sistema.
- Escribe `db.json` formateado y estable (claves ordenadas igual que hoy).

#### Mapeo hoja → tabla

| Hoja | Tabla | Tipo |
|---|---|---|
| 🔵dim_ejes | ejes | `Eje` |
| 🟣dim_lineas | lineas | `Linea` |
| 🟢dim_instituciones | instituciones | `Institucion` |
| 🟡dim_componentes | componentes | `Componente` |
| 🟠tabla_actividades | actividades | `Actividad` |
| 🧭cat_procesos_programados | procesos | `Proceso` |
| 📅calendario_programacion | eventos_programados | `EventoProgramado` |
| ✅actividades_ejecutadas | ejecuciones | `Ejecucion` |
| 🤝participacion_actividad | participaciones | `Participacion` |
| 👥participacion_agregada | participaciones_agregadas | `ParticipacionAgregada` |
| 👤personas | personas | `Persona` |
| 📦productos_entregables | productos_entregables | `ProductoEntregable` |
| 📊metas_actividades | metas + metas_mensuales | `Meta` / `MetaMensual` |
| 📊seguimiento_metas | resultados | `Resultado` |
| 🧾bitacora_solicitudes | solicitudes | `Solicitud` |
| 🏢ocupacion_shelter | ocupacion_shelter | `OcupacionShelter` |
| 💰sostenibilidad_financiera | sostenibilidad_financiera | `SostenibilidadFinanciera` |
| 🤝propuestas_incidencia | propuestas_incidencia | `PropuestaIncidencia` |
| ⚡procesos_incidencia | procesos_incidencia | `ProcesoIncidencia` |
| 📌compromisos_seguimiento | compromisos | `Compromiso` |
| 🤲alianzas_incidencia | alianzas | `Alianza` |
| 📋bitacora_hitos_incidencia | hitos_incidencia | `HitoIncidencia` |

### Componente 2 — Superadmin (`administrador` ve todo)

Cambio mínimo en dos puntos, sin tocar los 19 guards individuales:

- `components/layout/ProtectedRoute.tsx`: si `user.rol === "administrador"`, se omite
  la verificación de `roles` (acceso total).
- `config/nav.ts` → `navParaRol()`: si el rol es `administrador`, devuelve el `NAV`
  completo.

## Flujo de datos

Excel → `import_excel.py` → `db.json` → `tabla<T>()` → arreglos en `api.mock.ts` →
`ApiClient` → React Query → pantallas. Sin cambios en pantallas ni en el contrato.

## Verificación

- `npm run typecheck` y `npm run build` en verde (el tipado valida el mapeo).
- Levantar el demo, entrar como `admin@demo.test` y recorrer las secciones: cargan con
  datos reales, sin errores de runtime ni KPIs en `NaN`.
- Conteos del `db.json` resultante coinciden con los volúmenes del Excel (±, según
  filtrado de filas vacías/totales).

## Riesgos y límites

- **PII real versionada** (aceptado).
- Las hojas de **incidencia/shelter/sostenibilidad** podrían no estar cableadas en
  `api.mock.ts`; si una pantalla es placeholder, el dato vivirá en `db.json` pero no se
  mostrará. No se expande `api.mock.ts` en este alcance; se reportará si ocurre.
- El importador es una herramienta de generación de una sola pasada; la verificación
  es typecheck + build + recorrido en runtime (no tests unitarios del script).

## No incluido (YAGNI)

- Rol nuevo `superadmin` (se decidió extender `administrador`).
- Cableado de nuevas pantallas o nuevos endpoints en `api.mock.ts`.
- Migración real a MySQL (eso es Fase 2).
