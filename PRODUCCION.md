# Checklist de producción — Sistema MEL

Cierre de la **Fase 4** (doc 07, Sprint 7) y referencia operativa para el lanzamiento
(doc 04 §6.3). Marca lo verificado en código vs. lo que se ejecuta en el VPS de staging/producción.

## Construido y verificado (en este repositorio, CI verde)

- [x] **Cadena referencial por construcción**: FK reales `ON DELETE RESTRICT` en toda la
      cadena (proceso → evento → ejecución → participación → persona) y en metas, productos,
      incidencia, verticales y resultados. Imposible crear huérfanos.
- [x] **Autenticación individual** (Shield, access tokens) — sin contraseñas compartidas.
- [x] **Autorización objeto + fila**: filtros `tokens`, `rbac:`, `scope-institucion`,
      `throttle`; **denegación por defecto**; segmentación por institución en la capa Repository (ADR-004).
- [x] **Deduplicación en servidor** (ADR-003): clave determinista, consolidación,
      **cola de revisión** (nunca autofusión); `personas` derivada, sin alta manual.
- [x] **Máquina de estados** `control_registro` validada en Service; `REVISAR→OK` solo coordinación.
- [x] **KPIs sobre `control_registro = OK`**: tableros y reporte FECHAC como cálculos en vivo;
      ningún conteo proviene de filas-plantilla.
- [x] **Auditoría append-only** de toda escritura (quién/qué/cuándo + antes/después); sin endpoint de borrado.
- [x] **Migración saneada** (`spark mel:import`): limpia `#REF!`, descarta filas-plantilla,
      regenera personas por dedup, concilia contra la línea base.
- [x] **Calidad**: PHPStan nivel 8 limpio; PHPUnit (86 pruebas) incl. IDOR/escalada/fuga entre
      instituciones, RN-001..004/020/021, QA1–QA7; web typecheck/lint/test/build. Todo en CI.
- [x] **Contrato `api.ts` íntegro**: los 49 métodos implementados en mock y real (la SPA no
      distingue origen vía `VITE_USE_MOCK`).

## Pendiente fuera de este entorno (staging/producción, requiere infraestructura)

- [ ] `docker compose up` (MySQL 8 + Redis) y `php spark migrate --all` contra **MySQL real**.
- [ ] `php spark shield:setup` + `db:seed` del admin inicial con secreto real (no `MelDemo2026!`).
- [ ] Cargar el Excel v1.9 en `apps/api/data/excel/` (PII, no versionado) y `php spark mel:import`;
      **conciliar** con la línea base ≈988/762/279/132 (doc 06 §4); ningún tablero en 1000/100%.
- [ ] HTTPS + cabeceras seguras + CORS afinado; `CI_ENVIRONMENT=production`; `encryption.key`.
- [ ] Conformidad **LFPDPPP** del tratamiento de PII (revisión legal); aviso de privacidad.
- [ ] Observabilidad (logs centralizados), **respaldos verificados** de MySQL, monitoreo.
- [ ] Alertas de plazos (día 20 / lunes siguiente) vía cola Redis — opcional (doc 07 Sprint 7).
- [ ] Conector BI documentado (modelo estrella sobre MySQL) y entrega del paquete FECHAC.
- [ ] Auditoría de seguridad final + `composer audit` / `npm audit` sin vulnerabilidades críticas.
- [ ] Sesión de validación de UX con Coordinación MEL (doc 09 §8).

> El código hace cumplir las invariantes de seguridad y datos **por construcción**; esta lista
> cubre la puesta en operación, que depende del VPS, MySQL/Redis y decisiones institucionales.
