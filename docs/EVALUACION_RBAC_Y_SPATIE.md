# Evaluación RBAC y Spatie Permission (Inmopro)

Este documento describe el **modelo de autorización del panel web Inmopro** tras adoptar **`spatie/laravel-permission`**, mantiene el **inventario de rutas** como referencia y deja claro qué **no** cambia en las **APIs móviles**.

Documentación de procesos relacionada: [GRAFICO_PROCESOS_SISTEMA.md](./GRAFICO_PROCESOS_SISTEMA.md).

---

## 0. Implementación vigente (resumen)

- **Paquete:** `spatie/laravel-permission`, guard por defecto `web` (sesión Fortify/Inertia).
- **Convención:** el **nombre del permiso en BD coincide con el nombre de ruta** Laravel para rutas cuyo `name` empieza por `inmopro.` (p. ej. `inmopro.clients.index`). Así, `$user->can('inmopro.clients.index')` y el middleware pueden usar el nombre de ruta actual.
- **Excepción — control de acceso:** rutas con prefijo `inmopro.access-control.*` tienen permisos propios con el mismo prefijo; no se exige que el usuario tenga un permiso llamado exactamente como cada ruta de ese submódulo de forma circular. Esas rutas están protegidas con el middleware `rbac.super-admin` (solo rol `super-admin`).
- **Middleware Inmopro:** alias `inmopro.permission` → `EnsureInmoproRoutePermission`: para rutas `inmopro.*` no exentas, `abort_unless($request->user()?->can($routeName), 403)`.
- **Rol `super-admin`:** `Gate::before` en `AppServiceProvider` devuelve `true` para cualquier ability si el usuario tiene ese rol (operación y CRUD de acceso sin bloqueo).
- **Sincronización:** `php artisan inmopro:sync-permissions` recorre rutas nombradas `inmopro.*` y ejecuta `Permission::findOrCreate($name, 'web')`. `AuthorizationSeeder` solo sincroniza permisos y el rol `super-admin`; **no asigna** ese rol por `.env`. En `local`/`testing`, `DatabaseSeeder` puede asignar `super-admin` al usuario de desarrollo creado allí; en producción la asignación es manual (p. ej. Tinker) u otros seeders explícitos (p. ej. QA funcional).
- **Transferencias de lote:** sustituido el permiso histórico `confirm-lot-transfer` por permisos de ruta reales (formulario: `inmopro.lots.transfer-confirmation` / `.store`; bandeja: `inmopro.lot-transfer-confirmations.*`; aprobar/rechazar: `.approve` / `.reject`).
- **Frontend:** `HandleInertiaRequests` expone `auth.user.permissions` (nombres de permiso) y `auth.user.roles` (nombres de rol). El menú “Control de acceso” aparece si el usuario tiene rol `super-admin`.
- **APIs (`routes/api.php`):** **sin capa Spatie**; Cazador y Datero siguen con tokens y reglas en controladores (ver §4).

---

## 1. Arquitectura actual (código real)

### 1.1 Modelo de datos (Spatie)

