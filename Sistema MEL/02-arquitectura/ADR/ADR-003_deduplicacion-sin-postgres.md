# ADR-003 — Deduplicación de personas sin PostgreSQL

| | |
|---|---|
| **Estado** | Aceptado |
| **Fecha** | 22 de junio de 2026 |
| **Depende de** | [ADR-001](ADR-001_stack-ci4-react-mysql.md) |
| **Reemplaza** | Deduplicación con `unaccent` + `pg_trgm` (estudio de viabilidad) |

## 1. Contexto

El conteo institucional clave es **beneficiarios únicos = `COUNT(DISTINCT id_persona)`**. En el Excel, `id_persona` deriva de `id_datosbeneficiario`, una clave difusa determinista construida concatenando fragmentos normalizados de los datos de la persona:

```
id_datosbeneficiario = UPPER(
    4 letras apellido paterno + 4 apellido materno + 4 nombre
    + año_nacimiento + inicial de sexo + 4 colonia + últimos 4 del teléfono )
```

La clave tiene dos debilidades conocidas: depende de teléfono y colonia (cambiarlos genera falsos "nuevos"), y los acentos/espacios generan claves distintas para la misma persona. Hoy hay **220 duplicados marcados (22.6%)**. El estudio recomendó resolverlo con `unaccent` y `pg_trgm` de PostgreSQL; ADR-001 eligió MySQL, que no tiene esas funciones.

## 2. Decisión

Implementar la deduplicación en un **`Service` del lado servidor** (`DeduplicacionService`), no en la base, con tres capas:

1. **Normalización determinista (reemplaza `unaccent`).** Antes de construir la clave, cada fragmento se normaliza en PHP: transliteración de acentos (á→a, ñ→n, etc.), `mb_strtoupper`, colapso de espacios, eliminación de caracteres no alfanuméricos. La clave resultante se persiste en `participaciones.id_datosbeneficiario` y se indexa.
2. **Asignación determinista de `id_persona`.** Al guardar una participación se calcula la clave; si ya existe → se reusa el `id_persona`; si es nueva → se crea `PER_#####`. Esto ocurre dentro de la transacción de alta de la participación.
3. **Similitud como sugerencia (reemplaza `pg_trgm`).** Para casos sospechosos (clave casi-igual, teléfono compartido con persona distinta, datos incompletos) el Service calcula similitud con `SOUNDEX` (filtro grueso en MySQL) + Levenshtein/Jaro-Winkler en PHP, y manda el registro a una **cola de revisión** (`alerta_duplicado = DUPLICADO_EN_CAPTURA`, `control_registro = REVISAR`) ordenada por similitud descendente. La fusión nunca es automática: coordinación decide y la decisión queda en `auditoria`.

## 3. Mapeo de conceptos

| PostgreSQL | MySQL + CI4 |
|---|---|
| `unaccent('José')` → `Jose` | `DeduplicacionService::normalizar()` en PHP (transliteración) + columna persistida |
| comparación acento-insensible | colación `utf8mb4_0900_ai_ci` en columnas de texto comparado |
| `similarity(a,b)` (`pg_trgm`) | `SOUNDEX(a)=SOUNDEX(b)` (grueso) + Levenshtein/Jaro-Winkler PHP (fino, como score) |
| índice GIN trigram | índice B-Tree sobre `id_datosbeneficiario` (clave exacta) + `SOUNDEX` precomputado opcional |
| función servidor recalcula `id_persona` | `spark mel:dedup` (recálculo masivo) + cálculo en línea al guardar |

## 4. Consecuencias

**Positivas**
- La clave normalizada corrige el problema de acentos del Excel (mismo origen, una sola identidad).
- La cola de revisión con score hace **trazable** cada decisión de fusión (hoy no lo era).
- `personas` deja de estar congelada: se reconstruye desde `participaciones` por el Service, siempre actualizada.

**Negativas / trade-offs**
- La similitud fina corre en PHP, no en la base; para recálculos masivos (migración) se hace por lotes en cola para no bloquear.
- `SOUNDEX` está afinado al inglés; para español se usa solo como filtro grueso, con la distancia de edición como criterio real.
- Persisten las debilidades de fondo de la clave (teléfono/colonia faltantes); se mitigan con la cola, no se eliminan (ver decisiones D-04/05/06 del análisis).

**Neutrales**
- El algoritmo de clave se conserva igual al del Excel para que la conciliación de migración sea comparable; solo se le añade la normalización.

## 5. Impacto en documentos

- `03_modelo_de_datos.md`: columna `id_datosbeneficiario` normalizada e indexada; `personas` derivada; cola en `participaciones` (`alerta_duplicado`, `control_registro`).
- `05_especificacion_api.md`: endpoints de cola de duplicados y resolución.
- `06_plan_de_pruebas.md`: casos de normalización (acentos, espacios), reuso de `id_persona`, envío a cola, conciliación a ≈762 únicas.

## 6. Implicaciones de seguridad

- La deduplicación toca PII (nombre, teléfono, año, colonia): el `Service` corre solo del lado servidor y la cola es visible únicamente para coordinación (RBAC).
- Ninguna fusión automática: se evita el riesgo de colapsar dos personas reales en una por un falso positivo.
