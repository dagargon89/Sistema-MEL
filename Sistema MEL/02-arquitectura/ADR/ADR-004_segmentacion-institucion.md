# ADR-004 — Segmentación de acceso por institución/territorio

| | |
|---|---|
| **Estado** | Aceptado |
| **Fecha** | 22 de junio de 2026 |
| **Depende de** | [ADR-001](ADR-001_stack-ci4-react-mysql.md), [ADR-002](ADR-002_autenticacion-shield.md) |
| **Reemplaza** | Row Level Security de PostgreSQL (estudio de viabilidad) |

## 1. Contexto

CPJ decidió que un **capturista solo debe ver y editar los datos de su institución/territorio**, no el universo completo. Coordinación y dirección ven todo (según política). Esta es la decisión D-07 del análisis, resuelta a favor de la segmentación.

PostgreSQL resolvería esto con Row Level Security (RLS): políticas en la base que filtran filas según el usuario. ADR-001 eligió MySQL, que **no tiene RLS nativa**. Por tanto, el filtrado por fila debe implementarse en la aplicación, y debe ser **imposible de omitir por descuido**.

## 2. Decisión

Implementar la segmentación en la **capa de aplicación de CI4**, centralizada para que ninguna query la olvide:

| Componente | Responsabilidad |
|---|---|
| Tabla `usuario_institucion` | Relación N:N usuario ↔ institución (y opcionalmente territorio). Define el **ámbito** de cada usuario. |
| Filtro `scope-institucion` | Carga el ámbito del usuario autenticado al request (lista de `id_institucion` permitidas) tras el filtro de auth. |
| `PolicyServices` | Decide si el usuario puede acceder a un objeto concreto: compara la institución del objeto contra su ámbito. **Denegación por defecto.** |
| `Repositories` | **Toda** query de lectura/escritura recibe el ámbito y aplica `WHERE id_institucion IN (:ambito)` salvo que el rol sea coordinación/dirección/admin con permiso global. El filtro vive aquí, no en el controlador, para que sea estructural. |
| Roles globales | `coordinacion`, `direccion`, `administrador` pueden tener ámbito global (ven todas las instituciones); el `capturista` está siempre acotado. |

La institución de un registro operativo **se hereda de la actividad** (que ya pertenece a una institución vía la herencia estratégica), de modo que el filtro se aplica de forma consistente en toda la cadena MEL.

## 3. Mapeo de conceptos

| PostgreSQL RLS | CI4 + MySQL |
|---|---|
| `CREATE POLICY ... USING (institucion_id = current_setting('app.user_inst'))` | Filtro `scope-institucion` + `Repository::scopeInstituciones($ambito)` aplicado a cada query |
| `auth.uid()` en la política | `auth()->id()` + ámbito cargado en el request |
| `BYPASS RLS` para roles privilegiados | Permiso de Shield `data.viewAll` para coordinación/dirección/admin |
| filtrado garantizado por el motor | filtrado garantizado por centralizarlo en el Repository (capa única) + pruebas negativas |

## 4. Consecuencias

**Positivas**
- Aísla los datos de beneficiarios por institución: un capturista de una institución no puede leer PII de otra (mejor postura LFPDPPP).
- Centralizado en `Repositories`: un solo lugar que revisar y testear.
- Flexible: el ámbito es N:N, así que un usuario puede cubrir varias instituciones si la operación lo requiere.

**Negativas / trade-offs**
- **Sin red de seguridad del motor:** si una query nueva no pasa por el Repository, podría fugar datos. Se mitiga con (a) prohibición de queries crudas en controladores/servicios, (b) revisión de código y (c) pruebas negativas obligatorias de fuga entre instituciones (doc 06).
- Más complejidad en consultas y en los tableros (que deben respetar el ámbito).

**Neutrales**
- Coordinación y dirección operan como hoy (ven todo); el cambio solo acota al capturista.

## 5. Impacto en documentos

- `03_modelo_de_datos.md`: tabla `usuario_institucion`; nota de herencia de institución en la cadena.
- `04_plan_de_seguridad.md`: A01 (Broken Access Control) — filtrado por fila como control central; §3.3 RBAC + ámbito.
- `05_especificacion_api.md`: todos los listados respetan el ámbito; 403 al acceder fuera de ámbito.
- `06_plan_de_pruebas.md`: casos negativos de IDOR entre instituciones.

## 6. Implicaciones de seguridad

- Es el control central contra **fuga horizontal de datos** (IDOR entre instituciones): el riesgo más grave del modelo de datos sensible.
- Denegación por defecto: si el ámbito no se pudo determinar, la query no devuelve nada.
- Toda la decisión es código versionado y testeable; no hay configuración de motor que se desincronice del código.
