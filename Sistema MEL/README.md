# Sistema MEL — Documentación Técnica del Proyecto
### Plataforma de Monitoreo, Evaluación y Aprendizaje · Comunidad Participa Juárez (CPJ)

> Sistema institucional de información que registra **qué se programa, qué se ejecuta, quién participa y a quién se beneficia**, vinculando cada registro con la planeación estratégica (POA) y produciendo los indicadores MEL que CPJ reporta internamente y a financiadores como FECHAC. Reemplaza el libro Excel de 44 hojas (v1.9) conservando toda su lógica y eliminando su deuda técnica de implementación.

| | |
|---|---|
| **Organización** | Comunidad Participa Juárez · Ciudad Juárez, Chihuahua |
| **Propósito** | Plataforma MEL multiusuario con integridad referencial real, deduplicación de personas del lado servidor y tableros con KPIs verídicos. |
| **Estado** | Documentación de ingeniería — lista para iniciar Sprint 0 |
| **Versión doc.** | 1.0 |
| **Fecha** | 22 de junio de 2026 |
| **Tipo de release** | MVP (sistema completo en 4 fases) |

> **Origen del contenido.** Esta documentación traduce a un estándar de ingeniería el análisis técnico del Sistema MEL v1.9 (Excel, 44 hojas), el estudio de viabilidad, los tres manuales institucionales (operativo v2.1, capturistas, coordinación) y los 14 documentos de especificación agnóstica de `docs_plataforma/`. La lógica de negocio (cadena referencial, herencia estratégica, deduplicación, casos A–D) se conserva íntegra; lo que cambia es el motor que la hace cumplir.

---

## Stack tecnológico (v1.0 — decidido)

| Capa | Tecnología | Versión (jun-2026) |
|---|---|---|
| Backend | **CodeIgniter** (API REST JSON pura) | 4.7.x |
| Lenguaje | PHP | 8.3+ |
| Frontend | **React + Vite + TypeScript** | React 19 · Vite 6 |
| Estado cliente | TanStack Query (server-state) + Zustand (UI-state) | — |
| Estilos | Tailwind CSS 4 (tokens del design system, doc 08) | 4.x |
| Base de datos (fuente de verdad) | **MySQL** | 8.0+ |
| Autenticación | **CodeIgniter Shield** (sesión + access tokens para la SPA); cuentas individuales | Shield 1.x |
| Caché, cola y throttle | Redis | — |
| Almacenamiento de evidencias | **Enlace a Google Drive institucional** (la plataforma guarda URL + nombre normalizado; no aloja archivos) | — |
| Reportería / BI | Power BI / Tableau conectados directo a MySQL (esquema en estrella) o tableros nativos en React | — |
| Despliegue | VPS Linux (Nginx sirve la SPA + proxy a `/api`) | HTTPS forzado |

**Arquitectura:** cliente-servidor desacoplado. La SPA de React consume una API REST JSON de CodeIgniter 4. **MySQL es la única fuente de verdad** de identidad, autorización y dominio transaccional. **El cliente nunca es de fiar**: toda regla de negocio, toda validación de cadena referencial y toda decisión de autorización (incluido el filtrado por institución/territorio) viven en CI4. La autenticación es 100% autoalojada con CodeIgniter Shield: sin contraseñas compartidas y sin dependencia de proveedores externos de identidad.

---

### Código fuente y arranque

El código se desarrollará en un monorepo (`apps/api` para CodeIgniter 4 y `apps/web` para React + Vite). Antes de codificar, lee **[`CLAUDE.md`](CLAUDE.md)** — es el punto de entrada con el stack, las reglas no negociables, los comandos y el orden de lectura. Esta documentación es la **fuente de verdad de desarrollo** y prevalece sobre el scaffolding.

---

## Índice de documentos

