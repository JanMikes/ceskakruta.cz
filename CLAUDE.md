# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

CeskaKruta.cz is a Symfony 7 e-commerce application for selling turkey meat products. It uses:
- **Backend**: Symfony 7 with Doctrine ORM, MySQL/MariaDB database
- **Frontend**: Symfony UX (Stimulus, Turbo), Bootstrap 5, Webpack Encore
- **Architecture**: Domain-driven design with CQRS pattern (Commands/Queries, Message handlers)

## Development Commands

**All PHP commands must be run inside the Docker container using `docker compose exec web`.**

### PHP/Symfony Commands
```bash
# Install dependencies
docker compose exec web composer install

# Run PHPStan static analysis (max level)
docker compose exec web composer run-script phpstan
# or directly:
docker compose exec web vendor/bin/phpstan --memory-limit=-1 analyse

# Symfony console commands
docker compose exec web bin/console cache:clear
docker compose exec web bin/console cache:warmup --env=dev

# Database operations
docker compose exec web bin/console doctrine:migrations:migrate
docker compose exec web bin/console doctrine:schema:update --force
```

### Frontend Asset Commands
Frontend assets are automatically built by the `js-watch` service in docker-compose.
```bash
# Manual commands (if needed, run inside container)
docker compose exec web npm install
docker compose exec web npm run dev
docker compose exec web npm run build
```

## Architecture Overview

### Directory Structure
- `src/Controller/` - HTTP controllers handling web requests
- `src/Entity/` - Doctrine entities (RecurringOrder, RecurringOrderItem, etc.)
- `src/Message/` - Command and Query objects (CQRS pattern)
- `src/MessageHandler/` - Handlers for commands and queries
- `src/Repository/` - Doctrine repositories for data access
- `src/Services/` - Business logic services (Cart, Security, Twig extensions)
- `src/FormData/` - Form data transfer objects
- `src/FormType/` - Symfony form types
- `src/Value/` - Value objects and DTOs
- `src/Exceptions/` - Custom exception classes
- `src/Components/` - Symfony UX Live Components

### Key Patterns
- **CQRS**: Commands and queries are separated into distinct message classes with dedicated handlers
- **Domain Services**: Business logic is encapsulated in service classes
- **Value Objects**: Complex data structures are represented as immutable value objects
- **Form Handling**: Dedicated FormData objects for form processing

### Configuration
- Standard Symfony configuration in `config/` directory
- Environment variables defined in `.env` files
- PHPStan configuration in `phpstan.neon` with max strictness level
- Webpack Encore configuration in `webpack.config.js`

## Testing and Quality

### Tests
```bash
# PHPUnit; tests/bootstrap.php recreates the `ceskakruta_test` database from tests/database/schema.sql + fixtures.sql
docker compose exec web composer run-script test
```
- `tests/database/schema.sql` is the production schema (DB is shared with the old Nette app — no schema changes)
- Extend `tests/Support/IntegrationTestCase` / `tests/Support/WebTestCase`; each test runs in a rolled-back transaction (DAMA)

### Static Analysis
```bash
# Run PHPStan (required before commits)
docker compose exec web composer run-script phpstan
```

### Environment Setup
- Uses Docker for development (see `docker-compose.yml`)
- Host ports are configurable when defaults are taken by another project:
  `WEB_PORT` (8080), `DB_PORT` (3306), `ADMINER_PORT` (8000), `MAILER_SMTP_PORT` (1025), `MAILER_UI_PORT` (8025),
  e.g. `WEB_PORT=8180 ADMINER_PORT=8100 docker compose up -d`. Containers always talk to each other on the internal ports.
- MariaDB database on port 3306
- Mailer service for development on port 1025

## Important Notes
- PHPStan runs at maximum level with Symfony and Doctrine extensions
- The application uses Symfony Messenger for command/query handling
- Frontend uses Symfony UX components with Bootstrap 5 styling
- Sentry integration available for error tracking
- The domain appears to be focused on turkey meat sales and recurring orders