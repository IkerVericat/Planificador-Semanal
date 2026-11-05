#!/usr/bin/env bash
# Despliega la última versión de la rama actual en el servidor.
# Uso (desde la raíz del repositorio, en el servidor):  ./scripts/deploy.sh
# Las migraciones se aplican solas al arrancar el contenedor php (RUN_MIGRATIONS=true).
set -euo pipefail

cd "$(dirname "$0")/.."

if grep -qE '^COMPOSE_FILE=.*docker-compose\.dev\.yml' .env 2>/dev/null; then
    echo "El .env activa el modo desarrollo (COMPOSE_FILE con docker-compose.dev.yml). Quítalo en el servidor." >&2
    exit 1
fi

echo "→ Copia de seguridad previa"
./scripts/backup-db.sh || echo "  (sin copia: la base de datos aún no está en marcha)"

echo "→ Descargando cambios"
git pull --ff-only

echo "→ Construyendo y reiniciando contenedores"
docker compose up -d --build --remove-orphans

echo "→ Limpiando imágenes antiguas"
docker image prune -f > /dev/null

docker compose ps
echo "Despliegue terminado."