| # | Documento | Contenido |
|---|---|---|
| 01 | [SRS — Especificación de Requisitos](01-vision/01_SRS_especificacion_requisitos.md) | Alcance, glosario MEL, roles, requisitos funcionales por módulo, máquina de estados, RNF, criterios de aceptación del MVP |
| 02 | [Arquitectura del Sistema](02-arquitectura/02_arquitectura_sistema.md) | Capas CI4+React, frontera SPA↔API, patrones de implementación, flujos críticos, despliegue VPS |
| 02a | [ADR-001 — Stack CI4 + React + MySQL](02-arquitectura/ADR/ADR-001_stack-ci4-react-mysql.md) | Elección de stack autoalojado; por qué no Supabase/PostgreSQL; equivalencias de features |
| 02b | [ADR-002 — Autenticación con CodeIgniter Shield](02-arquitectura/ADR/ADR-002_autenticacion-shield.md) | Identidad autoalojada; sesión + access tokens para la SPA; sin proveedor externo |
| 02c | [ADR-003 — Deduplicación sin PostgreSQL](02-arquitectura/ADR/ADR-003_deduplicacion-sin-postgres.md) | Reemplazo de `unaccent`/`pg_trgm`: columna normalizada + similitud app-side + cola de revisión |
| 02d | [ADR-004 — Segmentación por institución/territorio](02-arquitectura/ADR/ADR-004_segmentacion-institucion.md) | Filtrado a nivel de fila en la capa Policy (no hay RLS nativa en MySQL); modelo de pertenencia |
| 02e | [ADR-005 — Evidencias por enlace a Drive](02-arquitectura/ADR/ADR-005_evidencias-enlace-drive.md) | La plataforma guarda el enlace + nombre normalizado; no aloja archivos; salvaguardas de PII |
| 03 | [Modelo de Datos](03-datos/03_modelo_de_datos.md) | ERD, diccionario de datos, DDL MySQL 8 completo (3NF), vistas calculadas, modelo de pertenencia, deduplicación |
| 04 | [Plan de Seguridad](04-seguridad/04_plan_de_seguridad.md) | OWASP Top 10 con snippets CI4 reales, RBAC + filtrado por institución, Shield, PII (LFPDPPP), auditoría |
| 05 | [Especificación de la API](05-api/05_especificacion_api.md) | Endpoints REST, contratos, códigos HTTP, rate limiting, paginación, trazabilidad |
| 06 | [Plan de Pruebas](06-pruebas/06_plan_de_pruebas.md) | Estrategia, casos por módulo, pruebas de seguridad, conciliación de migración, matriz de trazabilidad |
| 07 | [Roadmap por Sprints](07-roadmap/07_roadmap_sprints.md) | Sprint 0 → Fases 1-4, hitos verificables del MVP, riesgos, backlog post-MVP |
| 08 | [Identidad Visual y Design System](01-vision/08_identidad_visual_design_system.md) | Marca Participa Juárez, paleta completa, tipografía, tokens listos para Tailwind, componentes, accesibilidad AA |

---

## Decisiones clave del MVP

| Tema | Decisión |
|---|---|
| Stack | CodeIgniter 4.7 (API) + React 19/Vite + MySQL 8 + Redis, autoalojado en VPS ([ADR-001](02-arquitectura/ADR/ADR-001_stack-ci4-react-mysql.md)) |
| Autenticación | CodeIgniter Shield, cuentas individuales; access tokens para la SPA ([ADR-002](02-arquitectura/ADR/ADR-002_autenticacion-shield.md)) |
| Deduplicación | Clave determinista normalizada en servidor + similitud como sugerencia + cola de revisión ([ADR-003](02-arquitectura/ADR/ADR-003_deduplicacion-sin-postgres.md)) |
| Segmentación de acceso | Por institución/territorio, filtrado a nivel de fila en la capa Policy ([ADR-004](02-arquitectura/ADR/ADR-004_segmentacion-institucion.md)) |
| Evidencias | Enlace a Google Drive institucional + nombre normalizado generado ([ADR-005](02-arquitectura/ADR/ADR-005_evidencias-enlace-drive.md)) |
| Metas mensuales | Normalizadas (`metas_mensuales`: actividad × mes × valor), no 18 columnas |
| Personas | Tabla **derivada**, poblada por deduplicación; sin alta manual |
| KPIs | Vistas calculadas en vivo sobre registros reales (`control_registro = OK`); cero filas-plantilla |
| Catálogo de actividades | 236 actividades (174 P / 42 E / 20 R) como conteo oficial |
| Roles | RBAC: Capturista, Coordinación MEL, Dirección, Administrador |
| Captura offline | Fuera de alcance (la captura siempre ocurre en línea) |

---

## Cómo leer esta documentación

1. Empieza por el **SRS (01)** para entender qué hace el sistema (cadena MEL, roles, máquina de estados).
2. Sigue con la **Arquitectura (02)** y los **ADR (001–005)** para entender cómo se estructura y por qué se eligió cada pieza.
3. El **Modelo de Datos (03)** y la **API (05)** son la referencia de implementación (ERD/DDL y endpoints).
4. El **Plan de Seguridad (04)** es de lectura obligatoria antes de codificar cualquier flujo con datos de beneficiarios o autorización.
5. El **Plan de Pruebas (06)** define la red de seguridad, la conciliación de migración y la "Definición de Hecho".
6. El **Roadmap (07)** marca el orden seguro de construcción (cimientos de seguridad antes que funcionalidades).

---

## Consistencia y trazabilidad

Cada requisito funcional del SRS se mapea a reglas de negocio (`RN-xxx`), entidades del modelo de datos, endpoints de la API y casos de prueba. Las matrices de trazabilidad viven en los documentos 05 y 06. Todos los diagramas están en Mermaid. La línea base de conciliación de la migración (≈988 participaciones, ≈762 personas únicas, ≈279 eventos, ≈132 ejecuciones) es el criterio objetivo de que la plataforma reproduce los datos reales y no los KPIs inflados del Excel.

---

## Próximos pasos sugeridos

- [ ] Confirmar la conformidad LFPDPPP del tratamiento de PII de beneficiarios con asesor legal.
- [ ] Provisionar el VPS de staging (Nginx + PHP-FPM + MySQL 8 + Redis) y el proyecto de Drive institucional para evidencias.
- [ ] Cerrar las decisiones de diseño residuales (modelo de pertenencia institución/territorio, umbrales de similitud de dedup, ciclo POA de 18 meses) y registrarlas como solicitudes.
- [ ] Arrancar **Sprint 0 — Cimientos**.

---

*Documentación técnica de Sistema MEL · Comunidad Participa Juárez · v1.0 · 22-jun-2026*
