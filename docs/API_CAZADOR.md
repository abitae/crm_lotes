# API Cazador

## Resumen
`Cazador` es el API para vendedores. El acceso es solo para `advisors` y usa autenticacion por token propio.

Base path:

`/api/v1/cazador`

Autenticacion:

- Login con `username` y `pin` de 6 digitos.
- Las rutas protegidas requieren header `Authorization: Bearer {token}`.

Seeders utiles para pruebas:

- Los vendedores seeded usan PIN por defecto: `123456`
- Ejemplos de usuarios: `director1`, `gerente1`, `senior1`, `asesor1`

## Flujo principal
1. El vendedor hace login.
2. Consulta o actualiza su perfil.
3. Crea o edita sus clientes propios.
4. Registra tickets de atencion por proyecto para sus clientes propios.
5. Consulta proyectos y lotes disponibles.
6. Registra una pre-reserva subiendo una imagen del voucher.
7. El lote pasa a `PRERESERVA`.
8. Un administrador revisa la solicitud desde el backend web y aprueba o rechaza.

## Endpoints

### POST `/auth/login`
Login del vendedor.

Request:

```json
{
  "username": "asesor1",
  "pin": "123456",
  "device_name": "Android"
}
```

Response `200`:

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

Errores tipicos:

- `422` credenciales invalidas

### POST `/auth/logout`
Invalida el token actual.

Response `200`:

```json
{
  "message": "Sesion cerrada correctamente."
}
```

### GET `/me`
Devuelve el perfil autenticado.

### PUT `/me`
Actualiza perfil del vendedor.

Request:

```json
{
  "name": "Asesor API",
  "phone": "999111222",
  "email": "asesor1@inmopro.com",
  "username": "asesor1"
}
```

### PUT `/me/pin`
Actualiza el PIN del vendedor.

Request:

```json
{
  "current_pin": "123456",
  "pin": "654321",
  "pin_confirmation": "654321"
}
```

## Clientes propios

Reglas:

- El vendedor solo ve clientes con `advisor_id` propio.
- Los clientes creados por el API se guardan automaticamente con tipo `PROPIO`.

### GET `/clients`
Lista clientes propios.

Query params opcionales:

- `search`

### POST `/clients`
Crea un cliente propio.

Request:

```json
{
  "name": "Cliente Cazador",
  "dni": "76543210",
  "phone": "987654321",
  "email": "cliente@demo.com",
  "referred_by": "Campana digital",
  "city_id": 1
}
```

Response `201`:

```json
{
  "message": "Cliente registrado.",
  "data": {
    "id": 101,
    "name": "Cliente Cazador",
    "dni": "76543210",
    "phone": "987654321",
    "email": "cliente@demo.com",
    "referred_by": "Campana digital",
    "city": {
      "id": 1,
      "name": "Lima",
      "department": "Lima"
    },
    "lots": []
  }
}
```

### GET `/clients/{client}`
Detalle de cliente propio con lotes asociados.

### PUT `/clients/{client}`
Actualiza cliente propio.

## Tickets de atencion

### GET `/attention-tickets`
Lista los tickets del vendedor autenticado.

Response por item:

```json
{
  "id": 1,
  "status": "pendiente",
  "scheduled_at": null,
  "notes": "Cliente solicita visita.",
  "created_at": "2026-03-13T20:10:00-05:00",
  "client": {
    "id": 25,
    "name": "Cliente Cazador",
    "dni": "76543210"
  },
  "project": {
    "id": 1,
    "name": "Villa Norte - Mito",
    "location": "Junin"
  }
}
```

### POST `/attention-tickets`
Crea un ticket de atencion.

Reglas:

- El cliente debe pertenecer al vendedor autenticado.
- El cliente debe ser de tipo `PROPIO`.
- El ticket se registra por `proyecto`, no por `lote`.
- El estado inicial siempre es `pendiente`.
- La agenda se define luego desde el backend administrativo.

Request:

```json
{
  "client_id": 25,
  "project_id": 1,
  "notes": "Cliente solicita visita de informacion."
}
```

Response `201`:

```json
{
  "message": "Ticket de atencion registrado.",
  "data": {
    "id": 1,
    "status": "pendiente",
    "scheduled_at": null,
    "notes": "Cliente solicita visita de informacion.",
    "created_at": "2026-03-13T20:10:00-05:00",
    "client": {
      "id": 25,
      "name": "Cliente Cazador",
      "dni": "76543210"
    },
    "project": {
      "id": 1,
      "name": "Villa Norte - Mito",
      "location": "Junin"
    }
  }
}
```

