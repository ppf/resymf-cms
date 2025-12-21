# Docker commands
.PHONY: up down logs clean-docker build-prod test-prod

up:
	docker compose up -d

down:
	docker compose down

logs:
	docker compose logs -f

clean-docker:
	docker compose down -v
	@echo "All volumes removed. Run 'make up' for fresh start."

build-prod:
	docker compose -f docker-compose.yml -f docker-compose.prod.yml build

test-prod:
	docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
