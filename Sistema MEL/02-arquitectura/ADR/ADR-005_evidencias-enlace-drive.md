# ADR-005 — Evidencias documentales por enlace a Google Drive

| | |
|---|---|
| **Estado** | Aceptado |
| **Fecha** | 22 de junio de 2026 |
| **Depende de** | [ADR-001](ADR-001_stack-ci4-react-mysql.md) |
| **Reemplaza** | Supabase Storage / alojamiento de archivos en la plataforma |

## 1. Contexto

Cada ejecución y cada producto entregable puede tener evidencia documental (fotos, listas de asistencia, informes). Hoy, el Excel guarda **el enlace** al Google Drive institucional más un **nombre de archivo normalizado**; los archivos viven en Drive, no en el libro. Hay dos caminos: que la plataforma aloje los archivos (almacenamiento propio) o que conserve el flujo actual de enlaces. CPJ eligió **conservar el enlace a Drive**.

## 2. Decisión

La plataforma **no aloja archivos**. Por cada evidencia guarda:

- `evidencia_url` — enlace estable al recurso en el Drive institucional.
- `nombre_archivo_evidencia` — nombre normalizado, que el sistema **genera automáticamente** con la convención `CPJ_EVID_[fecha]_[id_evento]_[actividad]_[consecutivo].[ext]` (RN-111/113).

El backend valida que la URL tenga formato válido (`valid_url_strict`) y deja registrado si está completa. La política de validación: si la evidencia es obligatoria para el módulo y falta o la URL es inválida, el `control_registro` no llega a `OK`.

## 3. Consecuencias

**Positivas**
- Cero infraestructura nueva de almacenamiento, respaldo de blobs ni gestión de cuotas.
- Mantiene el flujo que el personal ya conoce (Drive institucional).
- Reduce PII en reposo dentro de la plataforma: las listas de asistencia escaneadas no se duplican en el VPS.

**Negativas / trade-offs**
- La plataforma **no controla** la disponibilidad ni los permisos del archivo en Drive: si alguien mueve/borra el archivo o cambia su visibilidad, el enlace queda roto (mitigación: validación periódica de enlaces y marca de "evidencia no accesible").
- La trazabilidad del archivo en sí depende de Drive, no de la auditoría de la plataforma (la plataforma audita el enlace, no el contenido).

**Neutrales**
- Si en el futuro se decide alojar archivos, basta añadir un servicio de almacenamiento y un campo; el modelo ya separa URL y nombre.

## 4. Impacto en documentos

- `03_modelo_de_datos.md`: campos `evidencia_url`, `nombre_archivo_evidencia` en `ejecuciones`, `productos_entregables` e incidencia.
- `05_especificacion_api.md`: endpoint generador de nombre normalizado; validación de URL.
- `04_plan_de_seguridad.md`: nota de que el control de acceso al archivo vive en Drive; recomendación de permisos del Drive institucional.

## 5. Implicaciones de seguridad

- El control de acceso al **contenido** de la evidencia recae en el Drive institucional; CPJ debe garantizar que esas carpetas tienen permisos adecuados (no públicas).
- La plataforma trata `evidencia_url` como dato no confiable de entrada: se valida formato y se escapa siempre al renderizar; nunca se abre del lado servidor.
