# API Datero

## Resumen

`Datero` es el API REST para la **aplicación móvil de dateros** (captadores de clientes). Los dateros son registros en la tabla `dateros`, vinculados a un **vendedor responsable** (`advisor_id`). No usan el usuario web del CRM ni Laravel Sanctum sobre `users`: la autenticación es por **token opaco** propio, igual en espíritu que el API Cazador.

- **Prefijo base:** `/api/v1/datero` (Laravel monta `routes/api.php` bajo `/api`).
- **Autenticación:** `POST /auth/login` con `username` + `pin` (6 dígitos). Las rutas protegidas exigen `Authorization: Bearer {token}`.

Documentación relacionada:

- Prompt maestro para **React Native** contra este API: [PROMPT_CURSOR_REACT_NATIVE_DATERO.md](./PROMPT_CURSOR_REACT_NATIVE_DATERO.md).
- API del vendedor (alta/gestión de dateros desde el móvil del asesor): [API_CAZADOR.md](./API_CAZADOR.md).

---

## Autenticación y token

### Emisión del token

Tras un login correcto, el servidor devuelve un string aleatorio (longitud 80 caracteres) que el cliente debe guardar y enviar en cada petición protegida.

En base de datos se almacena **solo el hash SHA-256** del token (`datero_api_tokens.token`). El texto plano **no** se puede recuperar después.

### Cabecera obligatoria (rutas protegidas)

```http
Authorization: Bearer {token}
Accept: application/json
```

### Invalidación

- `POST /auth/logout` elimina el registro del token usado en esa petición.
- Si el datero pasa a `is_active = false`, o el **asesor asignado** pasa a inactivo, las peticiones con un token válido anteriormente responden **`401`** (`No autenticado.`).

### Middleware `datero.api`

Para cada petición autenticada el backend:

1. Exige Bearer token.
2. Busca el hash en `datero_api_tokens`.
3. Comprueba que el token no esté expirado (`expires_at` nulo o futuro).
4. Comprueba que el **datero** esté activo y tenga **asesor asignado activo**.
5. Actualiza `last_used_at` del token.
6. Inyecta en la petición los atributos `datero` y `datero_token` (uso interno).

---

## Flujo operativo sugerido (app móvil)

1. Pantalla de **login** (`username`, `pin`).
2. Tras login, mostrar datos del **datero** y del **vendedor responsable** (viene en la respuesta del login o en `GET /me`).
3. **Listar ciudades** (`GET /cities`) para selects en formularios de cliente (opcional pero recomendado).
4. **Listar clientes captados por este datero** (`GET /clients`).
5. **Alta de cliente** (`POST /clients`): el CRM asigna automáticamente el tipo **DATERO** y el `advisor_id` del vendedor responsable del datero.
6. **Detalle** (`GET /clients/{id}`) y **edición** (`PUT /clients/{id}`) solo de clientes registrados por el mismo datero.
7. **Cambio de PIN** (`PUT /me/pin`) cuando el usuario lo solicite.
8. **Cerrar sesión** (`POST /auth/logout`).

La creación de la cuenta de datero, asignación al asesor y datos administrativos se gestionan desde el **panel Inmopro** (o API Cazador en sección dateros), no desde este API.

---

## Modelo de datos (conceptual)

| Concepto | Descripción |
|----------|-------------|
| **Datero** | Persona que captura clientes; `username` único en `dateros` y no puede coincidir con `username` de ningún `advisor`. |
| **Cliente tipo DATERO** | Registro en `clients` con `client_type_id` del código `DATERO`. |
| **Asignación al vendedor** | `advisor_id` del cliente = `advisor_id` del datero autenticado (forzado en servidor). |
| **Trazabilidad** | `registered_by_datero_id` identifica qué datero dio de alta al cliente. El listado y el acceso por id del API Datero **solo** incluyen filas donde este campo coincide con el datero del token. |

---

## Códigos HTTP y errores comunes

| Código | Situación |
|--------|-----------|
| **200** | Éxito en GET/PUT/POST logout. |
| **201** | Cliente creado. |
| **401** | Sin Bearer, token inválido/expirado, datero inactivo o asesor inactivo/sin asignar. Cuerpo típico: `{"message":"No autenticado."}`. |
| **404** | Cliente no pertenece al datero (no coincide `registered_by_datero_id`). `{"message":"Cliente no encontrado."}`. |
| **422** | Validación Laravel, credenciales de login incorrectas, PIN actual incorrecto, duplicado de DNI/teléfono, etc. |
| **429** | Demasiados intentos de `POST /auth/login` desde la misma IP (rate limit `datero-login`, 10 por minuto). |
| **500** | Error interno; si falta el tipo de cliente `DATERO` en BD, el alta de cliente puede fallar con excepción (entorno mal sembrado). |

