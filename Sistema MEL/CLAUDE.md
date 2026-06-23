# CLAUDE.md — Sistema MEL (Comunidad Participa Juárez)

Guía operativa para trabajar en este repositorio. Léela completa antes de escribir código.

## Qué es esto

**Sistema MEL** es la plataforma de **Monitoreo, Evaluación y Aprendizaje** de Comunidad Participa Juárez (CPJ, Ciudad Juárez). Registra qué se programa, qué se ejecuta, quién participa y a quién se beneficia, lo vincula con la planeación estratégica (POA) y produce los indicadores que CPJ reporta a financiadores como FECHAC. Reemplaza un libro Excel de 44 hojas (v1.9) conservando su lógica y eliminando su deuda técnica. Es un MVP que se construye en 4 fases.

El núcleo del dominio es la **cadena referencial inviolable**:

```
proceso/grupo → evento programado → ejecución real → participación (1 persona) → persona única
   (opcional)       (calendario)        (lo ocurrido)     (lista de asistencia)     (derivada/deduplicada)
```

Sobre esa cadena el sistema **hereda** la estructura estratégica (eje→línea→componente→institución) desde la actividad elegida, **deduplica** personas para contar beneficiarios únicos, y **calcula** el avance de metas POA (M01–M18) con semáforo. Maneja ramas paralelas (entregables tipo E, casos excepcionales A–D) y subsistemas verticales (incidencia, shelter, sostenibilidad financiera).

## Stack (no improvisar otro)

| Capa | Tecnología |
|---|---|
| Backend | **CodeIgniter 4.7** — API REST JSON pura (`apps/api`) |
| Frontend | **React 19 + Vite 6 + TypeScript** — SPA (`apps/web`) |
| Estado cliente | TanStack Query (server-state) + Zustand (UI-state) |
| Estilos | Tailwind CSS 4 con los tokens del design system (doc 08) |
| Base de datos | **MySQL 8** — única fuente de verdad (identidad, autorización, dominio) |
| Autenticación | **CodeIgniter Shield** — cuentas individuales; sesión web + access tokens (`Authorization: Bearer`) para la SPA |
| Caché/cola/throttle | Redis |
| Evidencias | **Enlace a Google Drive institucional** (la plataforma guarda URL + nombre normalizado; no aloja archivos) |
| Despliegue | VPS Linux; Nginx sirve la SPA y hace proxy a `/api` |

## Reglas no negociables

1. **El cliente nunca es de fiar.** Toda validación de negocio y toda autorización viven en CI4. React solo da UX previa (esconder un botón no es seguridad). Jamás se decide un permiso, una transición de estado o un filtrado por institución con datos que vienen del SPA. *Por qué:* el frontend es código que corre en la máquina del usuario y puede manipularse; la única frontera de confianza es el servidor.
2. **MySQL es la única fuente de verdad.** No hay servicios externos de dominio. La identidad la gestiona Shield contra MySQL; las evidencias son enlaces externos (Drive), nunca autoridad de datos. El estado real y los permisos se recalculan siempre contra MySQL. *Por qué:* el problema raíz del Excel fue tener la lógica en un motor que no la garantizaba; aquí hay un solo motor responsable.
3. **Integridad referencial por construcción (OWASP A04 / A08).** La cadena MEL se sostiene con **claves foráneas reales** y `ON DELETE RESTRICT`, no con disciplina del usuario. Es físicamente imposible crear una ejecución sin evento programado, una participación sin ejecución, o un producto sobre una actividad que no sea tipo E. Ver `04-seguridad`. *Por qué:* convierte la "regla maestra" de los manuales en una invariante del motor, no en una recomendación.
4. **Seguridad por diseño (OWASP Top 10).** Ver `04-seguridad/04_plan_de_seguridad.md`. Resumen de los controles más críticos:
   - Autorización a nivel de **objeto y de fila**, no solo de ruta: `PolicyServices` + filtro `rbac:` + filtrado obligatorio por institución/territorio. **Denegación por defecto**.
   - Query Builder / sentencias preparadas **siempre**. Cero concatenación de SQL.
   - `$allowedFields` en cada Model (anti mass-assignment). Nunca `id`, ni `id_persona`, ni campos de estado/control que el servidor calcula.
   - React auto-escapa; `dangerouslySetInnerHTML` prohibido salvo DOMPurify.
   - PII de beneficiarios (nombre, teléfono, año de nacimiento, colonia) tratada como sensible: acceso restringido por rol y por institución, registrado en auditoría.
