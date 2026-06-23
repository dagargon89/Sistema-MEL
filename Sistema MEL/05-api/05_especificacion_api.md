# 05 · Especificación de la API

| | |
|---|---|
| **Documento** | 05 — Especificación de la API |
| **Versión** | 1.0 |
| **Fecha** | 22 de junio de 2026 |
| **Auth** | CodeIgniter Shield — access token `Authorization: Bearer <token>` |
| **Base URL** | `https://mel.participajuarez.org/api/v1` |
| **Formato** | JSON (UTF-8) |
| **Depende de** | [SRS (01)](../01-vision/01_SRS_especificacion_requisitos.md), [Modelo de Datos (03)](../03-datos/03_modelo_de_datos.md), [Plan de Seguridad (04)](../04-seguridad/04_plan_de_seguridad.md) |

---

## 1. Convenciones

### 1.1 Versionado
Versión en la URL: `/api/v1`. Cambios incompatibles abren `/api/v2` sin romper `v1`.

### 1.2 Autenticación
1. `POST /auth/login` con email y contraseña → devuelve un **access token** de Shield.
2. El cliente adjunta el token en cada petición: `Authorization: Bearer <token>`.
3. El filtro `auth` (Shield `tokens`) lo verifica; el filtro `scope-institucion` carga el ámbito del usuario.
4. `POST /auth/logout` revoca el token actual.
Todas las rutas requieren token salvo `POST /auth/login` y `GET /health`.

### 1.3 Códigos de estado HTTP

| Código | Significado en este sistema |
|---|---|
| 200 | OK (lectura o actualización exitosa) |
| 201 | Recurso creado |
| 204 | Éxito sin cuerpo |
| 400 | Petición malformada (JSON inválido) |
| 401 | No autenticado (token ausente/inválido/revocado) |
| 403 | Autenticado pero sin permiso (rol o **fuera de ámbito de institución**) |
| 404 | Recurso inexistente (o fuera de ámbito, indistinguible por diseño) |
| 409 | Conflicto (p. ej. transición de estado ilegal, regla de cadena) |
| 422 | Validación fallida (campos, reglas de negocio) |
| 429 | Rate limit excedido |
| 500 | Error del servidor |

### 1.4 Formato de error estándar
```json
{
  "success": false,
  "message": "Datos inválidos",
  "errors": {
    "apellido_paterno": "El campo apellido_paterno es obligatorio.",
    "sexo": "El valor de sexo no es válido."
  }
}
```
Respuesta de éxito:
```json
{ "success": true, "data": { } , "pager": { } }
```

### 1.5 Rate limiting

| Grupo de endpoints | Límite |
|---|---|
| `POST /auth/login` | 5 / minuto / IP |
| Escritura (`POST`/`PUT`/`PATCH`/`DELETE`) | 60 / minuto / usuario |
| Lectura (`GET`) | 120 / minuto / usuario |

### 1.6 Paginación
Parámetros `?page=N&limit=M` (`limit` máx. 100, default 15). Respuesta incluye `pager`:
```json
"pager": { "currentPage": 1, "pageCount": 12, "total": 173, "perPage": 15 }
```
Filtros comunes en listados: `?institucion=&actividad=&periodo=&estatus=&control=` (siempre acotados al ámbito del usuario).

---

## 2. Autenticación y sesión

### POST /auth/login — Iniciar sesión
Valida credenciales y emite un access token.
**Autenticación:** pública. **Rate limit:** login.

