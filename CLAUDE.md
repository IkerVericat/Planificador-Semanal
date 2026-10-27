# Planificador Semanal

App web para planificar tareas por semana, mes y año. Multitenant: cada usuario solo ve sus datos.
Idioma de la interfaz, del código de dominio visible al usuario y de los mensajes de commit: **español**.
Nombres de clases, variables, tablas y rutas de la API: **inglés** (`tasks`, `categories`, `TaskController`).

## Stack

| Capa | Tecnología |
|---|---|
| Backend | Laravel 11+ (PHP 8.3), API REST JSON bajo `/api` |
| Autenticación | Laravel Sanctum con **tokens de API** (Bearer), no cookies de SPA |
| Base de datos | PostgreSQL 15+, una sola base de datos, `user_id` en cada tabla de datos |
| Frontend | Vue 3 (Composition API + `<script setup>`), TypeScript, TailwindCSS, Vite |
| Tipo de app | PWA **mobile first** (`vite-plugin-pwa`) |
| Despliegue | Backend en VPS de DigitalOcean · Frontend en Vercel o Cloudflare Pages (por decidir) |

## Estructura del repositorio (monorepo)

```
/
├── CLAUDE.md            ← este archivo (contexto general)
├── backend/             ← proyecto Laravel  (ver backend/CLAUDE.md)
├── frontend/            ← proyecto Vue      (ver frontend/CLAUDE.md)
├── docs/
│   ├── mockup.png       ← diseño de referencia de todas las pantallas
│   └── db/schema.sql    ← esquema de referencia de PostgreSQL
└── .claude/settings.json
```

Antes de construir una pantalla, **abre y mira `docs/mockup.png`**. Antes de tocar la base de datos, lee `docs/db/schema.sql` **y** la sección "Base de datos" de `backend/CLAUDE.md`: las migraciones de Laravel se apartan del esquema en algunos puntos para encajar con Sanctum (columna `password`, sin tabla `sessions` propia, `personal_access_tokens` con UUID).

## Funcionalidad (según el mockup)

1. **Iniciar sesión**: "Bienvenido de nuevo", email + contraseña, enlace "¿Olvidaste tu contraseña?", enlace a "Regístrate".
2. **Crear cuenta**: nombre, email, contraseña (mínimo 8 caracteres), confirmar contraseña.
3. **Recuperar contraseña**: email + botón "Continuar" → correo con enlace de restablecimiento.
4. **Mi planificador**, con selector segmentado Semana / Mes / Año y botón de perfil arriba a la derecha:
   - **Semana**: navegación `‹ 12 – 18 de octubre ›`, barra de categorías (FIJO, URGENTE, IMPORTANTE, NUEVA), una tarjeta por día (Lunes 12 oct…) con filas `hora | TÍTULO` coloreadas por categoría.
   - **Mes**: navegación `‹ Octubre 2026 ›`, cuadrícula L M X J V S D (la semana empieza en lunes), puntos de color por categoría bajo cada día, día seleccionado resaltado, y debajo la lista del día ("Jueves 15 de octubre · 4 tareas").
   - **Año**: navegación `‹ 2026 ›`, 12 minicalendarios en cuadrícula de 3 columnas, días coloreados según la cantidad de tareas (mapa de calor con leyenda "Menos … Más tareas"), mes actual con borde.
5. **Botón flotante "+"** (abajo a la derecha) para crear una tarea en todas las vistas.
6. Tareas: título, notas, fecha, hora de inicio/fin opcionales (sin hora = todo el día), categoría, completada, y recurrencia (`none|daily|weekly|monthly|yearly`) con excepciones por fecha.

## Reglas que no se negocian

- **Aislamiento entre usuarios**: toda consulta de datos se filtra por el usuario autenticado. Nunca aceptes `user_id` desde el cliente; se toma siempre de `auth()->user()`. Cada endpoint nuevo lleva un test que comprueba que un usuario **no** puede leer ni modificar datos de otro (debe recibir 404).
- **IDs UUID** en todas las tablas (no enteros autoincrementales).
- **Fechas**: `task_date` es una fecha local del usuario (sin zona horaria); las horas son `time`. La zona del usuario está en `users.timezone` (por defecto `Europe/Madrid`). Los `timestamptz` se devuelven en ISO 8601.
- **Formato visible**: horas en formato 12 h como en el mockup (`9 AM`, `7:30 PM`); fechas en español (`12 – 18 de octubre`, `Jueves 15 de octubre`).
- No añadas dependencias nuevas sin explicar por qué y sin preguntar.
- No hagas commits ni push salvo que te lo pida.

