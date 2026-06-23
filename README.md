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

## Estado (Fase 0 — en progreso)

| Pista | Entregable | Estado |
|---|---|---|
| B (SPA) | Scaffold + biblioteca de componentes + 19 pantallas contra el mock | ✅ navegable y verificado |
| A (infra) | Monorepo, `docker-compose`, CI | ✅ |
| A (API) | Scaffold CI4 + ruta `/api/v1/health` | ✅ responde 200 |
| A (datos) | Migraciones base del esquema (doc 03) | ⏳ siguiente |
| A (auth) | Shield + RBAC + segmentación (Sprint 1) | ⏳ Fase 1 |

El plan por fases completo está descrito en [`Sistema MEL/07-roadmap`](Sistema%20MEL/07-roadmap/07_roadmap_sprints.md).