Errores de validación **422** siguen el formato estándar de Laravel (`errors` por campo). Además, el alta/edición de cliente puede incluir la clave **`duplicate_registration`** (mensaje legible, ver sección Clientes).

---

## Endpoints

### POST `/auth/login`

Autenticación del datero.

**Request (JSON):**

| Campo | Obligatorio | Descripción |
|-------|-------------|-------------|
| `username` | Sí | Usuario del datero. |
| `pin` | Sí | Exactamente 6 dígitos. |
| `device_name` | No | Etiqueta del token en BD (por defecto el servidor usa `"Datero"`). |

**Respuesta `200`:**

```json
{
  "token": "cadena-opaca-de-80-caracteres",
  "datero": {
    "id": 1,
    "name": "Datero QA",
    "phone": "987000999",
    "email": "datero.qa@crm-lotes.test",
    "dni": "45987654",
    "username": "datero_funcional",
    "is_active": true,
    "last_login_at": "2026-03-21T12:00:00-05:00",
    "city": {
      "id": 1,
      "name": "Lima",
      "department": "Lima"
    }
  },
  "advisor": {
    "id": 42,
    "name": "ASESOR NIVEL 1 - 1",
    "phone": "900100001",
    "email": "asesor1@inmopro.com",
    "username": "asesor1",
    "is_active": true,
    "team": {
      "id": 1,
      "name": "Team Norte",
      "color": "#0f766e"
    },
    "level": {
      "id": 1,
      "name": "NIVEL 1",
      "code": "NIVEL_1"
    }
  }
}
```

**Notas:**

- El **PIN** del datero **nunca** se devuelve en JSON.
- Si el datero no existe, está inactivo, el PIN no coincide, no tiene PIN en BD, no tiene asesor o el asesor está inactivo → **`422`** con `{"message":"Credenciales inválidas."}`.
- Tras login exitoso se actualiza `last_login_at` del datero.

---

### POST `/auth/logout`

Invalida el token enviado en `Authorization`.

**Respuesta `200`:**

```json
{
  "message": "Sesión cerrada correctamente."
}
```

---

### GET `/me`

Perfil del datero autenticado y resumen del vendedor responsable.

**Respuesta `200`:**

```json
{
  "data": {
    "datero": {
      "id": 1,
      "name": "Datero QA",
      "phone": "987000999",
      "email": "datero.qa@crm-lotes.test",
      "dni": "45987654",
      "username": "datero_funcional",
      "is_active": true,
      "last_login_at": "2026-03-21T12:00:00-05:00",
      "city": {
        "id": 1,
        "name": "Lima",
        "department": "Lima"
      }
    },
    "advisor": {
      "id": 42,
      "name": "ASESOR NIVEL 1 - 1",
      "phone": "900100001",
      "email": "asesor1@inmopro.com",
      "username": "asesor1",
      "is_active": true,
      "team": { "id": 1, "name": "Team Norte", "color": "#0f766e" },
      "level": { "id": 1, "name": "NIVEL 1", "code": "NIVEL_1" }
    }
  }
}
```

Si por inconsistencia de datos no hubiera asesor, `advisor` podría ser `null` (en la práctica el middleware impide usar el API sin asesor activo).

**Importante:** este API **no** expone `PUT /me` para editar nombre, teléfono o correo del datero; eso se gestiona en Inmopro / API Cazador (vendedor). Sí permite **`PUT /me/pin`** (siguiente apartado).

---

### PUT `/me/pin`

Cambio de PIN del datero autenticado.

**Request (JSON):**

| Campo | Obligatorio | Reglas |
|-------|-------------|--------|
| `current_pin` | Sí | 6 dígitos. |
| `pin` | Sí | 6 dígitos. |
| `pin_confirmation` | Sí | Debe coincidir con `pin` (regla `confirmed` de Laravel). |

**Respuesta `200`:**

```json
{
  "message": "PIN actualizado correctamente."
}
```

Si el PIN actual no coincide: **`422`** con `{"message":"El PIN actual no es válido."}`.

---

### GET `/cities`

Lista ciudades **activas** (`is_active = true`), ordenadas por `sort_order` y `name`.

**Query opcional:**

- `search`: búsqueda parcial en `name` o `department`.

