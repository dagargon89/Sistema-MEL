# ADR-002 — Autenticación con CodeIgniter Shield

| | |
|---|---|
| **Estado** | Aceptado |
| **Fecha** | 22 de junio de 2026 |
| **Depende de** | [ADR-001](ADR-001_stack-ci4-react-mysql.md) |
| **Reemplaza** | Supabase Auth (estudio de viabilidad) y la "protección por contraseña compartida" del Excel |

## 1. Contexto

El sistema actual "protege" la hoja `personas` con una contraseña compartida (`CPJ_MEL_2026`) impresa en un manual de distribución amplia y, en el archivo real, **desactivada**. Esto no es autenticación ni control de acceso. La plataforma necesita cuentas individuales, roles reales y trazabilidad de cada acción, todo **autoalojado** (decisión de ADR-001, que descarta Supabase Auth).

CodeIgniter ofrece **Shield**, su biblioteca oficial de autenticación y autorización, que se ejecuta dentro de la misma app CI4 y persiste en MySQL. Cubre: registro/login, hashing de contraseñas (bcrypt/argon2), sesión web, **access tokens** (HMAC) para clientes API, grupos y permisos, y verificación de email opcional.

## 2. Decisión

Usar **CodeIgniter Shield** como único proveedor de identidad y autorización base.

| Aspecto | Decisión |
|---|---|
| Mecanismo para la SPA | **Access tokens** de Shield enviados como `Authorization: Bearer <token>`, verificados por el filtro `tokens` (o `chain`) en cada petición a `/api` |
| Almacenamiento de contraseñas | Hash con `password_hash` (bcrypt por defecto) gestionado por Shield; nunca en texto plano |
| Sesión | Sesión de servidor con regeneración de ID al login; cookies `HttpOnly`, `Secure`, `SameSite` |
| Roles | Grupos de Shield: `capturista`, `coordinacion`, `direccion`, `administrador` (uno por usuario, ver doc 06) |
| Permisos finos | Combinación de grupos de Shield + `PolicyServices` propios para autorización a nivel de objeto y de fila |
| Revocación | Tokens revocables/expirables; logout destruye sesión y permite revocar el token activo |

## 3. Mapeo de conceptos (Supabase Auth → Shield)

| Supabase Auth | CodeIgniter Shield |
|---|---|
| JWT firmado por el proveedor | Access token HMAC almacenado y verificado por Shield contra MySQL |
| `auth.users` | Tablas `users` / `auth_identities` de Shield |
| Políticas que leen `auth.uid()` | `auth()->id()` disponible en el request; usado por `PolicyServices` |
| Roles en el JWT | Grupos de Shield (`auth()->user()->inGroup('coordinacion')`) |
| Refresh tokens | Rotación/expiración de access tokens de Shield |

## 4. Consecuencias

**Positivas**
- Identidad 100% autoalojada; las credenciales y los tokens viven en MySQL, no en un tercero.
- Cuentas individuales auditables: cada escritura se asocia a un `user_id` real (RN de auditoría).
- Menos código propio que un esquema JWT artesanal: Shield ya resuelve hashing, expiración, revocación y rate-limit de login.
- Desaparece la contraseña compartida e impresa.

**Negativas / trade-offs**
- Shield añade sus propias tablas y convenciones; el equipo debe conocerlas.
- Los access tokens deben almacenarse con cuidado en el cliente (en memoria; ver doc 04, seguridad del cliente).

**Neutrales**
- La verificación de email de Shield (`email_verified`) **no** es lo mismo que la verificación de identidad de beneficiario; en este sistema no se verifica identidad de beneficiarios (son sujetos de datos, no usuarios).

## 5. Impacto en documentos

- `CLAUDE.md`: regla no negociable de autenticación (ya reflejado).
- `04_plan_de_seguridad.md`: §3.2 flujo de autenticación con Shield; A07 controles.
- `05_especificacion_api.md`: §1.2 obtención/uso/revocación de access tokens.
- `03_modelo_de_datos.md`: tablas de Shield + `usuarios`/`roles` lógicos y `usuario_institucion`.

## 6. Implicaciones de seguridad

- **Nuevos controles:** hashing fuerte, regeneración de sesión, throttling de login (A07), tokens revocables.
- **Superficie eliminada:** la contraseña compartida y la "protección" inactiva del Excel.
- **Requisito:** el filtro de auth corre **antes** que cualquier controlador de `/api`; ninguna ruta de datos es pública salvo `login` y `health`.

## 7. Plan de migración

1. Instalar Shield (`composer require codeigniter4/shield`; `php spark shield:setup`).
2. Crear los grupos (`capturista`, `coordinacion`, `direccion`, `administrador`) y el usuario administrador inicial vía seeder.
3. Dar de alta a los capturistas/coordinadores reales con cuentas individuales (no migrar la contraseña compartida).
4. Asociar cada usuario a su(s) institución(es)/territorio(s) en `usuario_institucion` (ADR-004).
