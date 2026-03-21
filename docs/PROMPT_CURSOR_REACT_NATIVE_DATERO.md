# Prompt maestro para Cursor: aplicación React Native (API Datero)

**Uso:** copia este archivo completo (o por secciones) en el chat de Cursor al crear un **nuevo repositorio o carpeta** de app móvil para **dateros**. La fuente de verdad del contrato HTTP es [API_DATERO.md](./API_DATERO.md). El API del vendedor que administra dateros desde su móvil es [API_CAZADOR.md](./API_CAZADOR.md) (no lo uses en esta app salvo decisión explícita del producto).

**Ajustes del equipo:** los valores de stack (Expo, navegación, UI) son **recomendados por defecto**. Sustitúyelos al inicio del proyecto si el estándar interno es otro.

---

## 1. Meta-instrucciones para el agente (Cursor)

Eres un desarrollador senior en **React Native** y **TypeScript**. Debes:

- Implementar una app para **dateros** (captadores de clientes) que consume **únicamente** el **API REST Datero** documentado en `docs/API_DATERO.md`. **No inventes** rutas, parámetros ni códigos HTTP que no estén en esa documentación o en la tabla de la sección 4.
- Priorizar **HTTPS** en producción; **no** registrar el token en claro en consola en builds de producción ni en logs remotos sin redacción.
- Textos de interfaz y mensajes al usuario en **español**; nombres técnicos estándar en inglés (`token`, `Bearer`, `Authorization`) donde corresponda.
- Si falta información no cubierta por el API (p. ej. notificaciones push), deja **TODO** explícito y no asumas endpoints extra en el mismo backend sin confirmación.
- Respeta las **reglas de negocio** de la sección 5 (alcance de clientes por datero, tipo DATERO implícito, duplicados DNI/teléfono, rate limit de login, 401 si asesor/datero inactivo).

---

## 2. Contexto del producto

### 2.1 Usuario final

- Persona registrada como **Datero** en el CRM (panel Inmopro o alta por el vendedor vía API Cazador).
- Accede con **`username` + PIN de 6 dígitos** (no es el usuario/contraseña web del CRM).

### 2.2 Autenticación

- **No** es Sanctum de usuarios web.
- El login devuelve un **token opaco** (string largo) que se envía como `Authorization: Bearer <token>`.
- El logout **revoca** ese token en servidor; hay que borrarlo también en el dispositivo.

### 2.3 URL base del API

```
{{EXPO_PUBLIC_API_BASE_URL}}/api/v1/datero
```

- `EXPO_PUBLIC_API_BASE_URL` **sin** barra final.  
  Ejemplo con Laravel Herd: `https://crm-lotes.test` → prefijo de API: `https://crm-lotes.test/api/v1/datero`.

### 2.4 Relación con el vendedor

- Cada datero tiene un **vendedor responsable** (`advisor`). La app debe mostrar nombre y contacto del asesor (datos en login y/o `GET /me`) para que el datero sepa a quién se asignan los clientes captados.
- Los clientes creados desde esta app quedan en el CRM con tipo **DATERO** y `advisor_id` del vendedor responsable; el datero **no** elige otro vendedor desde el API.

### 2.5 Flujo de pantallas (orden lógico recomendado)

1. **Splash / comprobación de token** guardado → si válido, ir a home; si 401 al refrescar perfil, limpiar y login.
2. **Login** (`username`, `pin`, teclado numérico para PIN).
3. **Inicio (Home)** — resumen: nombre del datero, vendedor asignado (nombre, teléfono, equipo opcional), accesos a “Mis clientes”, “Perfil / seguridad”.
4. **Mis clientes** — lista con búsqueda local o debounce contra `GET /clients?search=`.
5. **Detalle de cliente** — `GET /clients/{id}`; mostrar datos básicos y sección **Lotes** (puede estar vacía).
6. **Nuevo cliente** — formulario alineado a validación del API; selector de ciudad con `GET /cities` (campo `city_id` opcional).
7. **Editar cliente** — `PUT /clients/{id}` con mismos campos que alta.
8. **Perfil** — lectura de `GET /me` (o datos cacheados del login); botón **Cambiar PIN** → `PUT /me/pin`.
9. **Cerrar sesión** — `POST /auth/logout` y borrado de token local.

**Fuera de alcance del API Datero (no implementar contra este backend sin otro contrato):** catálogo de lotes, pre-reservas, tickets de atención, recordatorios, edición del perfil del datero (nombre/email/teléfono) vía API.