**Respuesta `200`:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Lima",
      "department": "Lima",
      "code": "LIM"
    }
  ]
}
```

---

## Clientes (captados por el datero)

### Reglas de negocio

1. **Alcance del listado y del detalle:** solo clientes con `registered_by_datero_id` igual al `id` del datero del token.
2. **Alta (`POST /clients`):** el servidor fija:
   - `advisor_id` = `advisor_id` del datero (vendedor responsable).
   - `client_type_id` = tipo con código **`DATERO`**.
   - `registered_by_datero_id` = `id` del datero autenticado.
3. **Unicidad global de DNI y teléfono:** misma lógica que en el API Cazador. No puede existir otro cliente en el sistema con el mismo DNI (si se envía y no vacío) ni el mismo teléfono (no vacío). Respuesta **`422`** con error bajo la clave **`duplicate_registration`** y mensaje del estilo: `Cliente ya registrado por {nombre del vendedor}`.
4. **Edición (`PUT /clients/{client}`):** el cliente editado se excluye de la comprobación de duplicados. No se puede cambiar desde este API el `advisor_id` ni el tipo de cliente (solo los campos del formulario validados).

### GET `/clients`

Lista clientes captados por el datero autenticado.

**Query opcional:**

- `search`: coincidencia parcial en `name`, `dni` o `phone`.

**Respuesta `200`:** array en `data`. Cada elemento:

```json
{
  "id": 101,
  "name": "Cliente Ejemplo",
  "dni": "76543210",
  "phone": "987654321",
  "email": "cliente@demo.com",
  "referred_by": null,
  "city": {
    "id": 1,
    "name": "Lima",
    "department": "Lima"
  },
  "lots": []
}
```

En el listado, `lots` siempre es un **array vacío** `[]`.

---

### POST `/clients`

**Request (JSON):**

| Campo | Obligatorio | Descripción |
|-------|-------------|-------------|
| `name` | Sí | Máx. 255 caracteres. |
| `phone` | Sí | Máx. 50 caracteres. |
| `dni` | No | Máx. 20; si se omite o vacío, no participa en unicidad por DNI. |
| `email` | No | Email válido si se envía. |
| `referred_by` | No | Máx. 255. |
| `city_id` | No | Debe existir en `cities` si se envía. |

**Respuesta `201`:**

```json
{
  "message": "Cliente registrado.",
  "data": {
    "id": 101,
    "name": "Cliente Ejemplo",
    "dni": "76543210",
    "phone": "987654321",
    "email": "cliente@demo.com",
    "referred_by": null,
    "city": { "id": 1, "name": "Lima", "department": "Lima" },
    "lots": []
  }
}
```

---

### GET `/clients/{client}`

Detalle de un cliente **propiedad del datero** (`registered_by_datero_id`). Si el `id` es de otro cliente → **`404`** `Cliente no encontrado.`

Incluye relación con lotes:

```json
{
  "data": {
    "id": 101,
    "name": "Cliente Ejemplo",
    "dni": "76543210",
    "phone": "987654321",
    "email": "cliente@demo.com",
    "referred_by": null,
    "city": { "id": 1, "name": "Lima", "department": "Lima" },
    "lots": [
      {
        "id": 55,
        "block": "A",
        "number": 12,
        "project": "Proyecto Demo",
        "status": "LIBRE"
      }
    ]
  }
}
```

Los lotes mostrados son los asociados al cliente en el CRM (puede estar vacío si aún no hay unidades vinculadas).

---

### PUT `/clients/{client}`

Actualiza los mismos campos que en el alta (validación análoga). **`404`** si el cliente no pertenece al datero.

**Respuesta `200`:**

```json
{
  "message": "Cliente actualizado.",
  "data": {
    "id": 101,
    "name": "Cliente Actualizado",
    "dni": "76543210",
    "phone": "987654321",
    "email": "nuevo@demo.com",
    "referred_by": null,
    "city": { "id": 1, "name": "Lima", "department": "Lima" },
    "lots": []
  }
}
```

---

## Datos para pruebas (seeders)

Tras sembrar el entorno funcional (ver `FunctionalTestingSeeder` y dependencias), suele existir un datero de prueba:

- **Usuario:** `datero_funcional`
- **PIN:** `654321`

Los asesores de prueba del `AdvisorSeeder` usan PIN `123456` (solo para el API **Cazador**, no para Datero).

Asegúrate de tener el tipo de cliente **`DATERO`** en `client_types` (incluido en `ClientTypeSeeder`). Si la base ya existía antes de añadir el tipo, ejecuta de nuevo ese seeder o un `updateOrCreate` equivalente.

---

## Resumen de rutas

| Método | Ruta | Auth |
|--------|------|------|
| POST | `/auth/login` | No |
| POST | `/auth/logout` | Bearer |
| GET | `/me` | Bearer |
| PUT | `/me/pin` | Bearer |
| GET | `/cities` | Bearer |
| GET | `/clients` | Bearer |
| POST | `/clients` | Bearer |
| GET | `/clients/{client}` | Bearer |
| PUT | `/clients/{client}` | Bearer |

URL absoluta de ejemplo (Laravel Herd): `https://crm-lotes.test/api/v1/datero/auth/login` (ajustar host al proyecto).
