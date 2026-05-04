# La Cuponera SV API

Base URL local: `http://localhost:8080/api/v1`

Todas las respuestas son JSON. Los endpoints protegidos usan `Authorization: Bearer {token}`.

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

Registra un cliente.

Campos: `username`, `email`, `password`, `password_confirmation`, `first_name`, `last_name`, `dui`, `birth_date`.

**Respuesta** `201`:

```json
{
  "statusCode": 201,
  "message": "Cliente registrado correctamente.",
  "data": {
    "id": 1,
    "username": "jdoe",
    "email": "jdoe@example.com",
    "role": "client"
  }
}
```

---

### `POST /register/company`

Registra una empresa en estado `pending`.

Campos: `username`, `email`, `password`, `password_confirmation`, `name`, `nit`, `address`, `phone`.

**Respuesta** `201`:

```json
{
  "statusCode": 201,
  "message": "Empresa registrada correctamente. Queda pendiente de aprobacion.",
  "data": {
    "id": 2,
    "username": "mi_empresa",
    "email": "contacto@empresa.com",
    "role": "company"
  }
}
```

---

### `POST /login`

Inicia sesion por `email` o `username`.

```json
{
  "login": "admin",
  "password": "Password123",
  "device_name": "postman"
}
```

**Respuesta** `200`:

```json
{
  "statusCode": 200,
  "message": "Inicio de sesion correcto.",
  "data": {
    "user": {
      "id": 1,
      "username": "admin",
      "email": "admin@lacuponera.test",
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

### `POST /logout`

Revoca el token actual. Requiere autenticacion.

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

Todos requieren token de administrador.

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
