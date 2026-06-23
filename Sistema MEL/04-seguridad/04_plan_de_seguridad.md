# 04 · Plan de Seguridad

| | |
|---|---|
| **Documento** | 04 — Plan de Seguridad |
| **Versión** | 1.0 |
| **Fecha** | 22 de junio de 2026 |
| **Marco** | OWASP Top 10 (2021), OWASP ASVS, LFPDPPP (México) |
| **Depende de** | [SRS (01)](../01-vision/01_SRS_especificacion_requisitos.md), [Arquitectura (02)](../02-arquitectura/02_arquitectura_sistema.md), [Modelo de Datos (03)](../03-datos/03_modelo_de_datos.md), [ADR-002](../02-arquitectura/ADR/ADR-002_autenticacion-shield.md), [ADR-004](../02-arquitectura/ADR/ADR-004_segmentacion-institucion.md) |

> Lectura obligatoria antes de codificar cualquier flujo con PII de beneficiarios o con autorización. Este sistema maneja datos personales de población vulnerable atendida por una ONG; la postura de seguridad es estricta por diseño.

---

## 1. Postura de seguridad

### 1.1 Activos a proteger

| Activo | Criticidad | Justificación |
|---|---|---|
| PII de beneficiarios (nombre, teléfono, año nacimiento, colonia) | **Crítica** | Datos personales de población atendida; su fuga causa daño directo y viola la LFPDPPP. |
| Identidad de personas consolidadas (`personas`, dedup) | **Crítica** | Vincula a una persona con su historial de participación; sensible. |
| Credenciales y tokens (Shield) | **Crítica** | Su compromiso da acceso al sistema completo. |
| Integridad de los indicadores (conteos para FECHAC) | **Alta** | Cifras infladas o manipuladas dañan la credibilidad institucional y el financiamiento. |
| Auditoría (`auditoria`) | **Alta** | Es la prueba de quién hizo qué; debe ser inmutable. |
| Catálogos y metas (POA) | **Media** | Cambios indebidos distorsionan la planeación; controlados por rol. |
| Enlaces de evidencia (Drive) | **Media** | El contenido vive en Drive; la plataforma protege el enlace y su contexto. |

### 1.2 Actores de amenaza

- **Usuario autenticado malicioso o negligente** (capturista que intenta ver datos de otra institución, o escalar privilegios).
- **Atacante externo** sin credenciales (fuerza bruta de login, inyección, abuso de API).
- **Escalada horizontal** (IDOR): acceder a registros de otra institución (riesgo principal del modelo segmentado).
- **Escalada vertical**: un capturista intentando acciones de coordinación/admin.
- **Robo de token** (XSS, almacenamiento inseguro en el cliente).
- **Insider con acceso a infraestructura** (mitigado por permisos mínimos y auditoría).

---

## 2. OWASP Top 10 — Controles aplicados

### A01 · Broken Access Control

**Riesgo específico.** El sistema segmenta por institución/territorio sin RLS nativa de MySQL (ADR-004); una consulta que olvide el filtro fuga PII de otra institución (IDOR horizontal). También: un capturista podría intentar acciones de coordinación.

**Controles.**

1. **Filtrado por ámbito centralizado en el Repository** (la fuente única que arma queries). Denegación por defecto: sin ámbito, no devuelve nada.

```php
// app/Repositories/EjecucionRepository.php
public function find(int $id, array|string $ambito): ?array
{
    $builder = $this->db->table('ejecuciones e')
        ->select('e.*, a.id_institucion')
        ->join('eventos_programados ev', 'ev.id_evento_programado = e.id_evento_programado')
        ->join('actividades a', 'a.id_actividad = ev.id_actividad')
        ->where('e.id_ejecucion', $id);

    if ($ambito !== 'ALL') {                 // roles globales: 'ALL'
        if ($ambito === []) { return null; } // denegación por defecto
        $builder->whereIn('a.id_institucion', $ambito);
    }
    return $builder->get()->getFirstRow('array'); // null si está fuera de ámbito
}
```
*Vive en:* Repository.

2. **Autorización a nivel de objeto en PolicyServices** (no solo de ruta):