**Request:**
```json
{ "email": "capturista@participajuarez.org", "password": "********" }
```
**Respuesta 200:**
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGci...",
    "user": { "id": 12, "nombre": "Ana López", "rol": "capturista" },
    "ambito": ["INS_00002"]
  }
}
```
**Errores:** 401 credenciales inválidas · 429 demasiados intentos.
**Seguridad:** bcrypt vía Shield; regeneración de sesión; throttling.

### POST /auth/logout — Cerrar sesión
Revoca el access token actual. **Auth:** Bearer. **Respuesta:** 204.

### GET /auth/me — Perfil y ámbito
Devuelve usuario, rol y ámbito de instituciones. **Auth:** Bearer. **Respuesta 200:** `{ data: { user, rol, ambito } }`.

---

## 3. Catálogos

### GET /catalogos/actividades — Listar actividades
Lista el catálogo, con eje/línea/componente/institución heredados; filtrable por `tipo` (P/E/R), `caso` (A–D) e `institucion`.
**Auth:** Bearer. **Rate limit:** lectura.
**Respuesta 200:**
```json
{ "success": true, "data": [
  { "id_actividad": "ACT_030", "nombre": "Asesoría a propuestas", "tipo_registro": "P",
    "caso_excepcional": null, "id_institucion": "INS_00002",
    "herencia": { "eje": "Eje 1", "linea": "Línea 2", "componente": "Comp 3", "institucion": "Inst B" } }
] }
```

### POST /catalogos/actividades — Crear actividad
**Auth:** Bearer + rol `coordinacion`/`administrador` (RF-CAT-010). **Errores:** 403 si capturista.

### PATCH /catalogos/actividades/{id}/tipo-registro — Reclasificar P/E/R
Cambia `tipo_registro`; exige rol `coordinacion`; registra en `solicitudes` y `auditoria` (RF-CAT-013).
**Request:** `{ "tipo_registro": "E", "motivo": "Reclasificación POA 2026" }`
**Respuesta 200** / **403** capturista / **409** si rompe una regla (p. ej. tiene ejecuciones nominales).

> Ejes, líneas, componentes e instituciones siguen el mismo patrón CRUD (`/catalogos/ejes`, etc.), todos restringidos a coordinación/admin para escritura.

---

## 4. Núcleo MEL — cadena referencial

### GET /procesos · POST /procesos
CRUD de procesos. `POST` exige `tipo_programacion` válido (RF-PROG-020). **Auth:** Bearer (capturista en su ámbito). Acotado por institución.

### POST /eventos-programados — Programar evento
Crea un evento; si `tipo_programacion ≠ SESION_UNICA` exige `id_proceso` (RF-PROG-021); rechaza fechas invertidas (RF-PROG-022).
**Request:**
```json
{ "id_actividad": "ACT_030", "id_proceso": 5, "tipo_programacion": "MULTI_SESION_PROGRAMADA",
  "fecha_inicio": "2026-07-01", "fecha_finalizacion": "2026-07-01", "modalidad": "Presencial",
  "lugar": "Centro Comunitario", "colonia": "Independencia" }
```
**Respuesta 201** / **422** fechas o proceso faltante / **403** fuera de ámbito.

### GET /eventos-programados — Calendario
Lista el universo programado, filtrable por actividad, institución, responsable, periodo y estatus (RF-PROG-023), acotado al ámbito.

### POST /ejecuciones — Registrar ejecución
Solo sobre un evento programado existente y no cancelado (RF-EJEC-030); no ofrece actividades tipo E (RF-EJEC-032); exige datos para validar (RF-EJEC-031).
**Request:**
```json
{ "id_evento_programado": 88, "fecha_ejecucion_real": "2026-07-01",
  "estatus_ejecucion": "ejecutada", "tipo_registro_participacion": "Nominal",
  "resumen_narrativo": "Se realizó la asesoría con 12 asistentes...",
  "evidencia_url": "https://drive.google.com/file/d/abc123/view" }
```
**Respuesta 201:**
```json
{ "success": true, "data": { "id_ejecucion": 132, "control_registro": "OK",
  "nombre_archivo_evidencia": "CPJ_EVID_20260701_88_ACT030_001.pdf" } }
```
**Errores:** 422 evento inexistente / actividad tipo E / falta dato → INCOMPLETO · 409 evento cancelado · 403 fuera de ámbito.
**Seguridad:** valida FK (RN-001), bloquea tipo E (RN-021), calcula `control_registro` en servidor.

### PATCH /ejecuciones/{id}/validacion — Transición de estado
Mueve `control_registro` según la máquina de estados ([SRS §4](../01-vision/01_SRS_especificacion_requisitos.md)). `REVISAR→OK` requiere rol `coordinacion`.
**Request:** `{ "control_registro": "OK", "detalle": "Revisado y validado" }`
**Respuesta 200** / **409** transición ilegal / **403** capturista intentando validar desde REVISAR.

### POST /participaciones — Registrar participación (con dedup)
Solo sobre una ejecución existente (RF-PART-040); calcula `id_datosbeneficiario` y asigna `id_persona` en servidor (RF-PART-041/042); marca sospechosos a cola (RF-PART-043).
**Request:**
```json
{ "id_ejecucion": 132, "nombres": "José", "apellido_paterno": "Pérez",
  "apellido_materno": "López", "anio_nacimiento": 1990, "sexo": "M",
  "telefono": "6561234567", "colonia_persona": "Independencia" }
```
**Respuesta 201:**
```json
{ "success": true, "data": { "id_participacion": 988, "id_persona": "PER_00762",
  "control_registro": "OK", "alerta_duplicado": "OK" } }
