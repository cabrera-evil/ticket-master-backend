# La Cuponera SV Backend

API backend en Laravel para la Fase 1 del proyecto "La Cuponera SV".

## Stack

- PHP 8.5 con mise
- Laravel 13
- JWT con cookies HttpOnly (`firebase/php-jwt`)
- PostgreSQL
- SMTP local con Mailpit
- Docker Compose

## Funcionalidad Fase 1

- Login universal para administradores, empresas y clientes.
- Autenticacion con JWT en cookies HttpOnly (`jwt` + `refreshToken`).
- Renovacion automatica de tokens proximos a vencer sin intervencion del cliente.
- Registro de clientes.
- Registro de empresas en estado pendiente.
- Registro de nuevos administradores por un administrador.
- Aprobacion y rechazo de empresas por un administrador.
- Recuperacion de contrasena para cualquier tipo de usuario.
- Validaciones y respuestas JSON de error.
- Migraciones para todas las tablas necesarias del proyecto completo.

## Setup local con mise

```bash
mise install
mise run install
cp .env.example .env
```

La base de datos del proyecto es PostgreSQL. Las migraciones deben ejecutarse contra PostgreSQL desde Docker o contra una instancia local compatible.

## Setup con Docker

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

API local:

```text
http://localhost:8080/api/v1
```

Mailpit:

```text
http://localhost:8025
```

## Admin inicial

El seeder crea un administrador por defecto:

```text
username: admin
email: admin@lacuponera.test
password: Password123
```

Puede cambiarse con `ADMIN_NAME`, `ADMIN_USERNAME`, `ADMIN_EMAIL` y `ADMIN_PASSWORD` en `.env`.

## Pruebas

Las pruebas usan PostgreSQL. Cree la base de pruebas una vez si desea correrlas:

```bash
docker compose exec db createdb -U lacuponera lacuponera_test
```

```bash
docker compose exec app php artisan test
```

O con mise:

```bash
mise run test
```

`mise run test` asume que las variables `DB_*` apuntan a PostgreSQL accesible desde el entorno local. Para el flujo recomendado, use Docker.

## Documentacion API

La documentacion se genera automaticamente con Scramble (OpenAPI 3.1):

- UI interactiva: `http://localhost:8080/api/docs`
- OpenAPI JSON: `http://localhost:8080/api/docs.json`

Export manual del contrato a archivo:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan scramble:export --path=docs/openapi.json
```

Referencia rapida: [docs/API.md](docs/API.md).