```php
// app/Services/PolicyService.php
public function puedeCapturarEnEjecucion(\CodeIgniter\Shield\Entities\User $u, int $idEjecucion): bool
{
    if ($u->can('data.viewAll') && $u->inGroup('coordinacion','administrador')) return true;
    $ambito = model('UsuarioInstitucionModel')->institucionesDe($u->id);
    $ejec   = service('ejecucionRepository')->find($idEjecucion, $ambito);
    return $ejec !== null; // si no está en su ámbito, find() devolvió null
}
```
*Vive en:* Service (Policy).

3. **Filtro `rbac` por ruta** (grupo de Shield requerido) + `$autoRoute = false`. *Vive en:* Filter / Routes.

### A02 · Cryptographic Failures

**Riesgo específico.** Credenciales y PII en tránsito o en reposo sin cifrar; tokens débiles.

**Controles.**

1. **HTTPS forzado** y HSTS:
```php
// app/Config/App.php
public bool $forceGlobalSecureRequests = true; // producción
```
2. **Hashing de contraseñas con Shield** (bcrypt/argon2id; nunca texto plano). Shield gestiona el rehash.
3. **Servicio de cifrado de CI4** para cualquier dato sensible que requiera cifrado app-side; clave solo en `.env`:
```
encryption.key = hex2bin:<clave-de-64-hex>
```
*Vive en:* Config / Shield / Service.

### A03 · Injection

**Riesgo específico.** SQL injection vía campos de captura; XSS vía datos de beneficiario renderizados en la SPA o en reportes.

**Controles.**

1. **Query Builder / sentencias preparadas siempre**; cero concatenación:
```php
// CORRECTO
$db->table('participaciones')->where('id_datosbeneficiario', $clave)->get();
// PROHIBIDO: "... WHERE id = '$id'"
```
2. **Validación declarativa** de toda entrada antes de tocar la base (tipos, rangos, `in_list`, `valid_url_strict` para evidencias).
3. **Escape de salida**: React auto-escapa; `dangerouslySetInnerHTML` prohibido salvo DOMPurify. En cualquier render del lado servidor (correos, export), `esc($valor)`.
*Vive en:* Repository, Validation, React/Vista.

### A04 · Insecure Design

**Riesgo específico.** El diseño debe hacer **imposible** crear huérfanos o KPIs inflados, no solo desaconsejarlo.

**Controles.**

1. **Integridad referencial por construcción**: FK con `ON DELETE RESTRICT` en toda la cadena (doc 03). Probado en doc 06.
2. **Máquina de estados validada en el Service antes de persistir** (no se confía en el cliente para el estado):
```php
// app/Services/EjecucionService.php
public function validar(int $id, string $destino, \CodeIgniter\Shield\Entities\User $u): Resultado
{
    $estados = ['REVISAR' => ['OK','INCOMPLETO'], 'INCOMPLETO' => ['OK','REVISAR']];
    $actual  = $this->repo->estado($id);
    if (! in_array($destino, $estados[$actual] ?? [], true)) {
        return Resultado::error('Transición de estado no permitida.');
    }
    if ($destino === 'OK' && $actual === 'REVISAR' && ! $u->inGroup('coordinacion')) {
        return Resultado::error('Solo coordinación valida desde REVISAR.'); // RN máquina de estados
    }
    // ... persistir + auditar
}
```
3. **Tableros sobre vistas** que cuentan solo `control_registro = OK` (sin filas-plantilla). *Vive en:* DDL, Service, Vistas.

### A05 · Security Misconfiguration

**Riesgo específico.** Toolbar de debug o errores expuestos en producción; CORS abierto; cabeceras ausentes.

**Controles.**

1. **Cabeceras de seguridad globales** (`secureheaders`):
```php
// app/Config/SecureHeaders.php
public array $headers = [
    'X-Content-Type-Options'    => 'nosniff',
    'X-Frame-Options'           => 'DENY',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    'Referrer-Policy'           => 'strict-origin-when-cross-origin',
    'Content-Security-Policy'   => "default-src 'self'; frame-ancestors 'none'",
];
```
2. **CORS acotado** al origen de la SPA:
```php
// app/Config/Cors.php
public array $default = [
    'allowedOrigins' => ['https://mel.participajuarez.org'],
    'allowedHeaders' => ['Content-Type','Authorization'],
    'allowedMethods' => ['GET','POST','PUT','PATCH','DELETE','OPTIONS'],
    'supportsCredentials' => false,
];
```
3. **Producción endurecida**: `CI_ENVIRONMENT=production`, toolbar off, `display_errors=0`, `$autoRoute=false`. *Vive en:* Config / despliegue.