```
**Errores:** 422 ejecución inexistente o campos faltantes (→ INCOMPLETO) · 403 fuera de ámbito.
**Seguridad:** sin alta manual de personas (no existe `POST /personas`); dedup y `id_persona` solo en servidor; `$allowedFields` excluye `id_persona`/`control_registro`.

### POST /participaciones-agregadas — Conteo agregado
Registra un conteo no nominal; exige `periodo_corte` en casos A/B (RF-AGRE-051); marca `control_registro = AGREGADO` (RF-AGRE-050).
**Request:**
```json
{ "id_ejecucion": 140, "cantidad_participantes": 37, "sexo_grupo": "Mixto",
  "grupo_edad_aprox": "18-29", "motivo_no_nominal": "Feria comunitaria",
  "fuente_conteo": "Conteo en sitio", "periodo_corte": "M06" }
```
**Respuesta 201** / **422** falta `periodo_corte` en caso A/B.

---

## 5. Personas y cola de deduplicación

### GET /personas — Consolidado (solo coordinación)
Lista personas únicas con su `control_registro`. **Auth:** Bearer + `coordinacion`. **403** para capturista.

### GET /personas/duplicados — Cola de revisión
Lista los registros `alerta_duplicado = DUPLICADO_EN_CAPTURA` / `control_registro = REVISAR`, ordenados por score de similitud (RF-PART-043). **Auth:** `coordinacion`.
**Respuesta 200:**
```json
{ "success": true, "data": [
  { "id_participacion": 415, "nombres": "Maria", "apellido_paterno": "Garcia",
    "id_persona_sugerida": "PER_00120", "score_similitud": 0.94,
    "motivo": "Mismo teléfono, nombre similar" }
] }
```

### PATCH /personas/duplicados/{id} — Resolver duplicado
Coordinación decide: fusionar con una persona existente o confirmar como nueva. Queda en `auditoria` (RF-PART-043, RN-065).
**Request:** `{ "accion": "fusionar", "id_persona_destino": "PER_00120", "motivo": "Misma persona" }`
**Respuesta 200** / **403** capturista / **409** si la persona destino no existe.

> No existe `POST /personas`: las personas solo nacen de participaciones (RF-PART-044).

---

## 6. Productos / entregables (tipo E)

### POST /productos — Registrar entregable
Solo actividades tipo E (RF-PROD-060); hereda estructura; valida evidencia (RF-PROD-062).
**Request:**
```json
{ "id_actividad": "ACT_048", "nombre_producto": "Alianza firmada con X",
  "tipo_producto": "Alianza", "fecha_entrega": "2026-06-15", "estatus": "entregado",
  "evidencia_url": "https://drive.google.com/file/d/xyz/view" }
```
**Respuesta 201** / **422** si la actividad no es tipo E.

---

## 7. Metas y seguimiento

### GET /metas · POST /metas · PUT /metas/{id} — Gestión de metas
Captura de meta anual y mensual (M01–M18). **Auth:** Bearer + `coordinacion` (RF-META-070). **403** capturista.
**Request (POST):**
```json
{ "id_actividad": "ACT_030", "meta_anual_total": 120, "unidad_meta": "personas",
  "mensuales": [ {"mes":"M01","valor":10}, {"mes":"M02","valor":10} ] }