---

## 3. Configuración del proyecto

### 3.1 Variables de entorno

| Variable | Descripción |
|----------|-------------|
| `EXPO_PUBLIC_API_BASE_URL` | Origen HTTPS del backend **sin** `/api` (ej. `https://crm-lotes.test`). |

Validar al arranque en release que exista y sea `https://`.

### 3.2 Cliente HTTP

- Timeout recomendado: **30 s**.
- Cabeceras por defecto: `Accept: application/json`.
- Peticiones con cuerpo JSON: `Content-Type: application/json`.
- Rutas protegidas: `Authorization: Bearer <token>`.

### 3.3 Almacenamiento del token

- Usar almacenamiento **seguro** (p. ej. `expo-secure-store` con Expo). Evitar `AsyncStorage` plano si la política de seguridad del equipo lo prohíbe.
- Tras **401** en cualquier llamada autenticada: eliminar token, limpiar estado de usuario y navegar a **Login**.

### 3.4 Rate limiting en login

- `POST auth/login` está limitado a **10 intentos por minuto por IP** (`datero-login`).
- Si la respuesta es **429**, mostrar mensaje claro (“Demasiados intentos, espera un minuto”) y no reintentar en bucle.

### 3.5 Manejo de errores JSON

- **422** credenciales login: cuerpo `{ "message": "Credenciales inválidas." }` (mostrar tal cual o mensaje equivalente).
- **422** PIN incorrecto en cambio de PIN: `{ "message": "El PIN actual no es válido." }`.
- **422** validación Laravel: objeto `errors` por campo; puede existir clave **`duplicate_registration`** en cliente (mensaje humano).
- **404** cliente: `{ "message": "Cliente no encontrado." }`.
- **401**: `{ "message": "No autenticado." }`.

---

## 4. Tabla maestra de endpoints

Prefijo: **`/api/v1/datero`** (rutas relativas a continuación).

| Método | Ruta | Auth | Query | Cuerpo |
|--------|------|------|-------|--------|
| POST | `auth/login` | No | — | JSON: `username`, `pin` (6 dígitos), `device_name` opcional |
| POST | `auth/logout` | Bearer | — | vacío |
| GET | `me` | Bearer | — | — |
| PUT | `me/pin` | Bearer | — | JSON: `current_pin`, `pin`, `pin_confirmation` (6 dígitos) |
| GET | `cities` | Bearer | `search` opcional | — |
| GET | `clients` | Bearer | `search` opcional | — |
| POST | `clients` | Bearer | — | JSON cliente (sección 6) |
| GET | `clients/{id}` | Bearer | — | — |
| PUT | `clients/{id}` | Bearer | — | JSON cliente (mismos campos que POST) |

---

## 5. Reglas de negocio obligatorias

### 5.1 Alcance de clientes

- Solo existen en la app los clientes devueltos por `GET /clients` (filtrados por el backend según `registered_by_datero_id`).
- No se puede ver ni editar un `id` de cliente ajeno: el API responde **404** `Cliente no encontrado.`

### 5.2 Alta de cliente

- El cliente se crea siempre como tipo **DATERO** y asignado al **vendedor del datero**; el cliente móvil **no** envía `client_type_id` ni `advisor_id`.
- Unicidad **global** en CRM: mismo **teléfono** (no vacío) o mismo **DNI** (no vacío) que otro cliente → **422** con error `duplicate_registration` (mostrar el mensaje devuelto).

### 5.3 Sesión e inactividad

- Si el administrador desactiva el datero o el asesor, el token deja de ser aceptado → **401**. La app debe volver a login.

### 5.4 PIN

- Entrada de PIN: exactamente **6 dígitos** en login y en cambio de PIN.
- Cambio de PIN requiere confirmación (`pin_confirmation` igual a `pin`).

---

## 6. Contratos JSON detallados

### 6.1 POST `auth/login` — respuesta exitosa

Campos principales:

- `token` (string): guardar de forma segura.
- `datero`: objeto con `id`, `name`, `phone`, `email`, `dni`, `username`, `is_active`, `last_login_at` (ISO 8601 o null), `city` (objeto o null).
- `advisor`: objeto con `id`, `name`, `phone`, `email`, `username`, `is_active`, `team` (opcional), `level` (opcional).

Tipar en TypeScript con interfaces que reflejen [API_DATERO.md](./API_DATERO.md).

### 6.2 GET `me`