5. **Autenticación = CodeIgniter Shield.** Cuentas individuales; **sin contraseñas compartidas ni impresas** (el viejo `CPJ_MEL_2026` desaparece). La SPA usa access tokens de Shield (`Authorization: Bearer <token>`) verificados en cada petición por el filtro `tokens`/`chain`. *Por qué:* la "protección por contraseña en un manual" del Excel no era seguridad; Shield da identidad real y auditable.
6. **Segmentación por institución/territorio.** Cada usuario pertenece a una o más instituciones/territorios (`usuario_institucion`). El capturista solo ve y edita lo de su ámbito; coordinación y dirección ven el universo según política. El filtro se aplica **en el servidor**, en la capa Policy/Repository, nunca solo en la UI. Ver [ADR-004](02-arquitectura/ADR/ADR-004_segmentacion-institucion.md).
7. **Deduplicación del lado servidor.** `id_datosbeneficiario` se calcula en un `Service` al guardar la participación (normalización determinista de acentos/espacios), nunca a mano. Los posibles duplicados van a una **cola de revisión** con decisión trazable de coordinación. `personas` se reconstruye desde `participaciones`; no tiene formulario de alta. Ver [ADR-003](02-arquitectura/ADR/ADR-003_deduplicacion-sin-postgres.md).
8. **Asíncrono donde duele.** Correos, recálculos masivos de deduplicación, refresco de tablas-resumen y exportaciones pesadas van a cola (Redis). El request HTTP no espera por I/O lento.
9. **Transacciones ACID** en toda operación multi-tabla (`$db->transStart()/transComplete()`). La máquina de estados y las reglas de cadena se validan en el Service **antes** de persistir.
10. **Toda acción de escritura se audita** automáticamente en la tabla `auditoria` (append-only): quién, qué, cuándo, valor antes/después. Las decisiones sobre duplicados y reclasificaciones quedan trazadas.
11. **KPIs sobre datos reales.** Ningún tablero cuenta celdas no vacías ni filas-plantilla. Todo conteo es sobre registros con FK válida y `control_registro = OK`. No existen "filas-plantilla" en la base.

## Arquitectura en capas (backend)

`Filters` (cors, auth Shield `tokens`, rbac, scope-institucion, throttle, secureheaders) → `Controllers` delgados → `Validation`/DTO → `PolicyServices` (autorización objeto + fila) → `Services` (negocio + transacciones + máquina de estados + dedup) → `Repositories` (queries optimizadas, filtrado por institución, sin N+1) → `Models/Entities` (mapeo, `$allowedFields`) → MySQL. Efectos secundarios vía `Events`; trabajo pesado vía `spark` commands / cola Redis.

## Comandos

```bash
# Backend (apps/api)
composer install
cp env.example .env                 # rellenar secretos de BD, Redis, encryption.key
php spark migrate --all             # crea el esquema (doc 03)
php spark shield:setup              # instala las tablas de Shield (auth, identities, tokens)
php spark db:seed InitialSeeder     # roles, catálogos base, usuario admin inicial
php spark mel:import                # importa y concilia los datos del Excel (doc 11/migración)
php spark serve                     # API en http://localhost:8080
composer test                       # PHPUnit
vendor/bin/phpstan analyse          # análisis estático (nivel 8 objetivo)

# Frontend (apps/web)
npm install
cp .env.example .env.local          # VITE_API_URL
npm run dev                         # SPA en http://localhost:5173 (proxy /api → 8080)
npm run build
npm run typecheck && npm run lint

# Infra local
docker compose up -d                # MySQL 8 + Redis
```

## Identidad visual (obligatoria)

La marca es **Participa Juárez**. Usar SIEMPRE los tokens de `01-vision/08_identidad_visual_design_system.md`:
- **Morado `#53155a`** = primario (CTA, activos). **Lima `#dbec57`** = realce, **con texto morado oscuro `#3A0F40`, nunca blanco** (el lima sobre blanco no pasa contraste AA).
- Secundario morado claro `#7A3B82`. Semáforo de metas y `control_registro` con verdes/ámbar/rojos verificados AA.
- Tailwind: mapear los tokens en `@theme`. Regla de lint: prohibir `text-white` sobre fondo lima.

## Orden de lectura de la documentación

1. `README.md` — panorama e índice.
2. `01-vision/01_SRS_*.md` — qué hace el sistema (requisitos, roles, máquina de estados, casos A–D).
3. `02-arquitectura/02_arquitectura_sistema.md` + ADR **001** (stack), **002** (Shield), **003** (dedup), **004** (segmentación), **005** (evidencias) — cómo y por qué.
4. `03-datos/03_modelo_de_datos.md` y `05-api/05_especificacion_api.md` — referencia de implementación (ERD/DDL y endpoints).
5. `04-seguridad/04_plan_de_seguridad.md` — **lectura obligatoria** antes de tocar cualquier flujo con PII o autorización.
6. `06-pruebas/06_plan_de_pruebas.md` — red de seguridad, conciliación de migración y Definición de Hecho.
7. `07-roadmap/07_roadmap_sprints.md` — orden seguro de construcción (Sprint 0 → Fase 4).

> Los ADR registran decisiones. Si algo en un documento contradice un ADR, **prevalece el ADR**.

## Orden de construcción (resumen del roadmap)

Sprint 0 cimientos → Auth+RBAC+segmentación (Shield) → Catálogos+herencia → Cadena MEL (núcleo) + dedup + cola → Migración+conciliación → Metas+productos+tableros → Incidencia+verticales → Resultados+reportería FECHAC+endurecimiento. **Nunca** construir una funcionalidad sensible antes que su control de seguridad.

## Definición de Hecho (por historia)

Pasa estática (PHPStan / ESLint+tsc), tiene pruebas unitarias y de feature que cubren sus criterios de aceptación, las pruebas de seguridad relevantes pasan (IDOR, escalada de rol, fuga entre instituciones, token inválido), no introduce N+1 en rutas calientes, los conteos cuadran con la línea base de conciliación cuando aplica, y la documentación afectada queda actualizada.

## Estructura del repo

```
sistema-mel/
├── CLAUDE.md            ← este archivo
├── README.md
├── 01-vision/ … 07-roadmap/   ← documentación técnica (fuente de verdad)
├── apps/
│   ├── api/             ← CodeIgniter 4 (API + Shield)
│   └── web/             ← React 19 + Vite (SPA)
└── docker-compose.yml
```

> Esta documentación es la fuente de verdad. El código se construye siguiendo los documentos y el roadmap, no al revés.