```

### GET /metas/seguimiento — Tablero de semáforo
Avance real vs. meta por actividad y mes, con semáforo; casos C/D no marcan rezago en meses intermedios (RF-META-071/072). Acotado al ámbito.
**Parámetros:** `?periodo=M06&institucion=&eje=`
**Respuesta 200:**
```json
{ "success": true, "data": [
  { "id_actividad": "ACT_030", "mes": "M06", "meta_mes": 10, "avance_mes": 9,
    "porcentaje": 90.0, "semaforo": "VERDE" },
  { "id_actividad": "ACT_094", "mes": "M06", "meta_mes": 0, "avance_mes": 0,
    "porcentaje": null, "semaforo": "CORTE_AL_CIERRE" }
] }
```

---

## 8. Incidencia

| Método/Ruta | Descripción | Auth |
|---|---|---|
| `GET·POST /incidencia/propuestas` | Propuestas asesoradas (ACT_030/031) | Bearer (ámbito) |
| `GET·POST /incidencia/procesos` | Procesos de incidencia (persisten hasta concluir) | Bearer |
| `GET·POST /incidencia/compromisos` | Compromisos; requieren proceso válido (RF-INC-081) | Bearer |
| `GET·POST /incidencia/alianzas` | Alianzas (ACT_048) | Bearer |
| `GET·POST /incidencia/hitos` | Bitácora de hitos (requiere proceso) | Bearer |

`POST /incidencia/compromisos` y `/hitos` devuelven **422** sin `id_proceso_incidencia` válido (RN-004).

---

## 9. Verticales

### POST /shelter/ocupacion — Ocupación mensual
Registra ocupación por tipo de espacio y mes; el % se calcula (RF-VERT-090).
**Request:** `{ "id_actividad": "ACT_224", "mes_periodo": "M06", "tipo_espacio": "Módulo individual", "capacidad_instalada": 20, "ocupacion": 17 }` → `pct_ocupacion: 85.0`.

### POST /sostenibilidad — Ingresos/costos por actividad y mes
Registra ingresos/costos/recursos; calcula utilidad, acumulados, % de avance y semáforo (RF-VERT-091).
**Request:** `{ "id_actividad": "ACT_230", "mes_periodo": "M06", "ingresos_brutos": 50000, "costos_directos": 30000, "costos_indirectos": 8000, "meta_anual": 240000 }`.

---

## 10. Resultados (tipo R)

### POST /resultados — Registrar resultado
Para actividades tipo R: indicador, línea base, valor, método, evidencia (RF-RES-100). **422** si la actividad no es tipo R.

---

## 11. Gobernanza

### GET·POST /solicitudes — Solicitudes
Cualquier usuario registra (RF-GOB-110); coordinación cambia estado y asigna responsable (RF-GOB-111).
**PATCH /solicitudes/{id}** `{ "estado": "resuelta", "responsable_atencion": 4, "comentarios": "Aplicado" }` — solo coordinación.

### GET /auditoria — Historial
Consulta del historial inmutable (RF-GOB-112). **Auth:** Bearer + `coordinacion`/`administrador` (dirección solo lectura). No existe endpoint de borrado.

### GET /evidencias/nombre — Generar nombre normalizado
Devuelve el nombre normalizado para una evidencia (RF-GOB-113): `CPJ_EVID_[fecha]_[id_evento]_[actividad]_[consecutivo].[ext]`.

---

## 12. Tableros y exportación

### GET /tableros/{tipo} — Tableros con KPIs reales
`tipo` ∈ `operativo|coordinacion|ejecutivo|analitico|shelter`. KPIs calculados sobre vistas con `control_registro = OK` (RF-TAB-120); filtrables por periodo/institución/eje/actividad (RF-TAB-121); acotados al ámbito.
**Respuesta 200 (ejecutivo):**
```json
{ "success": true, "data": {
  "beneficiarios_unicos": 762, "participaciones_nominales": 988,
  "participaciones_agregadas": 2185, "cobertura_total": 3173,
  "eventos_programados": 279, "ejecuciones": 132, "cumplimiento_ejecucion": 0.47 } }
```

### GET /export/fechac — Reporte de financiador (Fase 4)
Exporta el reporte para FECHAC en el formato acordado (RF-TAB-122). **Auth:** `coordinacion`/`direccion`.

---

## 13. Matriz de trazabilidad (RF → endpoint)

| Requisito | Endpoint(s) |
|---|---|
| RF-AUTH-001/005 | `POST /auth/login`, `POST /auth/logout` |
| RF-AUTH-004 | filtro `scope-institucion` en todos los listados |
| RF-CAT-010/013 | `/catalogos/*`, `PATCH /catalogos/actividades/{id}/tipo-registro` |
| RF-PROG-020…023 | `/procesos`, `/eventos-programados` |
| RF-EJEC-030…033 | `POST /ejecuciones`, `PATCH /ejecuciones/{id}/validacion` |
| RF-PART-040…046 | `POST /participaciones` |
| RF-AGRE-050…053 | `POST /participaciones-agregadas`, `GET /metas/seguimiento` |
| RF-PART-043/044 | `GET /personas/duplicados`, `PATCH /personas/duplicados/{id}` (sin `POST /personas`) |
| RF-PROD-060…062 | `POST /productos` |
| RF-META-070…072 | `/metas`, `GET /metas/seguimiento` |
| RF-INC-080/081 | `/incidencia/*` |
| RF-VERT-090/091 | `/shelter/ocupacion`, `/sostenibilidad` |
| RF-RES-100 | `POST /resultados` |
| RF-GOB-110…113 | `/solicitudes`, `GET /auditoria`, `GET /evidencias/nombre` |
| RF-TAB-120…123 | `GET /tableros/{tipo}`, `GET /export/fechac` |
