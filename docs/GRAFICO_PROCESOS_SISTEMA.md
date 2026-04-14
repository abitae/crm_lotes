# Gráfico de procesos del sistema CRM Lotes Markdow

Documento de referencia con diagramas Mermaid del sistema **CRM Lotes**, alineado con **API Cazador**, **API Datero** e **Inmopro** (panel web).

**Referencias cruzadas**

- Flujo de negocio (CRM → venta → postventa, diagrama único): [flujo_crm_venta_postventa.md](./flujo_crm_venta_postventa.md)
- Contrato API asesor: [API_CAZADOR.md](./API_CAZADOR.md) · Análisis: [ANALISIS_API_CAZADOR.md](./ANALISIS_API_CAZADOR.md)
- Contrato API datero: [API_DATERO.md](./API_DATERO.md)
- Evaluación de roles/permisos y Spatie: [EVALUACION_RBAC_Y_SPATIE.md](./EVALUACION_RBAC_Y_SPATIE.md)

---

## Índice

1. [Arquitectura general](#1-arquitectura-general-actores-canales-y-almacenamiento)
2. [Recorrido operativo del asesor en API Cazador](#2-recorrido-operativo-del-asesor-en-api-cazador)
3. [Flujo detallado de pre-reserva desde la app](#3-flujo-detallado-de-pre-reserva-desde-la-app)
4. [Flujo de transferencia con aprobación posterior](#4-flujo-de-transferencia-con-aprobación-posterior)
5. [Doble estado del lote y de la revisión de transferencia](#5-doble-estado-del-lote-y-de-la-revisión-de-transferencia)
6. [Mapa de estados del lote](#6-mapa-de-estados-del-lote)
7. [Cobranza y cuentas por cobrar por lote](#7-cobranza-y-cuentas-por-cobrar-por-lote)
8. [Flujo de comisiones](#8-flujo-de-comisiones)
9. [Ciclo de clientes en backoffice y Excel](#9-ciclo-de-clientes-en-backoffice-y-excel)
10. [Flujo de membresías de asesores](#10-flujo-de-membresías-de-asesores)
11. [Recordatorios en API Cazador (cliente PROPIO)](#11-recordatorios-en-api-cazador-cliente-propio)
12. [Tickets de atención y firma de escritura](#12-tickets-de-atención-y-firma-de-escritura)
13. [Caja, bancos y movimientos operativos](#13-caja-bancos-y-movimientos-operativos)
14. [Vista ejecutiva de módulos Inmopro](#14-vista-ejecutiva-de-módulos-inmopro)
15. [Canal API Datero y frontera con Inmopro y Cazador](#15-canal-api-datero-y-frontera-con-inmopro-y-cazador)
16. [Agenda y recordatorios en Inmopro](#16-agenda-y-recordatorios-en-inmopro)
17. [Importación de proyectos desde Excel](#17-importación-de-proyectos-desde-excel)
18. [Configuración de reportes y salidas](#18-configuración-de-reportes-y-salidas)
19. [Sugerencia de seguimiento con IA (lote)](#19-sugerencia-de-seguimiento-con-ia-lote)
20. [Resumen de rutas por proceso](#20-resumen-de-rutas-por-proceso)

---

## 1. Arquitectura general: actores, canales y almacenamiento

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#ecfdf5','primaryTextColor':'#0f172a','primaryBorderColor':'#059669','lineColor':'#64748b','secondaryColor':'#f1f5f9','tertiaryColor':'#ffffff','fontFamily':'ui-sans-serif, system-ui'}}}%%
flowchart LR
    subgraph mobileCazador [Canal_movil_Cazador]
        advisorApp["App Cazador"]
        seller["Asesor"]
    end

    subgraph mobileDatero [Canal_movil_Datero]
        dateroApp["App Datero"]
        captador["Datero captador"]
    end

    subgraph web [Canal_web]
        browser["Navegador"]
        operator["Operador CRM"]
    end

    subgraph core [CrmLotes_backend]
        apiCaz["API Cazador\n/api/v1/cazador"]
        apiDate["API Datero\n/api/v1/datero"]
        inmopro["Inmopro\n/inmopro/*"]
        storage["Storage publico"]
        db["Base de datos"]
    end

    seller --> advisorApp
    advisorApp -->|"token Bearer, Sanctum asesor"| apiCaz
    captador --> dateroApp
    dateroApp -->|"token Bearer datero"| apiDate
    operator --> browser
    browser -->|"session usuario web"| inmopro
    apiCaz --> db
    apiDate --> db
    inmopro --> db
    apiCaz --> storage
    apiDate --> storage
    inmopro --> storage
```

---

## 2. Recorrido operativo del asesor en API Cazador

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#ecfdf5','primaryTextColor':'#0f172a','primaryBorderColor':'#059669','lineColor':'#64748b','secondaryColor':'#f1f5f9','tertiaryColor':'#ffffff'}}}%%
flowchart TB
    startNode([Inicio]) --> login["POST /auth/login"]
    login --> tokenCheck{"Token valido?"}
    tokenCheck -->|"No"| login
    tokenCheck -->|"Si"| profile["GET/PUT /me\nPUT /me/pin"]
    profile --> ownClients["Clientes propios\n(tipo PROPIO)"]
    ownClients --> reminders["Recordatorios\nsolo cliente PROPIO"]
    ownClients --> tickets["Tickets de atencion\ncliente PROPIO"]
    reminders --> projects["GET /projects"]
    tickets --> projects
    projects --> lots["GET /lots, /lots/{id}"]
    lots --> canReserve{"Lote LIBRE?"}
    canReserve -->|"No"| lots
    canReserve -->|"Si"| prereq["POST pre-reserva\nvoucher y monto"]
    prereq --> waitAdmin([Revision backoffice])
```

---

## 3. Flujo detallado de pre-reserva desde la app

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#ecfdf5','primaryTextColor':'#0f172a','primaryBorderColor':'#059669','lineColor':'#64748b','actorBkg':'#f8fafc','actorBorder':'#334155'}}}%%
sequenceDiagram
    participant seller as Asesor
    participant api as API_Cazador
    participant db as BaseDeDatos
    participant storage as Storage
    participant web as Inmopro
    participant admin as Administrador

    seller->>api: POST /lots/{lot}/pre-reservations
    api->>api: Validar token y reglas
    api->>api: Cliente propio, lote LIBRE
    api->>storage: Guardar voucher
    api->>db: Pre-reserva PENDIENTE, lote PRERESERVA
    api->>seller: 201 Created

    admin->>web: /inmopro/lot-pre-reservations
    web->>db: Listar y filtrar
    admin->>web: Aprobar o rechazar
    alt aprobacion
        web->>db: Pre-reserva APROBADA, lote RESERVADO
    else rechazo
        web->>db: Pre-reserva RECHAZADA, lote LIBRE
    end
```

---

## 4. Flujo de transferencia con aprobación posterior

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#ecfdf5','primaryTextColor':'#0f172a','primaryBorderColor':'#059669','lineColor':'#64748b','actorBkg':'#f8fafc','actorBorder':'#334155'}}}%%
sequenceDiagram
    participant user as Usuario_con_permiso
    participant web as Inmopro
    participant db as BaseDeDatos
    participant storage as Storage
    participant reviewer as Revisor

    user->>web: Lote RESERVADO, confirmar transferencia
    web->>web: Permisos Spatie (rutas inmopro.lots.transfer-confirmation*)
    web->>web: Sin revision PENDIENTE duplicada
    user->>web: Subir evidencia
    web->>storage: Imagen evidencia
    web->>db: lot_transfer_confirmation PENDIENTE, lote TRANSFERIDO
    reviewer->>web: /inmopro/lot-transfer-confirmations
    reviewer->>web: Aprobar o rechazar
    alt aprobacion
        web->>db: Revision APROBADA, comisiones si aplica
    else rechazo
        web->>db: Revision RECHAZADA, lote RESERVADO
    end
```

---

## 5. Doble estado del lote y de la revisión de transferencia

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#ecfdf5','primaryTextColor':'#0f172a','primaryBorderColor':'#059669','lineColor':'#64748b','secondaryColor':'#f1f5f9','tertiaryColor':'#ffffff'}}}%%
flowchart TB
    libre["Lote LIBRE"] --> prere["Lote PRERESERVA"]
    prere -->|"Aprobado"| reservado["Lote RESERVADO"]
    prere -->|"Rechazado"| libre

    reservado -->|"Evidencia"| transferPending["TRANSFERIDO\nRevision PENDIENTE"]
    transferPending -->|"Aprobar"| transferApproved["TRANSFERIDO\nRevision APROBADA"]
    transferPending -->|"Rechazar"| transferRejected["RESERVADO\nRevision RECHAZADA"]
    reservado -->|"Cronograma"| cuotas["Lote CUOTAS"]

    transferRejected -->|"Nueva evidencia"| transferPending
```

---

## 6. Mapa de estados del lote

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#ecfdf5','primaryTextColor':'#0f172a','primaryBorderColor':'#059669','lineColor':'#64748b'}}}%%
stateDiagram-v2
    [*] --> LIBRE
    LIBRE --> PRERESERVA: API pre-reserva
    PRERESERVA --> LIBRE: Backoffice rechaza
    PRERESERVA --> RESERVADO: Backoffice aprueba
    RESERVADO --> TRANSFERIDO: Registro transferencia
    RESERVADO --> CUOTAS: Cuotas o financiamiento
    TRANSFERIDO --> RESERVADO: Revision rechazada
    CUOTAS --> [*]
    TRANSFERIDO --> [*]
```

_Códigos de sistema alineados con el modelo `LotStatus`: LIBRE, PRERESERVA, RESERVADO, TRANSFERIDO, CUOTAS._

---

## 7. Cobranza y cuentas por cobrar por lote

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#ecfdf5','primaryTextColor':'#0f172a','primaryBorderColor':'#059669','lineColor':'#64748b','secondaryColor':'#f1f5f9','tertiaryColor':'#ffffff'}}}%%
flowchart TB
    activeLot["Lote RESERVADO / TRANSFERIDO / CUOTAS"] --> receivableIndex["/inmopro/accounts-receivable"]
    receivableIndex --> filters["Filtros proyecto, estado, busqueda"]
    filters --> detail["Cuotas y pagos del lote"]
    detail --> createInstallment["POST .../installments"]
    detail --> createPayment["POST .../payments"]
    createInstallment --> installmentStatus["Cuota PENDIENTE / PAGADA / VENCIDA"]
    createPayment --> paymentLink["Caja y forma de pago"]
    paymentLink --> paymentTotals["Totales y saldo"]
    paymentTotals --> summary["Cartera agregada"]
```

---

## 8. Flujo de comisiones

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#ecfdf5','primaryTextColor':'#0f172a','primaryBorderColor':'#059669','lineColor':'#64748b','secondaryColor':'#f1f5f9','tertiaryColor':'#ffffff'}}}%%
flowchart LR
    approval["Transferencia APROBADA"] --> validateAdvisor{"Lote con asesor?"}
    validateAdvisor -->|"No"| stopNode["Sin comision"]
    validateAdvisor -->|"Si"| readLevel["Nivel del asesor"]
    readLevel --> direct["Comision DIRECTA"]
    readLevel --> pyramidCheck{"Superior en piramide?"}
    pyramidCheck -->|"Si"| pyramid["Comision PIRAMIDAL"]
    pyramidCheck -->|"No"| pendingOnly["Solo directa"]
    direct --> pendingStatus["PENDIENTE"]
    pyramid --> pendingStatus
    pendingOnly --> pendingStatus
    pendingStatus --> payFlow["POST .../mark-as-paid"]
```

---

## 9. Ciclo de clientes en backoffice y Excel

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#ecfdf5','primaryTextColor':'#0f172a','primaryBorderColor':'#059669','lineColor':'#64748b','secondaryColor':'#f1f5f9','tertiaryColor':'#ffffff'}}}%%
flowchart TB
    clientIndex["/inmopro/clients"] --> clientFilters["Busqueda nombre, DNI, telefono"]
    clientIndex --> dimensionFilters["Tipo, ciudad, asesor"]
    clientIndex --> exportExcel["GET .../export-excel"]
    clientIndex --> importExcel["POST .../import-from-excel"]
    importExcel --> validateExcel["Validar xlsx/xls"]
    validateExcel --> mapFields["Mapeo columnas"]
    mapFields --> upsertClient["Upsert por DNI"]
    upsertClient --> clientList["Directorio actualizado"]
    clientList --> clientShow["Ficha y lotes"]
```

---

## 10. Flujo de membresías de asesores

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#ecfdf5','primaryTextColor':'#0f172a','primaryBorderColor':'#059669','lineColor':'#64748b','secondaryColor':'#f1f5f9','tertiaryColor':'#ffffff'}}}%%
flowchart TB
    configTypes["Tipos de membresia\n/inmopro/membership-types"] --> advisors["Asesores"]
    advisors --> assignMembership["Alta advisor_membership"]
    assignMembership --> membershipRecord["Vigencia y monto"]
    membershipRecord --> payments["POST .../payments"]
    payments --> receivables["Cartera membresia"]
    receivables --> reviewAdvisor["Estado y vencimientos"]
```

---

## 11. Recordatorios en API Cazador (cliente PROPIO)

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#ecfdf5','primaryTextColor':'#0f172a','primaryBorderColor':'#059669','lineColor':'#64748b','actorBkg':'#f8fafc','actorBorder':'#334155'}}}%%
sequenceDiagram
    participant seller as Asesor
    participant api as API_Cazador
    participant db as BaseDeDatos

    seller->>api: GET /reminders
    api->>db: Filtrar por asesor y cliente PROPIO
    api-->>seller: Lista

    seller->>api: POST /reminders
    api->>db: Validar cliente PROPIO del asesor
    alt valido
        api-->>seller: 201
    else invalido
        api-->>seller: 422
    end
```

---

## 12. Tickets de atención y firma de escritura

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#ecfdf5','primaryTextColor':'#0f172a','primaryBorderColor':'#059669','lineColor':'#64748b','actorBkg':'#f8fafc','actorBorder':'#334155'}}}%%
sequenceDiagram
    participant seller as Asesor
    participant api as API_Cazador
    participant db as BaseDeDatos
    participant web as Inmopro
    participant staff as Operador

    seller->>api: POST /attention-tickets
    api->>db: Ticket creado
    staff->>web: Listado y calendario
    web->>db: CRUD tickets
    staff->>web: Delivery deed PDF
    staff->>web: Marcar escritura firmada
    web->>db: Persistir estado firma
```

---

## 13. Caja, bancos y movimientos operativos

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#ecfdf5','primaryTextColor':'#0f172a','primaryBorderColor':'#059669','lineColor':'#64748b','secondaryColor':'#f1f5f9','tertiaryColor':'#ffffff'}}}%%
flowchart LR
    cashAccounts["/inmopro/cash-accounts"] --> registerEntry["Ingreso o egreso"]
    registerEntry --> ledger["Movimientos"]
    payments["Pagos lote o membresia"] --> linkCash["Cuenta de caja"]
    linkCash --> ledger
    ledger --> balances["Saldos"]
    balances --> finance["/inmopro/financial"]
```

---

## 14. Vista ejecutiva de módulos Inmopro

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#ecfdf5','primaryTextColor':'#0f172a','primaryBorderColor':'#059669','lineColor':'#64748b','secondaryColor':'#f1f5f9','tertiaryColor':'#ffffff'}}}%%
flowchart TB
    dashboard["Dashboard"] --> masters["Maestros"]
    dashboard --> sales["Ventas y cartera"]
    dashboard --> operations["Operacion"]
    dashboard --> finance["Finanzas"]
    dashboard --> reports["Reportes y docs"]
    dashboard --> systemArea["Sistema"]

    masters --> projects["Proyectos y lotes"]
    masters --> catalogs["Tipos cliente, ciudades,\ndateros, estados, niveles, teams"]
    sales --> clients["Clientes"]
    sales --> advisors["Asesores y membresias"]
    sales --> agendaLink["Agenda"]
    operations --> prereTab["Pre-reservas"]
    operations --> transferTab["Transferencias"]
    operations --> tickets["Tickets"]
    finance --> receivableTab["Cuentas por cobrar"]
    finance --> cashTab["Caja y bancos"]
    finance --> commissions["Comisiones"]
    reports --> pdfReports["PDF y CSV"]
    reports --> metaReports["Meta reportes"]
    reports --> processDocs["Diagramas de procesos"]
    systemArea --> branding["Personalizacion / branding"]
```

---

## 15. Canal API Datero y frontera con Inmopro y Cazador

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#eff6ff','primaryTextColor':'#0f172a','primaryBorderColor':'#2563eb','lineColor':'#64748b','secondaryColor':'#f1f5f9','tertiaryColor':'#ffffff'}}}%%
flowchart TB
    subgraph alta [Alta_y_gestion_cuentas]
        inmoproCR["Inmopro /inmopro/dateros"]
        cazadorAPI["Cazador GET/POST/PUT /dateros"]
    end

    subgraph runtime [App_Datero_movil]
        loginD["POST /api/v1/datero/auth/login"]
        meD["GET /me, PUT /me/pin"]
        clientsD["CRUD /clients scope datero"]
    end

    subgraph rules [Reglas_servidor]
        tokenD["Token opaco + hash SHA-256"]
        scope["Solo clientes con registered_by_datero_id"]
        assign["Cliente tipo DATERO, advisor_id del datero"]
    end

    inmoproCR --> dbD[(dateros)]
    cazadorAPI --> dbD
    loginD --> tokenD
    clientsD --> scope
    clientsD --> assign
    tokenD --> dbD
```

_Detalle de endpoints y errores: [API_DATERO.md](./API_DATERO.md)._

---

## 16. Agenda y recordatorios en Inmopro

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#ecfdf5','primaryTextColor':'#0f172a','primaryBorderColor':'#059669','lineColor':'#64748b','secondaryColor':'#f1f5f9','tertiaryColor':'#ffffff'}}}%%
flowchart LR
    agenda["GET /inmopro/agenda"] --> events["advisor-agenda-events\nPOST PUT DELETE"]
    agenda --> reminders["advisor-reminders\nPOST PUT DELETE complete"]
    events --> cal["Vista calendario / lista"]
    reminders --> follow["Seguimiento por asesor"]
```

---

## 17. Importación de proyectos desde Excel

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#ecfdf5','primaryTextColor':'#0f172a','primaryBorderColor':'#059669','lineColor':'#64748b','secondaryColor':'#f1f5f9','tertiaryColor':'#ffffff'}}}%%
flowchart TB
    template["GET /inmopro/projects/excel-template"] --> fill["Operador completa plantilla"]
    fill --> upload["POST /inmopro/projects/import-from-excel"]
    upload --> validate["Validar filas y proyecto"]
    validate --> lotsGen["Crear o actualizar lotes"]
    lotsGen --> inventory["Inventario /inmopro/lots"]
```

---

## 18. Configuración de reportes y salidas

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#ecfdf5','primaryTextColor':'#0f172a','primaryBorderColor':'#059669','lineColor':'#64748b','secondaryColor':'#f1f5f9','tertiaryColor':'#ffffff'}}}%%
flowchart TB
    settings["GET/PUT /inmopro/report-settings"] --> meta["Metas generales ventas"]
    reports["GET /inmopro/reports"] --> views["Vistas proyectos, equipos, etc."]
    reports --> pdf["GET /inmopro/reports/pdf"]
    reports --> csv["GET /inmopro/reports/csv"]
    meta --> pdf
    views --> pdf
```

---

## 19. Sugerencia de seguimiento con IA (lote)

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#faf5ff','primaryTextColor':'#0f172a','primaryBorderColor':'#7c3aed','lineColor':'#64748b','secondaryColor':'#f1f5f9','tertiaryColor':'#ffffff'}}}%%
flowchart LR
    user["Operador en ficha de lote"] --> action["POST /inmopro/lots/{lot}/ai-follow-up-suggestion"]
    action --> throttle["Middleware throttle:ai"]
    throttle --> aiSvc["Servicio IA Laravel"]
    aiSvc --> suggestion["Texto sugerido para seguimiento"]
    suggestion --> ui["Mostrar en UI"]
```

---

## 20. Resumen de rutas por proceso

```mermaid
%%{init: {'theme':'base', 'themeVariables': {'primaryColor':'#f8fafc','primaryTextColor':'#0f172a','primaryBorderColor':'#64748b','lineColor':'#94a3b8'}}}%%
flowchart LR
    apis["APIs moviles\nCazador y Datero"] --> tabla["Tabla siguiente"]
    web["Web Inmopro"] --> tabla
```

| Proceso | Canal | Rutas principales |
|---------|-------|-------------------|
| Login asesor | API Cazador | `POST /api/v1/cazador/auth/login` |
| Perfil asesor | API Cazador | `GET/PUT /me`, `PUT /me/pin` |
| Dateros desde app asesor | API Cazador | `GET/POST /dateros`, `PUT /dateros/{id}` |
| Clientes del asesor | API Cazador | `GET/POST /clients`, `GET/PUT /clients/{id}` |
| Recordatorios asesor | API Cazador | `GET/POST /reminders`, `GET/PUT/DELETE /reminders/{id}`, `POST /reminders/{id}/complete` (cliente **PROPIO**) |
| Tickets asesor | API Cazador | `GET/POST /attention-tickets`, `POST /attention-tickets/{id}/cancel` |
| Proyectos y lotes | API Cazador | `GET /projects`, `GET /projects/{id}`, `GET /lots`, `GET /lots/{id}`, `GET /my-lots` |
| Pre-reserva | API Cazador | `POST /lots/{lot}/pre-reservations` |
| Login datero | API Datero | `POST /api/v1/datero/auth/login` |
| Perfil datero | API Datero | `GET /me`, `PUT /me/pin`, `POST /auth/logout` |
| Clientes captados por datero | API Datero | `GET/POST /clients`, `GET/PUT /clients/{id}` (alcance `registered_by_datero_id`) |
| Ciudades API | Cazador / Datero | `GET /api/v1/cazador/cities`, `GET /api/v1/datero/cities` |
| Dashboard Inmopro | Web | `GET /inmopro/dashboard` |
| Proyectos y plantilla Excel | Web | `/inmopro/projects`, `GET .../excel-template`, `POST .../import-from-excel` |
| Directorio clientes | Web | `/inmopro/clients`, `GET .../export-excel`, `POST .../import-from-excel`, `GET .../search` |
| Tipos cliente, ciudades, teams, niveles | Web | `/inmopro/client-types`, `/cities`, `/teams`, `/advisor-levels` |
| Asesores y acceso Cazador | Web | `/inmopro/advisors`, `PUT .../cazador-access` |
| Dateros backoffice | Web | `/inmopro/dateros` (resource) |
| Tipos y membresías asesor | Web | `/inmopro/membership-types`, bulk-assign, `/inmopro/advisor-memberships` |
| Inventario lotes | Web | `/inmopro/lots`, `GET .../export-pdf`, `POST .../ai-follow-up-suggestion` |
| Estados catálogo | Web | `/inmopro/lot-statuses`, `/inmopro/commission-statuses` |
| Pre-reservas backoffice | Web | `/inmopro/lot-pre-reservations`, `POST .../approve`, `POST .../reject` |
| Transferencias | Web | `/inmopro/lot-transfer-confirmations`, `GET/POST /inmopro/lots/{lot}/transfer-confirmation`, approve/reject |
| Cobranza | Web | `/inmopro/accounts-receivable`, `POST .../lots/{lot}/installments`, `POST .../lots/{lot}/payments` |
| Caja y bancos | Web | `/inmopro/cash-accounts`, `POST ...`, `POST .../{id}/entries` |
| Vista financiera | Web | `GET /inmopro/financial` |
| Comisiones | Web | `/inmopro/commissions`, `POST .../{id}/mark-as-paid` |
| Tickets admin | Web | `/inmopro/attention-tickets`, `.../calendar`, `.../delivery-deed`, `mark-signed` |
| Agenda backoffice | Web | `/inmopro/agenda`, `advisor-agenda-events`, `advisor-reminders` |
| Reportes | Web | `/inmopro/reports`, `/reports/pdf`, `/reports/csv`, `/inmopro/report-settings` |
| Branding | Web | `GET/PUT /inmopro/branding` |
| Diagramas | Web | `GET /inmopro/process-diagrams` |

---

Para exportar a imagen de alta calidad use [mermaid.live](https://mermaid.live) o una extensión Mermaid en el editor.