### A07 · Identification and Authentication Failures

**Riesgo específico.** Contraseña compartida (legado Excel), fuerza bruta, tokens no revocables.

**Controles.**

1. **CodeIgniter Shield**: cuentas individuales, sin contraseñas compartidas. Access tokens para la SPA verificados en cada petición.
2. **Throttling de login** (Shield + filtro de rate limit):
```php
// app/Filters/RateLimitFilter.php
public function before(\CodeIgniter\HTTP\RequestInterface $request, $arguments = null)
{
    $throttler = service('throttler');
    if (! $throttler->check(md5('login_' . $request->getIPAddress()), 5, MINUTE)) {
        return service('response')->setStatusCode(429)
            ->setJSON(['message' => 'Demasiados intentos. Espera un minuto.']);
    }
}
```
3. **Regeneración de sesión al login** y **revocación de token al logout** (Shield). *Vive en:* Shield, Filter, Auth Controller.

### A08 · Software and Data Integrity Failures

**Riesgo específico.** Manipulación de conteos o de la deduplicación; cambios sin traza.

**Controles.**

1. **Auditoría automática append-only** de toda escritura:
```php
// app/Services/AuditoriaService.php
public function registrar(?int $userId, string $entidad, int|string $idReg, string $accion, ?array $antes, ?array $despues): void
{
    model('AuditoriaModel')->insert([
        'id_usuario' => $userId, 'entidad' => $entidad, 'id_registro' => (string) $idReg,
        'accion' => $accion,
        'valor_antes'   => $antes   ? json_encode($antes,   JSON_UNESCAPED_UNICODE) : null,
        'valor_despues' => $despues ? json_encode($despues, JSON_UNESCAPED_UNICODE) : null,
    ]);
}
```
2. **Deduplicación y validación solo del lado servidor**; el cliente nunca asigna `id_persona` ni `control_registro`.
3. **`$allowedFields` estrictos** (anti mass-assignment): nunca `id`, `id_persona`, `id_datosbeneficiario`, `control_registro`, `decision_coordinacion`. *Vive en:* Model, Service, Events.

> Se cubren los 7 riesgos más aplicables. A06 (Vulnerable Components) se gestiona con `composer audit`/`npm audit` en CI; A09 (Logging Failures) con la auditoría + logs de Nginx/PHP; A10 (SSRF) no aplica de forma significativa porque la plataforma no descarga URLs de evidencia (solo guarda el enlace, ADR-005).

---

## 3. Seguridad específica por capa

### 3.1 Filtros / Middlewares

| Filtro | Propósito | Cuándo aplica |
|---|---|---|
| `cors` | Restringe orígenes a la SPA | Todas las rutas `/api` |
| `auth` (Shield `tokens`/`chain`) | Verifica el access token | Todas salvo `login`, `health` |
| `rbac:<grupo>` | Exige el grupo de Shield requerido | Rutas de coordinación/admin |
| `scope-institucion` | Carga el ámbito del usuario al request | Todas las rutas de datos |
| `throttle` | Rate limit por IP/usuario | Global + login reforzado |
| `secureheaders` | Cabeceras de seguridad | Global (after) |

### 3.2 Autenticación y sesión

Flujo en [Arquitectura §4.4](../02-arquitectura/02_arquitectura_sistema.md). Puntos de validación: credenciales (bcrypt), emisión de access token, verificación de token en cada request, expiración/revocación, regeneración de sesión. La verificación de email de Shield no equivale a verificación de identidad de beneficiario (que no se hace).

### 3.3 Autorización RBAC + ámbito

