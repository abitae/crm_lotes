# API Cazador

## Resumen
`Cazador` es el API para vendedores. El acceso es solo para asesores (`advisors`) y usa autenticación por token (Sanctum-style).

**Base path:** `/api/v1/cazador`

**Autenticación:**
- Login con `username` y `pin` de 6 dígitos.
- Las rutas protegidas requieren el header: `Authorization: Bearer {token}`.

**Seeders útiles para pruebas:**
- Los vendedores seeded usan PIN por defecto: `123456`
- Ejemplos de usuarios: `director1`, `gerente1`, `senior1`, `asesor1`

## Flujo principal
1. El vendedor hace login.
2. Consulta o actualiza su perfil.
3. Crea o edita sus clientes propios.
4. Registra tickets de atención por proyecto para sus clientes propios.
5. Consulta proyectos y lotes disponibles.
6. Registra una pre-reserva subiendo una imagen del voucher.
7. El lote pasa a `PRERESERVA`.
8. Un administrador revisa la solicitud desde el backend web y aprueba o rechaza.

## Endpoints

### POST `/auth/login`
Login del vendedor.

**Request (JSON):**

| Campo        | Tipo   | Requerido | Descripción                          |
|-------------|--------|-----------|--------------------------------------|
| `username`  | string | sí        | Máx. 255 caracteres                  |
| `pin`       | string | sí        | Exactamente 6 dígitos                |
| `device_name` | string | no      | Nombre del dispositivo (default: `Cazador`), máx. 255 |

```json
{
  "username": "asesor1",
  "pin": "123456",
  "device_name": "Android"
}
```

**Response `200`:**

