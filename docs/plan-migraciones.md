# Plan: reescribir las migraciones (lo haces tú a mano)

## Contexto

Las migraciones actuales son las de Laravel por defecto (ids enteros, tabla `sessions`, `morphs` normales). El diseño real está en `docs/db/schema.sql` y los ajustes para Laravel/Sanctum en `backend/CLAUDE.md` (sección "Base de datos"). El objetivo es que las migraciones reproduzcan ese esquema. **Tú escribes el código; yo solo reviso cuando me lo pidas.** Este plan indica qué cambiar, en qué orden y qué concepto repasas en cada paso.

Decisión: se **editan** las migraciones existentes (no hay datos reales) y se usa `migrate:fresh`. Esto borra los datos de la BD de desarrollo.

Archivos en `backend/database/migrations/`.

## Pasos

### 1. Preparar la base de datos de test (antes de nada)
Archivo: `backend/phpunit.xml` (hoy fuerza SQLite en memoria; con `citext`, arrays y `CHECK` no funcionará).
- Crear la BD `planificador_test` en el contenedor de Postgres.
- Cambiar `DB_CONNECTION`/`DB_DATABASE` en `phpunit.xml` (líneas `<env>` y `<server>`) a `pgsql` / `planificador_test`, **manteniendo `force="true"`**: ese `force` es lo que protege tu BD de desarrollo (commit "protect DB from tests").
- Concepto: por qué los tests deben usar el mismo motor que producción.

### 2. `0001_01_01_000000_create_users_table.php`
- `id()` → `uuid('id')->primary()`. Concepto: UUID vs autoincrement; el modelo usará `HasUuids`.
- Antes de crear tablas, activar la extensión `citext` (`DB::statement('CREATE EXTENSION IF NOT EXISTS citext')`). Opcional `pgcrypto` si quieres `DEFAULT gen_random_uuid()`.
- `email`: cambiarlo a `citext` (el Schema Builder no lo soporta; usa `DB::statement('ALTER TABLE users ALTER COLUMN email TYPE citext')` tras crear la tabla, o `$table->addColumn('citext', 'email')`). Mantener `unique`.
- Mantener `password` (no `password_hash`), `remember_token` solo si lo quieres (con tokens de API no se usa; puedes quitarlo).
- Añadir: `timezone` (string, default `Europe/Madrid`), `week_starts_on` (smallint, default 1), `default_view` (string, default `week`).
- Añadir `CHECK` con `DB::statement`: `week_starts_on BETWEEN 0 AND 6`, `default_view IN ('week','month','year')`, `length(trim(name)) > 0`.
- Timestamps: `timestampsTz()` en lugar de `timestamps()` (el esquema usa `timestamptz`).
- **Quitar** el bloque `Schema::create('sessions', ...)` y su `dropIfExists('sessions')` del `down()`.
- Conservar `password_reset_tokens` tal cual (email PK, token, created_at).
- `email_verified_at`: decide si lo conservas (no está en el esquema ni en el mockup).
- Conceptos: `up()`/`down()`, `DB::statement`, tipos de Postgres frente a los de Laravel.

### 3. `0001_01_01_000001_create_cache_table.php` y `..._000002_create_jobs_table.php`
- **No se tocan.** Se usan para caché en BD y para la cola (`queue:work` ya corre en Docker).
- Antes de dar por buena la decisión, comprobar `CACHE_STORE` y `QUEUE_CONNECTION` en `backend/.env`.

### 4. `2026_10_16_081914_create_personal_access_tokens_table.php`
- Cambiar `$table->morphs('tokenable')` → `$table->uuidMorphs('tokenable')`.
- Concepto: relación polimórfica (`tokenable_type` + `tokenable_id`) y por qué el tipo del id debe coincidir con `users.id`.