| Recurso / Acción | Capturista | Coordinación | Dirección | Admin |
|---|:---:|:---:|:---:|:---:|
| Cadena MEL (crear/editar) | ✅ (su ámbito) | ✅ (global) | ❌ | ❌ |
| Productos (tipo E) | ✅ (su ámbito) | ✅ | ❌ | ❌ |
| Editar `personas` / resolver cola dedup | ❌ | ✅ | ❌ | ❌ |
| Reclasificar P/E/R | ❌ | ✅ | ❌ | ❌ |
| Catálogos / metas (POA) | ❌ | ✅ | ❌ | (catálogos) ✅ |
| Resolver solicitudes | ❌ | ✅ | ❌ | ❌ |
| Ver auditoría | ❌ | ✅ | (lectura) | ✅ |
| Dashboards ejecutivos | (según) | ✅ | ✅ | ✅ |
| Gestionar usuarios | ❌ | (según política) | ❌ | ✅ |

El **ámbito** (institución/territorio) se aplica además del rol: el capturista siempre acotado; coordinación/dirección/admin con `data.viewAll`.

### 3.4 Protección de datos (tránsito y reposo)

- **Tránsito:** TLS forzado (HTTPS), HSTS.
- **Reposo:** PII en MySQL con acceso restringido por usuario de app de permisos mínimos; respaldos cifrados. Cifrado app-side disponible para campos que lo requieran (servicio de cifrado de CI4, clave en `.env`).
- **Evidencias:** el contenido vive en Drive (no en la plataforma); CPJ garantiza permisos restringidos en esas carpetas (ADR-005).

### 3.5 Seguridad del cliente (SPA)

- Access token **en memoria**, nunca en `localStorage`/`sessionStorage` (mitiga robo por XSS).
- React auto-escapa; `dangerouslySetInnerHTML` prohibido salvo DOMPurify.
- CSP `default-src 'self'`; sin scripts inline.
- La SPA nunca decide permisos; solo refleja lo que el backend autoriza.

### 3.6 Seguridad de servicios externos

- **Google Drive (evidencias):** la plataforma solo guarda y valida el enlace; no descarga ni renderiza el contenido del lado servidor (evita SSRF). El control de acceso al archivo es responsabilidad del Drive institucional.

---

## 4. Procedimientos operativos

### 4.1 Gestión de secretos
Viven solo en `.env` (BD, Redis, `encryption.key`, secretos de Shield); nunca versionados. Rotación: ante sospecha de filtración, rotar `encryption.key` y forzar reemisión de tokens. El `.env` está fuera del document root.

### 4.2 Checklist de hardening de servidor
Ver [Arquitectura §6.3](../02-arquitectura/02_arquitectura_sistema.md). Resumen: `production`, HTTPS+HSTS, toolbar off, `$autoRoute=false`, filtros globales activos, usuario MySQL de permisos mínimos, `writable/` y `.env` fuera del web root, respaldo diario + restauración probada.

### 4.3 Plan de respuesta a incidentes
1. Contener: revocar tokens/sesiones afectadas; aislar la cuenta comprometida.
2. Evaluar alcance con `auditoria` (qué registros se tocaron, por quién).
3. Notificar a coordinación/dirección y, si hay brecha de PII, evaluar obligaciones LFPDPPP.
4. Remediar (parche, rotación de secretos) y restaurar desde respaldo si hubo manipulación.
5. Post-mortem documentado y registrado como solicitud "ajuste" criticidad ALTA.

### 4.4 Privacidad y cumplimiento legal (LFPDPPP)
- **Qué es PII:** nombre, teléfono, año de nacimiento, colonia, correo de beneficiarios.
- **Manejo:** acceso por rol e institución (mínimo necesario); en reposo en infraestructura de CPJ; en tránsito cifrado.
- **Derechos del titular (ARCO):** la plataforma debe permitir localizar (por `id_persona`), rectificar (coordinación) y, ante solicitud válida, eliminar/anonimizar los datos de una persona, dejando traza en `auditoria`. El aviso de privacidad y la base legal del tratamiento los define CPJ con asesor legal.
- **Minimización:** no se capturan datos no necesarios; `anio_nacimiento` (año, no fecha completa) ya es una medida de minimización heredada del diseño original.

---