```json
{
  "token": "plain-text-token",
  "advisor": {
    "id": 1,
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

**Errores:**
- `422` — Credenciales inválidas (`message`: "Credenciales inválidas.")
- `422` — Validación (pin no 6 dígitos, username vacío, etc.)

### POST `/auth/logout`
Invalida el token actual. Requiere autenticación.

**Response `200`:**

```json
{
  "message": "Sesión cerrada correctamente."
}
```

---

### GET `/me`
Devuelve el perfil del vendedor autenticado.

**Response `200`:**

```json
{
  "data": {
    "id": 1,
    "name": "ASESOR NIVEL 1 - 1",
    "phone": "900100001",
    "email": "asesor1@inmopro.com",
    "username": "asesor1",
    "team": { "id": 1, "name": "Team Norte" },
    "level": { "id": 1, "name": "NIVEL 1" }
  }
}
```

### PUT `/me`
Actualiza perfil del vendedor.

**Request (JSON):**

| Campo     | Tipo   | Requerido | Descripción                    |
|-----------|--------|-----------|--------------------------------|
| `name`    | string | sí        | Máx. 255                       |
| `phone`   | string | sí        | Máx. 50                        |
| `email`   | string | sí        | Email válido, máx. 255         |
| `username`| string | sí        | Máx. 255, único (excl. el actual) |

```json
{
  "name": "Asesor API",
  "phone": "999111222",
  "email": "asesor1@inmopro.com",
  "username": "asesor1"
}
```

**Response `200`:**

```json
{
  "message": "Perfil actualizado.",
  "data": { ... }
}
```

### PUT `/me/pin`
Actualiza el PIN del vendedor.

**Request (JSON):**

| Campo             | Tipo   | Requerido | Descripción     |
|-------------------|--------|-----------|-----------------|
| `current_pin`     | string | sí        | 6 dígitos       |
| `pin`             | string | sí        | 6 dígitos       |
| `pin_confirmation`| string | sí        | Debe coincidir con `pin` |

```json
{
  "current_pin": "123456",
  "pin": "654321",
  "pin_confirmation": "654321"
}
```

**Response `200`:**

```json
{
  "message": "PIN actualizado correctamente."
}
```

**Errores:**
- `422` — PIN actual incorrecto (`message`: "El PIN actual no es válido.")
- `422` — Validación (dígitos, confirmación)

## Clientes propios

**Reglas:**
- El vendedor solo ve y gestiona clientes con `advisor_id` propio y tipo `PROPIO`.
- Los clientes creados por el API se guardan automáticamente con tipo `PROPIO`.

### GET `/clients`
Lista clientes propios.

**Query params opcionales:**

| Parametro | Descripción |
|-----------|-------------|
| `search`  | Busca en `name`, `dni` y `phone` |

**Response `200`:**

```json
{
  "data": [
    {
      "id": 101,
      "name": "Cliente Cazador",
      "dni": "76543210",
      "phone": "987654321",
      "email": "cliente@demo.com",
      "referred_by": "Campaña digital",
      "city": { "id": 1, "name": "Lima", "department": "Lima" },
      "lots": []
    }
  ]
}
```

### POST `/clients`
Crea un cliente propio.

**Request (JSON):**

| Campo        | Tipo   | Requerido | Descripción        |
|--------------|--------|-----------|--------------------|
| `name`       | string | sí        | Máx. 255           |
| `dni`        | string | no        | Máx. 20            |
| `phone`      | string | sí        | Máx. 50            |
| `email`      | string | no        | Email válido, máx. 255 |
| `referred_by`| string | no        | Máx. 255           |
| `city_id`    | int    | no        | Debe existir en `cities` |

```json
{
  "name": "Cliente Cazador",
  "dni": "76543210",
  "phone": "987654321",
  "email": "cliente@demo.com",
  "referred_by": "Campaña digital",
  "city_id": 1
}
```

**Response `201`:**

```json
{
  "message": "Cliente registrado.",
  "data": {
    "id": 101,
    "name": "Cliente Cazador",
    "dni": "76543210",
    "phone": "987654321",
    "email": "cliente@demo.com",
    "referred_by": "Campaña digital",
    "city": { "id": 1, "name": "Lima", "department": "Lima" },
    "lots": []
  }
}
```

### GET `/clients/{client}`
Detalle de cliente propio con lotes asociados.

**Response `200`:** `{ "data": { ...client, "lots": [ { "id", "block", "number", "project", "status" } ] } }`

**Errores:**
- `404` — Cliente no encontrado o no pertenece al vendedor (`message`: "Cliente no encontrado.")

### PUT `/clients/{client}`
Actualiza cliente propio. Mismos campos que POST (name, dni, phone, email, referred_by, city_id).

**Response `200`:** `{ "message": "Cliente actualizado.", "data": { ... } }`

**Errores:**
- `404` — Cliente no encontrado o no pertenece al vendedor

## Tickets de atención

### GET `/attention-tickets`
Lista los tickets del vendedor autenticado, ordenados por `scheduled_at` (nulos al final) y `created_at` descendente.

**Response `200`:**

```json
{
  "data": [
    {
      "id": 1,
      "status": "pendiente",
      "scheduled_at": null,
      "notes": "Cliente solicita visita.",
      "created_at": "2026-03-13T20:10:00-05:00",
      "client": { "id": 25, "name": "Cliente Cazador", "dni": "76543210" },
      "project": { "id": 1, "name": "Villa Norte - Mito", "location": "Junin" }
    }
  ]
}
```

Fechas en formato ISO 8601 (Atom).

### POST `/attention-tickets`
Crea un ticket de atención.

**Reglas:**
- El cliente debe pertenecer al vendedor y ser de tipo `PROPIO`.
- El ticket se registra por proyecto; el estado inicial es `pendiente`.
- La agenda (`scheduled_at`) se define desde el backend administrativo.

**Request (JSON):**

| Campo       | Tipo | Requerido | Descripción        |
|-------------|------|-----------|--------------------|
| `client_id`| int  | sí        | Debe existir en `clients` |
| `project_id`| int | sí        | Debe existir en `projects` |
| `notes`    | string | no     | Máx. 1000          |

```json
{
  "client_id": 25,
  "project_id": 1,
  "notes": "Cliente solicita visita de información."
}
```

**Response `201`:**

```json
{
  "message": "Ticket de atención registrado.",
  "data": {
    "id": 1,
    "status": "pendiente",
    "scheduled_at": null,
    "notes": "Cliente solicita visita de información.",
    "created_at": "2026-03-13T20:10:00-05:00",
    "client": { "id": 25, "name": "Cliente Cazador", "dni": "76543210" },
    "project": { "id": 1, "name": "Villa Norte - Mito", "location": "Junin" }
  }
}
```

**Errores:**
- `422` — El cliente no pertenece al vendedor autenticado

### POST `/attention-tickets/{attentionTicket}/cancel`
Cancela un ticket propio del vendedor.

**Reglas:**
- Solo se puede cancelar tickets del vendedor.
- No se puede cancelar si el estado es `realizado` o `cancelado`.
- Opcionalmente se puede enviar `notes`; se concatena al historial del ticket.

**Request (JSON):** `notes` (opcional, string, máx. 1000)

```json
{
  "notes": "El cliente pidió cancelar la solicitud."
}
```

**Response `200`:**

```json
{
  "message": "Ticket cancelado correctamente.",
  "data": { ... ticket actualizado ... }
}
```

**Errores:**
- `404` — Ticket no encontrado o no pertenece al vendedor
- `422` — El ticket ya no puede cancelarse (realizado/cancelado)

## Proyectos

### GET `/projects`
Lista proyectos con conteo de lotes, ordenados por nombre.

**Response `200`:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Villa Norte - Mito",
      "location": "Junin",
      "total_lots": 50,
      "lots_count": 50
    }
  ]
}
```

