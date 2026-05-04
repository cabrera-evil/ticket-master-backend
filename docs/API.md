# La Cuponera SV API

Base URL local: `http://localhost:8080/api/v1`

Todas las respuestas son JSON.

## Autenticacion

La API usa JWT en cookies HttpOnly. Al iniciar sesion o registrarse se establecen dos cookies:

| Cookie | Contenido | Duracion |
|---|---|---|
| `jwt` | Access token | 15 minutos |
| `refreshToken` | Refresh token | 7 dias |

Los endpoints protegidos leen el access token de la cookie `jwt`. Como alternativa, tambien se acepta el header `Authorization: Bearer {token}`.

El middleware renueva el access token automaticamente cuando queda menos de 5 minutos de vigencia, o cuando el cliente recibe un 401 con refresh token valido. En ese caso la respuesta incluye los headers:

```
X-Token-Refreshed: true
X-New-Access-Token: <nuevo_access_token>
```

---

## Formato de respuesta

Todas las respuestas exitosas siguen esta estructura:

```json
{
  "statusCode": 200,
  "message": "Descripcion del resultado.",
  "data": {}
}
```

Los endpoints paginados incluyen ademas un campo `pagination`:

```json
{
  "statusCode": 200,
  "message": "Descripcion del resultado.",
  "data": [],
  "pagination": {
    "page": 1,
    "pageSize": 15,
    "total": 42,
    "pageCount": 3
  }
}
```

`data` solo esta presente cuando hay contenido que devolver.

---

## Auth

### `POST /register/client`

Registra un cliente. Establece cookies de autenticacion.

Campos: `username`, `email`, `password`, `password_confirmation`, `first_name`, `last_name`, `dui`, `birth_date`.

**Respuesta** `201`:

```json
{
  "statusCode": 201,
  "message": "Cliente registrado correctamente.",
  "data": {
    "jwt": "<access_token>",
    "refreshToken": "<refresh_token>",
    "user": {
      "id": 1,
      "username": "jdoe",
      "email": "jdoe@example.com",
      "role": "client"
    }
  }
}
```

---

### `POST /register/company`

Registra una empresa en estado `pending`. Establece cookies de autenticacion.

Campos: `username`, `email`, `password`, `password_confirmation`, `name`, `nit`, `address`, `phone`.

**Respuesta** `201`:

```json
{
  "statusCode": 201,
  "message": "Empresa registrada correctamente. Queda pendiente de aprobacion.",
  "data": {
    "jwt": "<access_token>",
    "refreshToken": "<refresh_token>",
    "user": {
      "id": 2,
      "username": "mi_empresa",
      "email": "contacto@empresa.com",
      "role": "company"
    }
  }
}
```

---

### `POST /login`

Inicia sesion por `email` o `username`. Establece cookies de autenticacion.

```json
{
  "login": "admin",
  "password": "Password123"
}
```

**Respuesta** `200`:

```json
{
  "statusCode": 200,
  "message": "Inicio de sesion correcto.",
  "data": {
    "jwt": "<access_token>",
    "refreshToken": "<refresh_token>",
    "user": {
      "id": 1,
      "username": "admin",
      "email": "info@cabrera-dev.com",
      "role": "admin"
    }
  }
}
```

**Cuenta inactiva** `403`:

```json
{
  "statusCode": 403,
  "message": "La cuenta no esta activa."
}
```

---

### `POST /refresh-token`

Emite un nuevo par de tokens usando el `refreshToken` de la cookie. No requiere access token valido.

**Respuesta** `200`:

```json
{
  "statusCode": 200,
  "message": "Tokens actualizados correctamente.",
  "data": {
    "jwt": "<nuevo_access_token>",
    "refreshToken": "<nuevo_refresh_token>"
  }
}
```

**Refresh token invalido o expirado** `401`:

```json
{
  "statusCode": 401,
  "message": "INVALID_REFRESH_TOKEN"
}
```

---

### `POST /logout`

Cierra la sesion. Invalida las cookies de autenticacion. Requiere autenticacion.

**Respuesta** `200`:

```json
{
  "statusCode": 200,
  "message": "Sesion cerrada correctamente."
}
```

---

### `POST /password/forgot`

Solicita recuperacion de contrasena para cualquier rol.

Campo: `email`.

**Respuesta** `202`:

```json
{
  "statusCode": 202,
  "message": "Si el correo existe, se enviaran instrucciones para restablecer la contrasena."
}
```

---

### `POST /password/reset`

Restablece la contrasena con token.

Campos: `email`, `password`, `password_confirmation`, `token`.

**Respuesta** `200`:

```json
{
  "statusCode": 200,
  "message": "Contrasena actualizada correctamente."
}
```

---

## Admin

Todos requieren autenticacion de administrador.

### `POST /admin/users`

Crea otro administrador.

Campos: `name`, `username`, `email`, `password`, `password_confirmation`.

**Respuesta** `201`:

```json
{
  "statusCode": 201,
  "message": "Administrador registrado correctamente.",
  "data": {
    "id": 3,
    "username": "nuevo_admin",
    "email": "nuevo@lacuponera.test",
    "role": "admin"
  }
}
```

---

### `GET /admin/companies/pending`

Lista empresas pendientes de aprobacion (paginado).

**Respuesta** `200`:

```json
{
  "statusCode": 200,
  "message": "Empresas pendientes obtenidas correctamente.",
  "data": [
    {
      "id": 1,
      "name": "Empresa Ejemplo",
      "nit": "0614-123456-001-1",
      "status": "pending"
    }
  ],
  "pagination": {
    "page": 1,
    "pageSize": 15,
    "total": 3,
    "pageCount": 1
  }
}
```

---

### `PUT /admin/companies/{company}/approve`

Aprueba una empresa pendiente.

```json
{
  "commission_percentage": 12.5
}
```

**Respuesta** `200`:

```json
{
  "statusCode": 200,
  "message": "Empresa aprobada correctamente.",
  "data": {
    "id": 1,
    "name": "Empresa Ejemplo",
    "status": "approved",
    "commission_percentage": 12.5
  }
}
```

---

### `PUT /admin/companies/{company}/reject`

Rechaza una empresa pendiente.

```json
{
  "reason": "Datos incompletos."
}
```

**Respuesta** `200`:

```json
{
  "statusCode": 200,
  "message": "Empresa rechazada correctamente.",
  "data": {
    "id": 1,
    "name": "Empresa Ejemplo",
    "status": "rejected"
  }
}
```

---

## Errores

Los errores de validacion siguen el formato estandar de Laravel:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

Los errores de autorizacion:

```json
{
  "message": "No tiene permiso para realizar esta accion."
}
```

Sin autenticacion o token expirado sin refresh token valido:

```json
{
  "message": "Unauthenticated."
}
```
