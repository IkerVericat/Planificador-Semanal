#!/bin/sh
# Solo se ejecuta al inicializar un volumen vacío de PostgreSQL (docker-entrypoint-initdb.d)
set -e
psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -d "$POSTGRES_DB" -c "CREATE DATABASE planificador_test"
