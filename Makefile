# Atajos para el entorno de desarrollo (docker-compose.yml + docker-compose.dev.yml).
# Uso: make <objetivo>. Con argumentos: make composer ARGS="require vendor/paquete"

COMPOSE := docker compose -f docker-compose.yml -f docker-compose.dev.yml
PHP     := $(COMPOSE) exec php
ARGS    ?=

.DEFAULT_GOAL := help
.PHONY: help up down logs shell migrate seed test test-db lint lint-fix composer npm

help: ## Muestra esta ayuda
	@grep -E '^[a-z-]+:.*## ' $(MAKEFILE_LIST) | awk -F':.*## ' '{printf "  make %-10s %s\n", $$1, $$2}'

up: ## Construye y levanta todos los contenedores
	$(COMPOSE) up -d --build

down: ## Para y elimina los contenedores (los volúmenes se conservan)
	$(COMPOSE) down

logs: ## Logs en vivo (make logs SERVICE=php para uno solo)
	$(COMPOSE) logs -f --tail=100 $(SERVICE)

shell: ## Terminal dentro del contenedor PHP
	$(PHP) sh

migrate: ## Ejecuta las migraciones de Laravel
	$(PHP) php artisan migrate

seed: ## Borra la BD de desarrollo y la recrea con el usuario demo@example.com / password
	$(PHP) php artisan migrate:fresh --seed

test: ## Tests del backend (BD planificador_test en PostgreSQL; no tocan la de desarrollo)
	$(PHP) composer test

test-db: ## Crea la BD planificador_test si no existe (volúmenes creados antes de docker/db/init)
	$(COMPOSE) exec db sh -c 'psql -U "$$POSTGRES_USER" -d "$$POSTGRES_DB" -tc "SELECT 1 FROM pg_database WHERE datname = '"'"'planificador_test'"'"'" | grep -q 1 || psql -U "$$POSTGRES_USER" -d "$$POSTGRES_DB" -c "CREATE DATABASE planificador_test"'

lint: ## Comprueba el estilo del backend con Pint (no modifica archivos)
	$(PHP) vendor/bin/pint --test

lint-fix: ## Corrige el estilo del backend con Pint
	$(PHP) vendor/bin/pint

composer: ## Composer en el backend: make composer ARGS="require vendor/paquete"
	$(PHP) composer $(ARGS)

npm: ## npm en el frontend: make npm ARGS="install axios"
	$(COMPOSE) exec vite npm $(ARGS)
