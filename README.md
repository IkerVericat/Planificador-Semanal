# Documentación planificador semanal

## Encendido de la app (local)
1. `cp .env.example .env` y activa la sección "Desarrollo" del final (comenta `COMPOSE_PROFILES=production`)
2. sed -i '' "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" .env
3. `make up` (las migraciones se aplican solas al arrancar el contenedor `php`)

- URL web (Vite, desarrollo): http://localhost:5173
- URL web compilada + API (nginx): http://localhost:8080
- URL pgAdmin (Base de datos, solo desarrollo): http://localhost:5050

### Apagado
1. Docker compose down

### Borrado
docker compose down -v

### Makefile
He creado un archivo makefile que me facilita la ejecución de los comandos:

| Comando | Qué hace |
|---|---|
| `make` / `make help` | Lista los atajos disponibles |
| `make up` | Construye y levanta todos los contenedores (`up -d --build`) |
| `make down` | Para y elimina los contenedores (los volúmenes se conservan) |
| `make logs` | Logs en vivo. Para un solo servicio: `make logs SERVICE=php` |
| `make shell` | Abre una terminal `sh` dentro del contenedor `php` |
| `make migrate` | Ejecuta las migraciones (`php artisan migrate`) |
| `make seed` | Borra la BD de desarrollo y la recrea con datos de prueba (`demo@example.com` / `password`) |
| `make test` | Tests del backend (`composer test`) contra la BD `planificador_test` de PostgreSQL; no tocan la de desarrollo |
| `make test-db` | Crea la BD `planificador_test` si tu volumen de Postgres es anterior a `docker/db/init` |
| `make lint` | Comprueba el estilo del backend con Pint, sin modificar archivos |
| `make lint-fix` | Corrige el estilo del backend con Pint |
| `make composer ARGS="..."` | Ejecuta Composer en el contenedor `php`. Ej: `make composer ARGS="require vendor/paquete"` |
| `make npm ARGS="..."` | Ejecuta npm en el contenedor `vite` (frontend). Ej: `make npm ARGS="install axios"` |

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
App Dockerizada con todos los servicios y dependencias, en un solo droplet de DigitalOcean.
La web y la API comparten dominio (`https://dominio` y `https://dominio/api`); Caddy pone el HTTPS.

## Despliegue en DigitalOcean

```
Internet ──443──▶ caddy (HTTPS, Let's Encrypt) ──▶ nginx (SPA + /api) ──▶ php-fpm (Laravel) ──▶ db (PostgreSQL)
                                                                         queue (cola de Laravel)
```

### 1. Preparar (una sola vez)
1. **Droplet**: Ubuntu 24.04 LTS, Basic de 2 GB de RAM (con 1 GB la compilación de las imágenes se queda sin memoria), región Frankfurt o Amsterdam, acceso con clave SSH. Opcional: activar *Backups*.
2. **Cortafuegos** (*Networking → Firewalls*, aplicado al droplet): entrada solo TCP 22, 80, 443 y UDP 443. Hazlo en el panel y no con `ufw`: Docker se salta `ufw`.
3. **DNS**: registro `A` del dominio (o subdominio) → IP del droplet. Si el DNS está en Cloudflare, déjalo en "DNS only" (nube gris).
   Opcional `www`: registro `A` para `www` y, en `caddy/Caddyfile`, un bloque `www.{$DOMAIN} { redir https://{$DOMAIN}{uri} permanent }`.
4. **Correo**: cuenta en un proveedor SMTP (Brevo, Resend…), verifica el dominio y añade al DNS los registros **SPF** y **DKIM** que te dé. Sin ellos los correos acaban en spam.

### 2. Primera instalación en el servidor
```bash
ssh root@IP_DEL_DROPLET
curl -fsSL https://get.docker.com | sh                  # Docker + Docker Compose
adduser deploy && usermod -aG docker deploy
su - deploy                                             # a partir de aquí, como el usuario deploy
git clone https://github.com/IkerVericat/Planificador-Semanal.git planificador && cd planificador
cp .env.example .env && nano .env                      # rellena DOMAIN, APP_URL, FRONTEND_URL, APP_KEY, DB_PASSWORD, MAIL_*
docker compose up -d --build
```
- `APP_KEY`: `echo "base64:$(openssl rand -base64 32)"` · `DB_PASSWORD`: `openssl rand -base64 24`
- En el `.env` del servidor **no** pongas `COMPOSE_FILE` (es el modo desarrollo) y deja `COMPOSE_PROFILES=production` (arranca Caddy).
- **No cambies `DB_PASSWORD` después del primer arranque**: PostgreSQL la guarda al crear el volumen.

Comprueba: `https://tu-dominio/up` responde, puedes crear una cuenta y llega el correo de "¿Olvidaste tu contraseña?".

### 3. Copias de seguridad
```bash
./scripts/backup-db.sh                                  # crea backups/planificador-AAAAMMDD-HHMM.sql.gz (guarda 14 días)
crontab -e                                              # y añade la copia diaria a las 3:30:
30 3 * * * cd /home/deploy/planificador && ./scripts/backup-db.sh >> backups/backup.log 2>&1
```
Las copias quedan en el mismo servidor: descárgalas de vez en cuando (`scp`) o activa los *Backups* del droplet.
Restaurar: `gunzip -c backups/FICHERO.sql.gz | docker compose exec -T db sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB"'`

### 4. Actualizar a una versión nueva
```bash
cd ~/planificador && ./scripts/deploy.sh               # copia de seguridad + git pull + rebuild (las migraciones son automáticas)
```

### Útil en el servidor
| Comando | Qué hace |
|---|---|
| `docker compose ps` | Estado de los contenedores |
| `docker compose logs -f php` | Logs de Laravel (también `caddy`, `nginx`, `queue`) |
| `docker compose exec php php artisan tinker` | Consola de Laravel |
| `ssh -L 5433:127.0.0.1:5432 deploy@IP` | Túnel: la BD de producción queda en `localhost:5433` de tu equipo (pgAdmin, TablePlus…) |
