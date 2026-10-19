# Documentación planificador semanal

## Encendido de la app
1. sed -i '' "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" .env
2. docker compose up -d --build
3. docker compose exec php php artisan migrate --force

- URL web producción: http://localhost:8080
- URL pgAdmin (Base de datos): http://localhost:5050

## Apagado
1. Docker compose down

## Borrado
docker compose down -v


## STACK TECH
Backend → Laravel
Frontend → Vue 3 + TailwindCSS + Typescript

### DATABASE
PostgreSQL ( Una sola base de datos con tablas identificadas por user_id).
Pgadmin para la gestión de la base de datos de manera visual.

### WEB
Nginx

### FRONTEND
Enfocarlo como un SPA orientada a Mobile First.

### BACKEND I AUTENTICACIÓ
Laravel Sanctum para los tokens API.

### DESPLEGAMENT
App Dockerizada con todos los servicios y dependencias.
Backend → Crear VPS a DigitalOcean
Frontend → Vercel / Cloudflare pages (decidirlo posteriormente)