### POST `/attention-tickets/{attentionTicket}/cancel`
Cancela un ticket propio del vendedor.

Reglas:

- Solo se puede cancelar tickets del vendedor autenticado.
- No se puede cancelar un ticket ya `realizado` o `cancelado`.
- Se puede enviar una observacion opcional.

Request:

```json
{
  "notes": "El cliente pidio cancelar la solicitud."
}
```

Response `200`:

```json
{
  "message": "Ticket cancelado correctamente."
}
```

## Proyectos

### GET `/projects`
Lista proyectos.

### GET `/projects/{project}`
Detalle de proyecto.

## Lotes

### GET `/lots`
Lista lotes.

Query params opcionales:

- `project_id`
- `search`
- `available_only` (`true` por defecto)

Response por lote:

```json
{
  "id": 1,
  "block": "A",
  "number": 1,
  "area": "105.00",
  "price": "30000.00",
  "project": {
    "id": 1,
    "name": "Villa Norte - Mito"
  },
  "status": {
    "id": 1,
    "name": "Libre",
    "code": "LIBRE",
    "color": "#10b981"
  },
  "can_pre_reserve": true,
  "client": null,
  "advisor": null,
  "pre_reservations": []
}
```

### GET `/lots/{lot}`
Detalle de lote.

## Pre-reservas

### POST `/lots/{lot}/pre-reservations`
Registra una pre-reserva para un lote libre.

Reglas:

- El cliente debe pertenecer al vendedor autenticado.
- El `lot_id` enviado debe coincidir con el lote de la ruta.
- El proyecto enviado debe coincidir con el lote enviado.
- El cliente debe ser de tipo `PROPIO`.
- El lote debe estar en estado `LIBRE`.
- No puede existir una pre-reserva activa previa para el lote.
- El monto es obligatorio y debe ser mayor a cero.
- El voucher se sube como archivo de imagen.

Request `multipart/form-data`:

- `client_id`
- `project_id`
- `lot_id`
- `amount`
- `voucher_image`
- `payment_reference` opcional
- `notes` opcional

Ejemplo esperado:

```text
client_id=25
project_id=1
lot_id=10
amount=1500.00
voucher_image=(archivo)
payment_reference=OP-12345
notes=Abono inicial
```

Response `201`:

```json
{
  "message": "Pre-reserva registrada y pendiente de aprobacion.",
  "data": {
    "id": 1,
    "status": "PENDIENTE",
    "amount": "1500.00",
    "project": {
      "id": 1,
      "name": "Villa Norte - Mito"
    },
    "lot": {
      "id": 10,
      "block": "A",
      "number": 5
    },
    "voucher_url": "https://crm-lotes.test/storage/cazador/pre-reservations/archivo.png"
  }
}
```

Errores tipicos:

- `422` cliente no pertenece al vendedor
- `422` lote enviado no coincide con la ruta
- `422` lote no pertenece al proyecto enviado
- `422` unidad no disponible para pre-reserva
- `422` unidad ya con pre-reserva activa
- `500` estado `PRERESERVA` no configurado

## Estados del lote en este flujo

- `LIBRE`: disponible para pre-reserva
- `PRERESERVA`: solicitud registrada y pendiente de revision
- `RESERVADO`: aprobado desde backend administrativo
- `TRANSFERIDO`: venta cerrada
- `CUOTAS`: operacion financiada

## Aprobacion administrativa
La aprobacion no ocurre en el API.

Se gestiona en backend web:

- ruta web: `/inmopro/lot-pre-reservations`
- acciones:
  - aprobar
  - rechazar

Al aprobar:

- la solicitud pasa a `APROBADA`
- el lote pasa a `RESERVADO`

Al rechazar:

- la solicitud pasa a `RECHAZADA`
- el lote vuelve a `LIBRE`

En tickets de atencion:

- el vendedor crea el ticket en `pendiente`
- administracion agenda desde `/inmopro/attention-tickets`
- el vendedor puede cancelar desde Cazador mientras no este `realizado` o `cancelado`

## Seeders relacionados
Para un entorno de prueba consistente, `DatabaseSeeder` ahora carga:

- teams
- niveles de asesor
- estados de lote
- estados de comision
- proyectos
- tipos de cliente
- ciudades
- vendedores con credenciales Cazador
- clientes vinculados a vendedor y ciudad
- lotes
- pre-reservas de ejemplo

## Verificacion recomendada
1. `php artisan migrate:fresh --seed`
2. `npm run build`
3. Probar login con `asesor1 / 123456`
4. Consultar `/api/v1/cazador/projects`
5. Crear un cliente propio
6. Crear un ticket de atencion
7. Registrar una pre-reserva con voucher