### 5. Nueva migración: `create_categories_table`
Crear con `php artisan make:migration create_categories_table` (dentro del contenedor: `make shell`).
- `uuid id` PK; `user_id` uuid con `foreignUuid('user_id')->constrained()->cascadeOnDelete()`.
- `name` (string), `color` (`char(7)`), `position` (smallint, default 0), `timestampsTz()`.
- `unique(['user_id','name'])` y `unique(['user_id','id'])` (este segundo permite la FK compuesta desde `tasks`).
- `CHECK`: `color ~ '^#[0-9A-Fa-f]{6}$'` y `length(trim(name)) > 0`.
- Concepto: FK, `ON DELETE CASCADE`, índices únicos compuestos.

### 6. Nueva migración: `create_tasks_table`
- Columnas: `uuid id`, `user_id` (FK a users, cascade), `category_id` uuid nullable (**sin** FK simple), `title`, `notes` (text nullable), `task_date` (date), `start_time`/`end_time` (time nullable), `is_completed` (bool, default false), `completed_at` (timestampTz nullable), `recurrence` (string, default `none`), `recurrence_days` (`smallint[]`, vía `DB::statement('ALTER TABLE tasks ADD COLUMN recurrence_days smallint[]')`), `recurrence_until` (date nullable), `timestampsTz()`.
- FK compuesta con `DB::statement`: `FOREIGN KEY (user_id, category_id) REFERENCES categories (user_id, id) ON DELETE SET NULL (category_id)` (la lista de columnas en `SET NULL` requiere PostgreSQL 15+; comprueba la versión de tu imagen).
- `CHECK`s: título no vacío; `recurrence IN (...)`; `end_time IS NULL OR (start_time IS NOT NULL AND end_time > start_time)`; `recurrence_until IS NULL OR recurrence_until >= task_date`; `recurrence_days <@ ARRAY[1,...,7]::smallint[]`.
- Índices: `(user_id, task_date, start_time)`; índice parcial `ON tasks (user_id) WHERE recurrence <> 'none'` (con `DB::statement`); `category_id`.
- Concepto: por qué la FK compuesta impide que una tarea use la categoría de otro usuario (aislamiento a nivel de BD).

### 7. Nueva migración: `create_task_exceptions_table`
- `uuid id`, `user_id` (FK cascade), `task_id` (FK a tasks, cascade), `occurrence_date` (date), `is_skipped`, `is_completed` (bool, default false), `created_at` (timestampTz).
- `unique(['task_id','occurrence_date'])` e índice `(user_id, occurrence_date)`.
- Concepto: tabla de excepciones; una fila por ocurrencia modificada.

### 8. Fuera de alcance por ahora
- **RLS** (políticas, rol `planificador_app`): migración aparte más adelante; el CLAUDE.md permite dejarla desactivada en local.
- **Trigger `create_default_categories`**: no se crea; las categorías por defecto se insertan en código al registrar (Fijo `#C9CDF2`, Urgente `#EB775A`, Importante `#8DC63F`, Nueva `#F4B942`).
- **Trigger `set_updated_at`**: innecesario, Eloquent mantiene `updated_at`.

## Orden de nombres de archivo
Los nuevos deben ir **después** de `users` por fecha en el nombre: `categories` → `tasks` → `task_exceptions` (las FK exigen que la tabla destino exista). En `down()`, borrar en orden inverso.

## Verificación
1. `docker compose exec php php artisan migrate:fresh` → sin errores (borra datos de desarrollo).
2. Revisar en pgAdmin (http://localhost:5050) o con `\d tasks` en `psql`: tipos `uuid`, `citext`, `smallint[]`, `CHECK`, FK compuesta e índices.
3. Probar a mano que los `CHECK` rechazan datos inválidos (color `rojo`, `end_time` anterior a `start_time`, `recurrence_days = {8}`) y que una tarea no puede apuntar a una categoría de otro usuario.
4. `php artisan migrate:rollback` seguido de `migrate` → comprueba que los `down()` funcionan.
5. `make test` con la BD `planificador_test` → los tests de ejemplo pasan.
6. Cuando termines, pásame las migraciones y las reviso.
