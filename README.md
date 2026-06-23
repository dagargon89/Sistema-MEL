# Sistema MEL — Comunidad Participa Juárez

Plataforma de **Monitoreo, Evaluación y Aprendizaje** (MEL) de CPJ. Registra qué se
programa, qué se ejecuta, quién participa y a quién se beneficia; lo vincula con la
planeación estratégica (POA) y produce indicadores verídicos. Reemplaza el libro Excel
de 44 hojas eliminando sus fallas de raíz (KPIs inflados, integridad frágil, contraseñas
compartidas) **por construcción**.

> La documentación de ingeniería es la **fuente de verdad** y vive en [`Sistema MEL/`](Sistema%20MEL/)
> (SRS, arquitectura + ADR, modelo de datos, seguridad, API, pruebas, roadmap, design system).
> Empieza por [`Sistema MEL/CLAUDE.md`](Sistema%20MEL/CLAUDE.md).

## Estructura del monorepo

```
.
├── apps/
│   ├── api/            # Backend — CodeIgniter 4.7 (API REST JSON + Shield). MySQL 8.
│   └── web/            # Frontend — React 19 + Vite 6 + TypeScript + Tailwind 4 (SPA).
├── docker-compose.yml  # MySQL 8 + Redis para desarrollo local.
├── Sistema MEL/        # Documentación técnica (fuente de verdad).
└── .github/workflows/  # CI: typecheck/lint/build (web) + lint/rutas (api).
```

## Estrategia de construcción (Demo-First v2 · híbrido)

El SPA (`apps/web`) se construye contra un **contrato congelado** (`src/lib/api.ts`) cuyo
origen de datos es intercambiable vía `VITE_USE_MOCK`:

- `VITE_USE_MOCK=true` (default) → mock en memoria (`db.json`, espejo del DDL). Permite
  recorrer toda la cadena MEL **sin backend** y validar la UX con el stakeholder.
- `VITE_USE_MOCK=false` → API real CI4. Conforme el backend madura, se rellena
  `api.real.ts` módulo a módulo; **las pantallas no se reescriben**.

## Cómo correr

### Frontend (SPA, sin backend)

```bash
cd apps/web
npm install
npm run dev          # http://localhost:5173 (datos simulados)
npm run build && npm run typecheck && npm run lint
```

Cuentas demo (cualquier contraseña): `capturista@demo.test`, `coordinacion@demo.test`,
`direccion@demo.test`, `admin@demo.test`.

### Backend (API)

```bash
docker compose up -d            # MySQL 8 + Redis
cd apps/api
composer install
cp env.example .env             # rellenar secretos
php spark serve                 # http://localhost:8080  → GET /api/v1/health
```

## Estado (Fase 3 — incidencia y verticales, en progreso)

Fase 0, **Fases 1–2 completas** (Sprints 1–5) y el **Sprint 6** de Fase 3 están implementados y verificados (CI verde).

| Pista | Entregable | Estado |
|---|---|---|
| B (SPA) | Scaffold + biblioteca de componentes + 19 pantallas contra el mock | ✅ navegable y verificado |
| A (infra) | Monorepo, `docker-compose`, CI | ✅ |
| A (API) | Scaffold CI4 + ruta `/api/v1/health` | ✅ responde 200 |
| A (datos) | Migraciones base (dimensiones + gobernanza) + **cadena MEL** (procesos→…→personas), FK RESTRICT | ✅ verificado en SQLite (PHPUnit) |
| A (calidad) | Gates en CI: PHPUnit + PHPStan nivel 8 (api), Vitest (web) | ✅ |
| A (auth) · Sprint 1 | Shield (access tokens) + RBAC + segmentación por institución (ADR-004) | ✅ |
| A (catálogos) · Sprint 2 | Actividades con herencia + alta/reclasificación auditada; dimensiones | ✅ cableado en `api.real.ts` |
| A (cadena MEL) · Sprint 3 | Procesos→eventos→ejecuciones→participaciones, máquina de estados, **deduplicación** (ADR-003) | ✅ cableado en `api.real.ts` |
| A (migración) · Sprint 4 | `spark mel:import` (limpia `#REF!`, descarta plantillas, regenera personas, **concilia**) | ✅ mecanismo verificado vs fixture |
| A (metas/tableros) · Sprint 5 | Metas POA + seguimiento (semáforo 90/75, casos C/D), productos tipo E, tableros sobre `control=OK` | ✅ cableado en `api.real.ts` |
| A+B (incidencia/verticales) · Sprint 6 | Incidencia (RN-004) + shelter/sostenibilidad (indicadores calculados); **contrato ampliado** (api.ts/mock/real) | ✅ cableado en `api.real.ts` |

> **Carga real (fuera de este entorno):** colocar los CSV del Excel v1.9 en `apps/api/data/excel/`
> (PII, no se versionan) y correr `php spark mel:import` contra **MySQL real** (`docker compose up`);
> la conciliación debe cuadrar con la línea base ≈988/762/279/132 (doc 06 §4). Aquí el mecanismo se
> valida en SQLite con un fixture representativo. Pendiente también la **sesión de validación de UX**
> con Coordinación MEL (doc 09 §8).
>
> **Siguiente:** Fase 4 — resultados (tipo R), reportería FECHAC y endurecimiento (Sprint 7).

El plan por fases completo está descrito en [`Sistema MEL/07-roadmap`](Sistema%20MEL/07-roadmap/07_roadmap_sprints.md).