- Tablas del paquete: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` (migración publicada junto a Spatie).
- Las tablas legacy `roles` / `permissions` / pivotes antiguos fueron eliminadas por una migración compensatoria tras el cambio de esquema.
- **Usuario:** `App\Models\User` usa el trait `HasRoles` de Spatie. Método auxiliar `permissionNamesForFrontend()` para la lista enviada a Inertia.

### 1.2 Integración con Laravel Gate

Spatie registra abilities por nombre de permiso. El `Gate::before` solo añade el atajo para rol `super-admin`.

### 1.3 Propagación al frontend (Inertia)

`HandleInertiaRequests` comparte `auth.user.permissions` como array de **nombres** de permiso y `auth.user.roles` como nombres de rol. Cualquier comprobación en React debe duplicarse en servidor (middleware / Form Requests / policies).

### 1.4 Módulo Control de acceso (CRUD)

- Rutas bajo prefijo URI `/inmopro/access-control` con nombres `inmopro.access-control.*` (roles, permisos, asignación rol↔permiso, usuarios con roles).
- Páginas Inertia en `resources/js/pages/inmopro/access-control/`.
- Acceso restringido a **super-admin** vía middleware `rbac.super-admin`.

### 1.5 APIs móviles (Cazador y Datero)

- Autenticación por **token** y middleware `advisor.api` / `datero.api`.
- **No** usan el modelo `User` del panel web ni la tabla Spatie del guard `web` para autorización de esas rutas.
- La autorización es “¿token válido + reglas de negocio en controladores?” (p. ej. cliente PROPIO del asesor, clientes captados por el datero).

Los permisos Spatie del CRM web **no sustituyen** automáticamente estas reglas; son capas distintas.

### 1.6 Operación y riesgos a vigilar

- Asignar el rol `super-admin` en producción de forma explícita a quien corresponda (no hay lista en `.env`). Tras desplegar, ejecutar `inmopro:sync-permissions` cuando haya rutas nuevas y revisar roles en BD.
- Nuevas rutas Inmopro: ejecutar `inmopro:sync-permissions` y asignar permisos a los roles que correspondan.
- No confiar solo en el cliente para ocultar acciones: el middleware `inmopro.permission` aplica en servidor.

---

## 2. Convención adoptada para nombres de permiso

Formato: **igual al `name` de la ruta** cuando la ruta es `inmopro.*` (salvo el submódulo `inmopro.access-control.*`, donde los permisos siguen el mismo prefijo pero la autorización del CRUD va por super-admin).

Ejemplos:

- `inmopro.lot-transfer-confirmations.approve`
- `inmopro.clients.import-from-excel`
- `inmopro.reports.pdf`
- `inmopro.branding.update`

Ventajas: trazabilidad con `route('inmopro....')`, seeders idempotentes y comprobaciones `$user->can($routeName)` en middleware.

---

## 3. Inventario: rutas web Inmopro (`name` → candidato permiso)

Todas comparten middleware `auth`, `verified` y prefijo URI `/inmopro`. La lista siguiente agrupa por **módulo funcional**; cada fila es una **acción** que puede mapearse 1:1 a un permiso o agruparse (p. ej. un solo `inmopro.clients.manage` para CRUD).

### 3.1 General y dashboard

| Nombre de ruta | Verbos | Notas |
|----------------|--------|--------|
| `inmopro.dashboard` | GET | Entrada al panel. |

### 3.2 Proyectos e importación

| Nombre de ruta | Verbos |
|----------------|--------|
| `inmopro.projects.index`, `.create`, `.store`, `.show`, `.edit`, `.update`, `.destroy` | GET, POST, PUT/PATCH, DELETE |
| `inmopro.projects.excel-template` | GET |
| `inmopro.projects.import-from-excel` | POST |

### 3.3 Lotes e inventario

| Nombre de ruta | Verbos |
|----------------|--------|
| `inmopro.lots.index`, `.create`, `.store`, `.show`, `.edit`, `.update`, `.destroy` | REST |
| `inmopro.lots.export-pdf` | GET |
| `inmopro.lots.ai-follow-up-suggestion` | POST (throttle `ai`) |

### 3.4 Clientes

| Nombre de ruta | Verbos |
|----------------|--------|
| `inmopro.clients.index`, `.create`, `.store`, `.show`, `.edit`, `.update`, `.destroy` | REST |
| `inmopro.clients.search` | GET |
| `inmopro.clients.export-excel` | GET |
| `inmopro.clients.import-from-excel` | POST |

### 3.5 Catálogos y maestros

Incluye recursos completos (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) para:

- `inmopro.client-types.*`
- `inmopro.cities.*`
- `inmopro.lot-statuses.*`
- `inmopro.commission-statuses.*`
- `inmopro.advisor-levels.*`
- `inmopro.teams.*`
- `inmopro.membership-types.*` más `bulk-assign` y `bulk-assign.store`
- `inmopro.dateros.*`

### 3.6 Asesores y membresías

| Prefijo | Extras |
|---------|--------|
| `inmopro.advisors.*` | `.search`, `.cazador-access.update` |
| `inmopro.advisor-memberships.*` | `.payments.store` |

### 3.7 Operación: pre-reservas y transferencias

| Nombre de ruta | Verbos |
|----------------|--------|
| `inmopro.lot-pre-reservations.index`, `.store`, `.approve`, `.reject` | GET, POST |
| `inmopro.lot-transfer-confirmations.index`, `.approve`, `.reject` | GET, POST |
| `inmopro.lots.transfer-confirmation`, `.transfer-confirmation.store` | GET, POST |

_Transferencias: permisos = nombres de ruta (`inmopro.lots.transfer-confirmation`, `inmopro.lot-transfer-confirmations.*`)._

### 3.8 Tickets de atención

| Nombre de ruta | Verbos |
|----------------|--------|
| `inmopro.attention-tickets.*` | REST |
| `inmopro.attention-tickets.calendar` | GET |
| `inmopro.attention-tickets.delivery-deed` | GET |
| `inmopro.attention-tickets.delivery-deed.mark-signed` | POST |

### 3.9 Finanzas

| Nombre de ruta | Verbos |
|----------------|--------|
| `inmopro.financial.index` | GET |
| `inmopro.accounts-receivable.index` | GET |
| `inmopro.lots.installments.store`, `inmopro.lots.payments.store` | POST |
| `inmopro.cash-accounts.index`, `.store`, `.entries.store` | GET, POST |
| `inmopro.commissions.index`, `.mark-as-paid` | GET, POST |

### 3.10 Reportes y configuración

| Nombre de ruta | Verbos |
|----------------|--------|
| `inmopro.reports.index`, `.pdf`, `.csv` | GET |
| `inmopro.report-settings.edit`, `.update` | GET, PUT |

### 3.11 Agenda y recordatorios (backoffice)

| Nombre de ruta | Verbos |
|----------------|--------|
| `inmopro.agenda.index` | GET |
| `inmopro.advisor-agenda-events.store`, `.update`, `.destroy` | POST, PUT, DELETE |
| `inmopro.advisor-reminders.store`, `.update`, `.destroy`, `.complete` | POST, PUT, DELETE |

### 3.12 Sistema y documentación

| Nombre de ruta | Verbos |
|----------------|--------|
| `inmopro.branding.edit`, `.update` | GET, PUT |
| `inmopro.process-diagrams.index` | GET |

_Lista exhaustiva generada a partir de `php artisan route:list --name=inmopro` (orden alfabético en consola). El número de entradas es elevado: conviene automatizar la generación de permisos desde esta salida en un comando Artisan futuro._

---

## 4. Inventario: APIs (`routes/api.php`)

### 4.1 API Cazador (`/api/v1/cazador`)

Autenticación: login público; resto bajo `advisor.api`.

Candidatos a “permisos” del lado API (si en el futuro hubiera roles de asesor en BD):

- `auth.logout`, `me.show`, `me.update`, `me.pin.update`
- `dashboard.show`, `cities.index`
- `dateros.index`, `dateros.store`, `dateros.update`
- `clients.*`, `attention-tickets.*`, `reminders.*`
- `projects.*`, `my-lots.index`, `lots.index`, `lots.show`, `lots.pre-reservations.store`

Hoy la autorización es **por ownership** en controladores, no por tabla `permissions`.

### 4.2 API Datero (`/api/v1/datero`)

- `auth.login`, `auth.logout`, `me.show`, `me.pin.update`, `cities.index`, `clients.*`

Misma observación: tokens de `dateros`, no usuarios web.

---

## 5. Inventario: menú lateral (frontend)

Archivo: `resources/js/components/app-sidebar.tsx`.

Cada `href` bajo secciones Inventario, Ventas, Comercial, Operación, Documentación y Sistema debería alinearse con un permiso mínimo de **lectura** para mostrar el ítem, y acciones destructivas o financieras con permisos adicionales.

| Sección | Ítems (href) |
|---------|----------------|
| Principal | Dashboard (`dashboard()` Wayfinder) |
| Inventario | `/inmopro/lots`, `/inmopro/projects` |
| Ventas | financial, accounts-receivable, commissions, reports, report-settings |
| Comercial | agenda, clients, client-types, cities, advisors, dateros, membership-types, teams, advisor-levels |
| Operación | attention-tickets, lot-pre-reservations, lot-transfer-confirmations, cash-accounts, lot-statuses, commission-statuses |
| Documentación | `/inmopro/process-diagrams` |
| Sistema | `/inmopro/branding` |

_Notar: el dashboard principal de la app puede ser distinto del `inmopro.dashboard`; revisar qué layout usa cada página al aplicar permisos._

---

## 6. Decisión de implementación

Se adoptó **`spatie/laravel-permission`** con permisos alineados a nombres de ruta `inmopro.*`, middleware centralizado en rutas web Inmopro, CRUD de control de acceso para super-admin, y **sin** aplicar Spatie a `routes/api.php`.

---

## 7. Próximos pasos operativos (mantenimiento)

1. Tras añadir rutas Inmopro nuevas: `php artisan inmopro:sync-permissions` y asignar permisos a roles según negocio.
2. Definir roles operativos (lectura, cobranza, etc.) y asignaciones en seeders o desde el CRUD.
3. Filtrar ítems del menú lateral con `auth.user.permissions` además del rol super-admin donde aplique.
4. **APIs:** si en el futuro hubiera roles de asesor en BD, modelar aparte; no mezclar tokens con sesión web sin diseño claro.

---

## 8. Checklist post-implementación

- [x] Migración desde tablas RBAC legacy a tablas Spatie.
- [x] `AuthorizationSeeder` + comando `inmopro:sync-permissions` con `Permission::findOrCreate`.
- [x] Tests de autorización (middleware, transferencias, access-control).
- [x] [GRAFICO_PROCESOS_SISTEMA.md](./GRAFICO_PROCESOS_SISTEMA.md) actualizado (diagrama de transferencias).

---

## 9. Referencias de código

| Pieza | Ubicación |
|-------|-----------|
| Rutas Inmopro | `routes/inmopro.php` |
| Rutas API | `routes/api.php` |
| Middleware permiso por ruta | `app/Http/Middleware/EnsureInmoproRoutePermission.php` |
| Middleware super-admin | `app/Http/Middleware/EnsureUserIsSuperAdmin.php` |
| Registro de alias middleware | `bootstrap/app.php` |
| Gate super-admin | `app/Providers/AppServiceProvider.php` |
| Usuario (`HasRoles`) | `app/Models/User.php` |
| Permisos / roles en Inertia | `app/Http/Middleware/HandleInertiaRequests.php` |
| Sincronización de permisos | `app/Support/InmoproPermissionSynchronizer.php`, `app/Console/Commands/SyncInmoproPermissionsCommand.php` |
| Seeder autorización | `database/seeders/AuthorizationSeeder.php` |
| Seeder base (usuario dev + rol en local/testing) | `database/seeders/DatabaseSeeder.php` |
| Menú y control de acceso | `resources/js/components/app-sidebar.tsx` |
| UI control de acceso | `resources/js/pages/inmopro/access-control/` |

---

*Última revisión: implementación Spatie en panel Inmopro; APIs sin cambios de autorización Spatie.*