- `data.datero` y `data.advisor` — misma forma conceptual que en login.

### 6.3 POST y PUT `clients`

**Campos del cuerpo:**

| Campo | Reglas |
|-------|--------|
| `name` | Requerido, string, max 255 |
| `phone` | Requerido, string, max 50 |
| `dni` | Opcional, string, max 20 |
| `email` | Opcional, email, max 255 |
| `referred_by` | Opcional, string, max 255 |
| `city_id` | Opcional, entero existente en ciudades |

**Respuesta de item en `data`:**

- `id`, `name`, `dni`, `phone`, `email`, `referred_by`, `city` (objeto con `id`, `name`, `department` o null), `lots` (array; vacío en listado y en POST/PUT; en GET detalle puede incluir objetos con `id`, `block`, `number`, `project`, `status`).

### 6.4 GET `cities`

- `data[]`: `id`, `name`, `department`, `code`.

---

## 7. UX y accesibilidad (recomendaciones)

- Login: campo PIN con **ocultación** (secure text) y teclado numérico.
- Formularios: validación en cliente alineada a reglas del API antes de enviar (reduce frustración).
- Lista de clientes: estado vacío amigable (“Aún no registraste clientes”) y pull-to-refresh llamando a `GET /clients`.
- Errores de red: mensaje distinguishable de errores 422/401.
- Mostrar claramente **quién es el vendedor responsable** en Home para evitar confusiones operativas.

---

## 8. Estructura de carpetas sugerida (Expo + TypeScript)

Ajusta nombres al estándar del equipo; ejemplo:

```
src/
  api/
    client.ts          # fetch/axios con baseURL + interceptores 401
    dateroApi.ts       # funciones por endpoint (login, me, clients, cities...)
  auth/
    AuthContext.tsx    # token, usuario, login/logout
  types/
    api.ts             # interfaces Datero, Advisor, Client, City
  screens/
    LoginScreen.tsx
    HomeScreen.tsx
    ClientsListScreen.tsx
    ClientDetailScreen.tsx
    ClientFormScreen.tsx
    ChangePinScreen.tsx
    ProfileScreen.tsx
  components/
  navigation/
```

---

## 9. Checklist de implementación

- [ ] Variables de entorno y cliente HTTP con `Accept: application/json`.
- [ ] Login, persistencia segura de token, logout que llama al API y borra local.
- [ ] Interceptor o wrapper que ante **401** limpie sesión y redirija a login.
- [ ] Home con datos de `datero` y `advisor` (desde login o `GET /me`).
- [ ] Lista y búsqueda de clientes (`search` en query).
- [ ] Detalle con lotes (lista vacía posible).
- [ ] Alta y edición con validación y manejo de `duplicate_registration`.
- [ ] Selector de ciudades con `GET /cities`.
- [ ] Cambio de PIN con tres campos y manejo de error de PIN actual.
- [ ] Manejo de **429** en login.
- [ ] (Opcional) Pruebas E2E o de contrato con respuestas mockeadas según `API_DATERO.md`.

---

## 10. Criterios de aceptación (definición de hecho)

La app está lista para revisión cuando:

1. Un datero de prueba puede iniciar sesión, ver su vendedor, listar solo sus clientes, crear uno nuevo y verlo en la lista.
2. Intentar abrir/editar un `client` id inventado o ajeno muestra el error amigable acorde a **404**.
3. Registrar un teléfono ya usado en el CRM muestra el mensaje del servidor (**422** / `duplicate_registration`).
4. Cambiar PIN con PIN actual incorrecto muestra el mensaje del servidor.
5. Cerrar sesión invalida el token en servidor y no permite seguir llamando rutas protegidas sin nuevo login.

---

## 11. Referencia rápida de mensajes del backend

| Situación | HTTP | `message` típico |
|-----------|------|-------------------|
| Token ausente o inválido | 401 | `No autenticado.` |
| Login fallido | 422 | `Credenciales inválidas.` |
| PIN actual incorrecto | 422 | `El PIN actual no es válido.` |
| Cliente no del datero | 404 | `Cliente no encontrado.` |
| Logout OK | 200 | `Sesión cerrada correctamente.` |
| PIN actualizado | 200 | `PIN actualizado correctamente.` |
| Cliente creado | 201 | `Cliente registrado.` |
| Cliente actualizado | 200 | `Cliente actualizado.` |

Para textos largos de validación o duplicados, usar `errors` de Laravel o el mensaje en `duplicate_registration` tal como devuelve el API.
