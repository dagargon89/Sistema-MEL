# ADR-001 — Stack: CodeIgniter 4 + React/Vite + MySQL (autoalojado)

| | |
|---|---|
| **Estado** | Aceptado |
| **Fecha** | 22 de junio de 2026 |
| **Depende de** | — |
| **Reemplaza** | Recomendación de Supabase/PostgreSQL del estudio de viabilidad |

## 1. Contexto

El estudio de viabilidad (`Viabilidad_Plataforma_MEL_CPJ.md`) recomendó **Supabase (PostgreSQL) + React** porque entrega listas tres piezas que fueron justamente las que fallaron en Excel: integridad referencial, permisos por rol (RLS) y deduplicación con funciones de Postgres (`unaccent`, `pg_trgm`). El propio estudio dejó abierta una alternativa legítima: **CI4 + PostgreSQL autoalojado**, *"si el autoalojamiento total en servidor propio sin dependencia de un proveedor externo es un requisito duro de CPJ o de FECHAC"*.

CPJ decidió priorizar el **autoalojamiento total** y la consistencia con el estándar de ingeniería de la organización (mismo stack que las demás plataformas internas). El equipo posee experiencia en CodeIgniter 4 y React 19. La decisión concreta fue **CodeIgniter 4.7 + React/Vite + MySQL 8**.

Esto obliga a sustituir las tres piezas que Supabase/Postgres daban "gratis" por implementaciones propias o por equivalentes en MySQL. Este ADR documenta la decisión y las equivalencias; los detalles de cada sustitución viven en ADR-002 (auth), ADR-003 (dedup) y ADR-004 (segmentación).

## 2. Decisión

| Capa | Recomendación previa | Decisión adoptada |
|---|---|---|
| Backend / API | Supabase (REST/GraphQL autogenerada) + Edge Functions | **CodeIgniter 4.7**, API REST JSON pura |
| Base de datos | PostgreSQL (gestionado) | **MySQL 8** (autoalojado) |
| Autenticación | Supabase Auth | **CodeIgniter Shield** (ADR-002) |
| Permisos por fila | Row Level Security nativa | **Capa Policy/Repository en CI4** (ADR-004) |
| Deduplicación | `unaccent` + `pg_trgm` | **Columna normalizada + similitud app-side + cola** (ADR-003) |
| Tableros | Vistas materializadas Postgres | **Vistas MySQL + tablas-resumen por cola Redis** |
| Frontend | React 19 | React 19 + Vite 6 + TypeScript (sin cambio) |
| Hosting | Proveedor gestionado | **VPS Linux propio** (Nginx + PHP-FPM + MySQL + Redis) |

## 3. Mapeo de conceptos (Postgres/Supabase → CI4/MySQL)

| Concepto Postgres/Supabase | Equivalente en CI4 + MySQL |
|---|---|
| `unaccent(texto)` | Normalización determinista en un `Service` PHP (transliteración + `LOWER` + `TRIM`) que se persiste en una columna generada/calculada; colación `utf8mb4_0900_ai_ci` para comparaciones acento-insensibles |
| `pg_trgm` (similitud trigram) | `SOUNDEX` de MySQL como filtro grueso + Levenshtein/Jaro-Winkler en PHP como sugerencia ordenada en la cola de revisión |
| Row Level Security (`CREATE POLICY`) | Filtrado obligatorio por institución/territorio en `PolicyServices` + `Repositories` (toda query de lectura/escritura pasa por el ámbito del usuario) |
| Vista materializada | `VIEW` de MySQL para cálculo en vivo + tabla-resumen refrescada por `spark` command en cola cuando el volumen lo exija |
| Edge Function (deduplicación, metas) | `Service` de CI4 + `spark` command para recálculos masivos |
| Triggers de auditoría | `Events`/`Model callbacks` de CI4 que escriben en `auditoria` |
| Supabase Storage | No aplica: evidencias por enlace a Drive (ADR-005) |

## 4. Consecuencias

**Positivas**
- Autoalojamiento total: cero dependencia de un proveedor externo; los datos de beneficiarios (PII) viven en infraestructura controlada por CPJ, lo que simplifica el cumplimiento LFPDPPP.
- Consistencia con el estándar de ingeniería de la organización (mismo stack, mismas convenciones, misma documentación).
- El equipo trabaja sobre tecnología que ya domina (CI4 + React).
- Portabilidad: MySQL estándar, migraciones versionadas, exportable a CSV; sin lock-in de proveedor.

**Negativas / trade-offs aceptados**
- Hay que **construir a mano** lo que Supabase daba listo: auth (Shield mitiga mucho), permisos por fila (capa Policy) y deduplicación (Service). Es más código y más superficie de pruebas.
- Administración de servidor propia (hardening, respaldos, monitoreo) — antes era gestionada.
- MySQL es algo menos expresivo que Postgres para *fuzzy matching*; se compensa con lógica en PHP (ADR-003).
- No hay tiempo real "incluido"; el MEL no lo requiere (la captura no es colaborativa en vivo).

**Neutrales**
- React 19 + el modelo en estrella se conservan idénticos a lo que recomendaba el estudio; BI (Power BI/Tableau) se conecta igual, ahora a MySQL.

## 5. Impacto en documentos

- `README.md` y `CLAUDE.md`: stack y reglas (ya reflejado).
- `02_arquitectura_sistema.md`: todas las capas se describen en términos de CI4.
- `03_modelo_de_datos.md`: DDL en dialecto MySQL 8; columnas normalizadas para dedup.
- `04_plan_de_seguridad.md`: RBAC y filtrado por fila sustituyen a RLS; auth vía Shield.
- `05_especificacion_api.md`: contratos REST de CI4.

## 6. Implicaciones de seguridad

- **Aumenta** la superficie de código propio en autorización y deduplicación → se mitiga con pruebas negativas exhaustivas (doc 06) y denegación por defecto.
- **Reduce** la superficie externa: no hay políticas RLS ni reglas de proveedor que auditar fuera de la base; toda la autorización es código versionado y testeable en el repositorio.
- La ausencia de RLS nativa hace **crítico** que ninguna query omita el filtro por institución; se centraliza en `Repositories` para que sea imposible olvidarlo (ADR-004).
