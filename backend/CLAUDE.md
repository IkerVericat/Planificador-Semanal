# Backend · Laravel API

Lee primero el `CLAUDE.md` de la raíz (stack, reglas y contrato de la API).

## Convenciones

- Laravel 11+ / PHP 8.3, solo API (sin Blade). Rutas en `routes/api.php`, todas bajo `auth:sanctum` salvo registro, login y recuperación de contraseña.
- Estructura: `app/Http/Controllers/Api/*`, validación en `FormRequest`, respuestas con `JsonResource`, autorización con `Policy` por modelo.
- Lógica que no es CRUD trivial (expandir recurrencias, estadísticas del año) en clases de `app/Services/`, con tests unitarios.
- Modelos con el trait `HasUuids`. Claves primarias `uuid`.
- Tests con Pest (o PHPUnit si ya está instalado) contra **PostgreSQL**, no SQLite: el esquema usa `citext`, arrays y RLS. Base de datos de test separada (`planificador_test`) en `.env.testing`.
- Formatea con `./vendor/bin/pint` antes de terminar.

## Autenticación con Sanctum (tokens de API)

- `php artisan install:api` instala Sanctum y crea la migración `personal_access_tokens`.
- **Cambia `$table->morphs('tokenable')` por `$table->uuidMorphs('tokenable')`** en esa migración: los usuarios tienen id UUID y la versión por defecto usa enteros.
- Login y registro devuelven `$user->createToken($deviceName)->plainTextToken`. Logout: `$request->user()->currentAccessToken()->delete()`.
- El frontend guarda el token y lo envía como `Authorization: Bearer`. No uses el modo SPA de Sanctum (cookies/CSRF), porque el frontend vivirá en otro dominio (Vercel / Cloudflare Pages).
- `SESSION_DRIVER=array` en `.env`: la API no usa sesiones de Laravel.
- CORS (`config/cors.php`): permite solo el origen del frontend (`FRONTEND_URL`), `supports_credentials = false`.
- Limita intentos de login y de recuperación con `throttle` (p. ej. 5 por minuto).

## Base de datos

`docs/db/schema.sql` es el **diseño de referencia**. La fuente de verdad son las **migraciones de Laravel**, que deben reproducirlo con estos ajustes para que encaje con Laravel y Sanctum:

| En `schema.sql` | En las migraciones | Motivo |
|---|---|---|
| `users.password_hash` | `users.password` | Es la columna que usan `Auth`, `Hash` y el cast `hashed` de Laravel. |
| tabla `sessions` propia | **no se crea** | Las sesiones de login son los tokens de `personal_access_tokens` (Sanctum). Borra también la migración `sessions` por defecto de Laravel. |
| tabla `password_reset_tokens` propia (`id`, `user_id`, `token_hash`) | la de Laravel (`email` PK, `token`, `created_at`) | Así funciona el `Password` broker sin código propio (`Password::sendResetLink` / `Password::reset`). El token ya se guarda hasheado. |
| trigger `create_default_categories` | **sin trigger**: se crean en código al registrarse (`CreateDefaultCategories` en el flujo de registro, dentro de la misma transacción) | Con RLS activo el trigger falla porque aún no hay `app.user_id`, y en código se puede testear. |
| `email citext`, `ON DELETE SET NULL (category_id)`, arrays, `CHECK`, políticas RLS | `DB::statement(...)` dentro de la migración | El Schema Builder de Laravel no los soporta. |

Mantén igual el resto: UUID, `citext`, la FK compuesta `(user_id, category_id)` → `categories(user_id, id)`, los `CHECK`, los índices y los colores por defecto:

| Categoría | Color | position |
|---|---|---|
| Fijo | `#C9CDF2` | 1 |
| Urgente | `#EB775A` | 2 |
| Importante | `#8DC63F` | 3 |
| Nueva | `#F4B942` | 4 |

`recurrence_days` es `smallint[]`: crea un cast propio (`App\Casts\PgSmallintArray`) que convierta entre `{1,3,5}` y `[1,3,5]`.

## Aislamiento multitenant (dos capas)

1. **Aplicación (obligatoria)**: consulta siempre a través de la relación del usuario (`$request->user()->tasks()`), nunca `Task::find($id)` suelto. Las `Policy` comprueban `user_id`. Un recurso de otro usuario responde **404**, no 403.
2. **RLS en PostgreSQL (refuerzo)**: las políticas usan `current_setting('app.user_id', true)`. Para que funcionen:
   - La app se conecta con un rol **que no es el propietario de las tablas** (`planificador_app`), o se añade `ALTER TABLE ... FORCE ROW LEVEL SECURITY`. Si no, el propietario se salta RLS y la capa no protege nada.
   - Un middleware `SetTenantContext`, después de `auth:sanctum`, ejecuta `SELECT set_config('app.user_id', ?, false)` con el id del usuario y lo vacía al terminar la petición (`terminate`). No uses conexiones persistentes de PDO.
   - Las migraciones se ejecutan con el rol propietario (`DB_MIGRATION_*` o conexión `pgsql_owner`).
   - Si RLS da problemas al empezar, déjala desactivada en local y actívala antes de desplegar, pero la capa 1 siempre va.

## Recurrencias

- La tabla `tasks` guarda la tarea "plantilla". `GET /api/occurrences?from&to` la expande en el rango: `daily`, `weekly` (con `recurrence_days`, 1 = lunes … 7 = domingo), `monthly` (mismo día del mes; si no existe, se omite), `yearly`, hasta `recurrence_until`.
- Aplica `task_exceptions`: `is_skipped` quita la ocurrencia, `is_completed` la marca hecha solo ese día.
- Respuesta de cada ocurrencia: `{ task_id, date, start_time, end_time, title, notes, category_id, is_completed, is_recurring }`, ordenada por fecha y hora (las de todo el día primero).
- Rango máximo: 400 días (la vista Año usa `/api/stats/days`).

## Despliegue (VPS de DigitalOcean)

Ubuntu LTS + Nginx + PHP-FPM 8.3 + PostgreSQL. `APP_DEBUG=false`, HTTPS con Let's Encrypt, `php artisan config:cache route:cache`, `php artisan migrate --force` en cada despliegue, cola con Supervisor si se usan colas para el correo. No toques configuración de servidor sin pedirlo.