### GET `/projects/{project}`
Detalle de proyecto.

**Response `200`:**

```json
{
  "data": {
    "id": 1,
    "name": "Villa Norte - Mito",
    "location": "Junin",
    "total_lots": 50,
    "lots_count": 50,
    "blocks": ["A", "B"]
  }
}
```

## Lotes

### GET `/lots`
Lista lotes. Por defecto solo lotes con estado `LIBRE` (`available_only=true`).

**Query params opcionales:**

| Parametro       | Descripción |
|-----------------|-------------|
| `project_id`   | Filtrar por proyecto |
| `search`       | Busca en `block` y `number` |
| `available_only` | `true` (default) = solo estado LIBRE |

**Response `200`:**

```json
{
  "data": [
    {
      "id": 1,
      "block": "A",
      "number": 1,
      "area": "105.00",
      "price": "30000.00",
      "project": { "id": 1, "name": "Villa Norte - Mito" },
      "status": { "id": 1, "name": "Libre", "code": "LIBRE", "color": "#10b981" },
      "can_pre_reserve": true,
      "client": null,
      "advisor": null,
      "pre_reservations": []
    }
  ]
}
```

En listado, `client` y `advisor` van como `null` y `pre_reservations` como `[]`; en GET detalle se completan con datos.

### GET `/lots/{lot}`
Detalle de lote. Incluye `client`, `advisor` y `pre_reservations` (últimas con `id`, `status`, `created_at`).

**Response `200`:** `{ "data": { ...lote con detalle... } }`

## Pre-reservas

### POST `/lots/{lot}/pre-reservations`
Registra una pre-reserva para un lote libre. Content-Type: `multipart/form-data`.

**Reglas:**
- El cliente debe pertenecer al vendedor y ser de tipo `PROPIO`.
- `lot_id` debe coincidir con el lote de la URL.
- `project_id` debe coincidir con el proyecto del lote.
- El lote debe estar en estado `LIBRE`.
- No puede existir una pre-reserva activa (PENDIENTE o APROBADA) para el lote.

**Request (multipart/form-data):**

| Campo               | Tipo   | Requerido | Descripción |
|---------------------|--------|-----------|-------------|
| `client_id`         | int    | sí        | Debe existir en `clients` |
| `project_id`        | int    | sí        | Debe existir en `projects` |
| `lot_id`            | int    | sí        | Debe existir en `lots` |
| `amount`            | numeric| sí        | Mín. 0.01 |
| `voucher_image`     | file   | sí        | Imagen (jpg, png, etc.), máx. 5 MB (5120 KB) |
| `payment_reference` | string | no        | Máx. 255 |
| `notes`             | string | no        | Máx. 1000 |

