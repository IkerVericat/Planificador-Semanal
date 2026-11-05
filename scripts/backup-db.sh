#!/usr/bin/env bash
# Copia de seguridad de PostgreSQL (pg_dump comprimido) y borrado de las antiguas.
# Uso (desde la raíz del repositorio, en el servidor):  ./scripts/backup-db.sh
# Cron diario a las 3:30:  30 3 * * * cd /home/deploy/planificador && ./scripts/backup-db.sh >> backups/backup.log 2>&1
# Restaurar:  gunzip -c backups/planificador-AAAAMMDD-HHMM.sql.gz | docker compose exec -T db sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB"'
set -euo pipefail

cd "$(dirname "$0")/.."

BACKUP_DIR="${BACKUP_DIR:-backups}"
KEEP_DAYS="${KEEP_DAYS:-14}"
FILE="$BACKUP_DIR/planificador-$(date +%Y%m%d-%H%M).sql.gz"

mkdir -p "$BACKUP_DIR"
trap 'rm -f "$FILE.tmp"' EXIT

# --clean --if-exists: el volcado borra y recrea las tablas al restaurarlo
docker compose exec -T db sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --clean --if-exists --no-owner' \
    | gzip > "$FILE.tmp"
mv "$FILE.tmp" "$FILE"

find "$BACKUP_DIR" -name 'planificador-*.sql.gz' -mtime +"$KEEP_DAYS" -delete

echo "$(date '+%F %T') Copia creada: $FILE ($(du -h "$FILE" | cut -f1))"
