# La Cuponera SV API

Base URL local: `http://localhost:8080/api/v1`

Todas las respuestas son JSON. Los endpoints protegidos usan `Authorization: Bearer {token}`.

## Auth

### `POST /register/client`

Registra un cliente.

Campos: `username`, `email`, `password`, `password_confirmation`, `first_name`, `last_name`, `dui`, `birth_date`.

### `POST /register/company`

Registra una empresa en estado `pending`.

Campos: `username`, `email`, `password`, `password_confirmation`, `name`, `nit`, `address`, `phone`.

### `POST /login`

Inicia sesion por `email` o `username`.

```json
{
  "login": "admin",
  "password": "Password123",
  "device_name": "postman"
}
```

### `POST /logout`

Revoca el token actual. Requiere autenticacion.

### `POST /password/forgot`

Solicita recuperacion de contrasena para cualquier rol.

### `POST /password/reset`

Restablece la contrasena con token.

## Admin

Todos requieren token de administrador.

### `POST /admin/users`

Crea otro administrador.

Campos: `name`, `username`, `email`, `password`, `password_confirmation`.

### `GET /admin/companies/pending`

Lista empresas pendientes de aprobacion.

### `PUT /admin/companies/{company}/approve`

Aprueba una empresa pendiente.

```json
{
  "commission_percentage": 12.5
}
```

### `PUT /admin/companies/{company}/reject`

Rechaza una empresa pendiente.

```json
{
  "reason": "Datos incompletos."
}
```

## Errores

Validacion:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

Autorizacion:

```json
{
  "message": "No tiene permiso para realizar esta accion."
}
```