Ejemplo:

```text
client_id=25
project_id=1
lot_id=10
amount=1500.00
voucher_image=(archivo imagen)
payment_reference=OP-12345
notes=Abono inicial
```

**Response `201`:**

```json
{
  "message": "Pre-reserva registrada y pendiente de aprobación.",
  "data": {
    "id": 1,
    "status": "PENDIENTE",
    "amount": "1500.00",
    "project": { "id": 1, "name": "Villa Norte - Mito" },
    "lot": { "id": 10, "block": "A", "number": 5 },
    "voucher_url": "https://crm-lotes.test/storage/cazador/pre-reservations/archivo.png"
  }
}
```

**Errores:**
- `422` — El cliente no pertenece al vendedor autenticado
- `422` — El lote enviado no coincide con la ruta
- `422` — El lote no pertenece al proyecto enviado
- `422` — La unidad no está disponible para pre-reserva
- `422` — La unidad ya tiene una pre-reserva activa
- `422` — Validación (amount, voucher_image, etc.)
- `500` — No existe el estado de pre-reserva configurado en el sistema

## Estados del lote en este flujo

| Código       | Descripción |
|-------------|-------------|
| `LIBRE`     | Disponible para pre-reserva |
| `PRERESERVA`| Solicitud registrada, pendiente de revisión |
| `RESERVADO` | Aprobado desde backend administrativo |
| `TRANSFERIDO` | Venta cerrada |
| `CUOTAS`    | Operación financiada |

## Aprobación administrativa

La aprobación o rechazo de pre-reservas **no** se hace por este API; se gestiona en el backend web.

- **Ruta web:** `/inmopro/lot-pre-reservations`
- **Acciones:** aprobar, rechazar

**Al aprobar:** la pre-reserva pasa a `APROBADA` y el lote a `RESERVADO`.

**Al rechazar:** la pre-reserva pasa a `RECHAZADA` y el lote vuelve a `LIBRE`.

**Tickets de atención:** el vendedor crea el ticket en `pendiente`; la administración agenda desde `/inmopro/attention-tickets`. El vendedor puede cancelar desde Cazador mientras el ticket no esté `realizado` o `cancelado`.

## Resumen de rutas (requieren auth salvo login)

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `auth/login` | Login (username + pin) |
| POST | `auth/logout` | Cerrar sesión |
| GET  | `me` | Perfil |
| PUT  | `me` | Actualizar perfil |
| PUT  | `me/pin` | Cambiar PIN |
| GET  | `clients` | Listar clientes |
| POST | `clients` | Crear cliente |
| GET  | `clients/{client}` | Ver cliente |
| PUT  | `clients/{client}` | Actualizar cliente |
| GET  | `attention-tickets` | Listar tickets |
| POST | `attention-tickets` | Crear ticket |
| POST | `attention-tickets/{id}/cancel` | Cancelar ticket |
| GET  | `projects` | Listar proyectos |
| GET  | `projects/{project}` | Ver proyecto |
| GET  | `lots` | Listar lotes |
| GET  | `lots/{lot}` | Ver lote |
| POST | `lots/{lot}/pre-reservations` | Crear pre-reserva |

## Seeders relacionados

Para un entorno de prueba consistente, `DatabaseSeeder` carga:

- Teams, niveles de asesor, estados de lote, estados de comisión
- Proyectos, tipos de cliente, ciudades
- Vendedores con credenciales Cazador (PIN por defecto `123456`)
- Clientes vinculados a vendedor y ciudad, lotes, pre-reservas de ejemplo

## Verificación recomendada

1. `php artisan migrate:fresh --seed`
2. `npm run build` (o `npm run dev`)
3. Probar login: `POST /api/v1/cazador/auth/login` con `asesor1` / `123456`
4. Consultar `GET /api/v1/cazador/projects` con header `Authorization: Bearer {token}`
5. Crear un cliente propio, un ticket de atención y una pre-reserva con voucher
