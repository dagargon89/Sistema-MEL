# Pendientes — Sistema MEL

> Registro de trabajo pendiente. Última actualización: 2026-06-29.
> Estado general: sistema real **operativo y poblado** (login por rol, datos reales del Excel, lectura y escritura verificadas). El código del MVP (Fases 1–4 / Sprints 0–7) está en `main`. Falta cerrar la **Fase 4 (Sprint 7): validación, endurecimiento y despliegue**.

## 1. Inmediato — CI rojo

- [ ] **Job `api-mysql` del CI falla.** El job `CI · API · migración + smoke de auth contra MySQL real` (en `.github/workflows/ci.yml`) falla en GitHub Actions, aunque el flujo (`migrate --all` + seed + login) pasa al reproducirlo localmente → el fallo es **ambiental** (timing del `spark serve`/`curl`, o setup en el runner). Decidir entre:
  - **A.** Obtener el log del step que falla y corregir con precisión (conservar el smoke de login completo).
  - **B.** Simplificar el job: quitar el smoke `serve`+`curl` (frágil en CI) y dejar `migrate --all` + `seed` + verificación SQL de que el admin tiene su grupo de Shield. Conserva lo crítico (cazar el bug de FK) sin fragilidad. **(Recomendado.)**
  - Nota: el job `CI · API · lint · stan · test` ya quedó **verde** tras arreglar PHPStan en `UsuariosRealesSeeder` (commit `b7f3768`).

## 2. Fase 4 / Sprint 7 — Despliegue y endurecimiento

- [ ] **Despliegue a staging** (la #4 que quedó sin empezar): VPS Linux, Nginx (sirve la SPA + proxy `/api` → CI4), HTTPS, `CORS_ALLOWED_ORIGINS` con el dominio real, `CI_ENVIRONMENT=production`, respaldos verificados, checklist de producción (doc 04 §6.3, roadmap Sprint 7).
- [ ] **Vulnerabilidades npm** (dev): 5 avisos en `esbuild`→`vite`→`vitest` (herramientas de build/test, no van al bundle productivo). `npm audit fix --force` sube a vite 7 (breaking) → actualizar y revalidar `build`/`test` con calma. `composer audit` (PHP) está limpio.
- [ ] **Observabilidad y auditoría final** (Sprint 7): revisar logs, `composer/npm audit` en CI, auditoría de seguridad de cierre.

## 3. Verificación pendiente

- [x] Lectura: 23 endpoints GET sanos por los 4 roles (0 errores 5xx; 403 = RBAC por diseño).
- [x] Escritura: 17 POST validan (422, sin 500); crear proceso real OK; scope bloquea fuga horizontal (403 "Fuera de su ámbito").
- [ ] **Flujos de escritura completos** (cobertura total, opcional): PATCH validar ejecución (máquina de estados), reclasificar P/E/R (auditoría), resolver duplicados end-to-end, crear metas. Probados a nivel de validación, no de flujo completo.

## 4. Datos — entidades fuera del ETL actual

- [ ] El ETL (`tools/excel_to_csv.py` + `mel:import`) carga la **cadena MEL core** (236 act / 274 eventos / 983 part / 700 personas). **Fuera de alcance** (requiere extender `MigracionService` + más hojas/CSV): `productos_entregables`, `metas`/`metas_mensuales`, `resultados`, incidencia (propuestas/procesos/compromisos/alianzas/hitos), verticales (shelter, sostenibilidad). Cargarlas es un sub-proyecto si se necesitan datos reales en esas secciones.

## 5. Follow-ups de calidad (de los reviews de código)

- [ ] **Constantes dead code** en `tools/mel_etl/sheets.py`: `ESTATUS_DIM`, `TIPO_PROG`, `ESTATUS_PROC`, `ESTATUS_EVENTO`, `ESTATUS_EJEC`, `CONTROL_EJEC` — definidas pero no consumidas (los lambdas de `_TRANSFORMS` inlinean los literales). Eliminar o cablear.
- [ ] **Tests del ETL** — gaps menores: header de `actividades.csv` solo verifica las 3 primeras columnas; faltan asserts de `#VALUE!` y `sexo` None/desconocido; no hay test de integración que afirme conteos (eventos=279) salvo vía CLI.
- [ ] **`escribir_csv`** (`tools/mel_etl/extract.py`): `os.makedirs(os.path.dirname(path))` no blindado para rutas sin directorio.
- [ ] **Idempotencia de `UsuariosRealesSeeder`**: verifica la tabla `usuarios`, no `auth_identities`; un fallo a mitad podría dejar identidad Shield duplicada. Bajo riesgo (seeder de dev).
- [ ] **Spec del ETL §3** lista dos columnas opcionales (`tipo_registro_participacion`, `control_registro`) en `ejecuciones.csv`/`agregadas.csv` que el extractor omite; alinear el spec o añadirlas.

## 6. UX

- [x] Botones "Cuentas demo" del login: arreglados para modo real (usan `MelDemo2026!`), mergeado.
- [ ] (Opcional) En un despliegue productivo, considerar ocultar del todo los botones/cuentas demo.

---

### Cómo correr el sistema real (referencia)

```bash
docker compose up -d                      # MySQL 8 + Redis
bash tools/cargar_datos_reales.sh         # recrea BD, migra, ETL del Excel, mel:import, usuarios
# backend:  cd apps/api && php spark serve
# frontend: cd apps/web && npm run dev     (con apps/web/.env.local → VITE_USE_MOCK=false)
# login: admin@demo.test / MelDemo2026!  (formulario; los 4 roles usan esa contraseña)
```

### Bugs de auth resueltos esta sesión (contexto)

Latentes porque el CI corría en SQLite y el login real nunca se había ejercido contra MySQL:
1. Tipo incompatible en FK `usuarios→users` de Shield (INT vs BIGINT).
2. `rol: null` en el login (seeder sin `addGroup`).
3. Permiso `data.viewAll` con mayúscula (Shield normaliza `can()` a minúsculas → `data.viewall`).
4. Filtro `Rbac` no eximía al administrador (superadmin).
