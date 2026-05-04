# Development

This API is intentionally small: Laravel + PostgreSQL + SMTP. Redis is not included yet; add it later only if the project starts using Redis-backed cache, queues, or rate limiting.

## Requirements

- mise
- Docker and Docker Compose
- PHP and Composer through mise

Install local tools:

```bash
mise install
mise run install
```

## Environment

Create the local env file:

```bash
cp .env.example .env
```

Default database settings:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=lacuponera
DB_USERNAME=lacuponera
DB_PASSWORD=secret
```

Default SMTP settings use Mailpit:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```

## Start Services

Start Laravel, PostgreSQL, and Mailpit:

```bash
docker compose up -d --build
```

URLs:

```text
API:     http://localhost:8080/api/v1
Mailpit: http://localhost:8025
```

API docs (Scramble/OpenAPI):

```text
UI:       http://localhost:8080/api/docs
OpenAPI:  http://localhost:8080/api/docs.json
```

## First-Time Laravel Setup

Run the key generation once:

```bash
docker compose exec app php artisan key:generate
```

Run migrations only when you are ready to create/update the PostgreSQL schema:

```bash
docker compose exec app php artisan migrate --seed
```

The seeder creates the default administrator:

```text
username: admin
email: admin@lacuponera.test
password: Password123
```

Change those values with `ADMIN_NAME`, `ADMIN_USERNAME`, `ADMIN_EMAIL`, and `ADMIN_PASSWORD` in `.env`.

## Migration Commands

Run pending migrations:

```bash
docker compose exec app php artisan migrate
```

Run migrations and seeders:

```bash
docker compose exec app php artisan migrate --seed
```

Rebuild the development database from scratch:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Only use `migrate:fresh --seed` in development because it drops existing tables.

## Tests

Tests are configured for PostgreSQL database `lacuponera_test`. Create it once if you want to run the test suite:

```bash
docker compose exec db createdb -U lacuponera lacuponera_test
```

Then run:

```bash
docker compose exec app php artisan test
```

## Useful Commands

View API routes without running migrations:

```bash
docker compose exec app php artisan route:list --path=api
```

Open a Laravel shell:

```bash
docker compose exec app php artisan tinker
```

Read logs:

```bash
docker compose logs -f app
docker compose logs -f db
docker compose logs -f mailpit
```

## API Documentation Integration

This project uses Scramble to generate OpenAPI 3.1 docs from code (routes, `FormRequest`, resources).

- Documentation UI route: `/api/docs`
- OpenAPI JSON route: `/api/docs.json`
- Scramble UI theme default: `dark` (configured in `config/scramble.php`)

You can change the theme by setting `scramble.ui.theme` to `light`, `dark`, or `system`.

Export JSON to a file:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan scramble:export --path=docs/openapi.json
```