## Contrato de la API (borrador, mantenlo actualizado aquí)

Todas las rutas devuelven JSON; las protegidas requieren `Authorization: Bearer <token>`.
Los recursos se devuelven **sin envolver** en `data` (`GET /api/categories` → `[ … ]`). Fechas `YYYY-MM-DD`, horas `HH:mm`.

| Método y ruta | Descripción |
|---|---|
| `POST /api/auth/register` | `{ name, email, password, password_confirmation, timezone?, device_name? }` → **201** `{ user, token }`. Crea las 4 categorías por defecto. Límite 10/min |
| `POST /api/auth/login` | `{ email, password, device_name? }` → `{ user, token }`. Límite 5/min por email + IP |
| `POST /api/auth/logout` | Revoca el token actual → 204 |
| `POST /api/auth/forgot-password` | `{ email }` → 200 `{ message }` aunque el email no exista. El correo enlaza a `FRONTEND_URL/reset-password?token=…&email=…`. Límite 5/min |
| `POST /api/auth/reset-password` | `{ email, token, password, password_confirmation }` → 200 `{ message }`; revoca todos los tokens del usuario |
| `GET /api/me` · `PATCH /api/me` | Perfil `{ id, name, email, timezone, week_starts_on, default_view, created_at }`; PATCH acepta `name`, `timezone`, `week_starts_on`, `default_view` |
| `GET/POST /api/categories` · `PATCH/DELETE /api/categories/{id}` | `{ id, name, color, position }`, ordenadas por `position`. Al borrar, sus tareas quedan sin categoría |
| `GET /api/occurrences?from=YYYY-MM-DD&to=YYYY-MM-DD` | Tareas **ya expandidas** (recurrencias + excepciones) en el rango, máx. 400 días: `[{ task_id, date, original_date, start_time, end_time, title, notes, category_id, is_completed, is_recurring }]`. `date` es el día en que se muestra y `original_date` el que le toca por la repetición (distintos si se ha movido); lo usan Semana y Mes |
| `GET /api/stats/days?year=2026` | `{ "2026-10-15": 4, … }` número de tareas por día (solo días con tareas; `{}` si no hay), para el mapa de calor de Año |
| `POST /api/tasks` · `GET/PATCH/DELETE /api/tasks/{id}` | CRUD de la tarea "plantilla" `{ id, category_id, title, notes, task_date, start_time, end_time, is_completed, completed_at, recurrence, recurrence_days, recurrence_until, created_at, updated_at }`. PATCH valida contra los valores actuales |
| `PUT /api/tasks/{id}/occurrences/{date}` | `{ is_completed?, is_skipped?, moved_to_date? }` sobre una ocurrencia de una tarea recurrente; `{date}` es su `original_date`. `moved_to_date` la mueve solo a ella a otro día (`null` o la fecha original la devuelven) → `{ task_id, date, is_completed, is_skipped, moved_to_date }`. 404 si la tarea no cae ese día, 422 si no es recurrente |

Errores de validación: formato estándar de Laravel (422 con `message` y `errors`), mensajes en español (`backend/lang/es`).
Recurso inexistente o de otro usuario: 404 `{ "message": "No encontrado." }`. Sin token: 401.

## Comandos habituales

```bash
# Backend
cd backend && php artisan serve          # API en http://localhost:8000
cd backend && php artisan test           # tests (PHPUnit, contra la BD planificador_test)
cd backend && php artisan migrate:fresh --seed
cd backend && ./vendor/bin/pint          # formateo

# Frontend
cd frontend && npm run dev               # http://localhost:5173
cd frontend && npm run build
cd frontend && npm run type-check
cd frontend && npm run test              # Vitest
```

## Forma de trabajar

- Trabaja en pasos pequeños y verificables: una funcionalidad por vez, con sus tests, y ejecútalos antes de decir que está hecho.
- Si algo del mockup o de este archivo es ambiguo, pregunta antes de inventar.
- Cuando cambies el contrato de la API, actualiza la tabla de arriba y los tipos de `frontend/src/types/api.ts` en el mismo cambio.
- Commits en español, en imperativo y cortos: `Añade vista mensual`, `Corrige filtro por usuario en tareas`.
