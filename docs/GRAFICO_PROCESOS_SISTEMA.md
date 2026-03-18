# Gráfico de procesos del sistema CRM Lotes

Documento actualizado con diagramas Mermaid del sistema `CRM Lotes`, alineado con el flujo actual de `API Cazador` y `Inmopro web`.

---

## 1. Arquitectura general: actores, canales y almacenamiento

```mermaid
flowchart LR
    subgraph mobile [CanalMovil]
        advisorApp["App Cazador"]
        seller["Asesor"]
    end

    subgraph web [CanalWeb]
        browser["Navegador admin"]
        operator["Operador / administrador"]
    end

    subgraph core [CrmLotes]
        api["API Cazador\n/api/v1/cazador"]
        inmopro["Inmopro web\n/inmopro/*"]
        storage["Storage publico"]
        db["Base de datos"]
    end

    seller --> advisorApp
    advisorApp -->|"login, clientes, tickets, proyectos, lotes, pre-reservas"| api
    operator --> browser
    browser -->|"inventario, clientes, transferencias, cobranza, reportes"| inmopro
    api --> db
    inmopro --> db
    api --> storage
    inmopro --> storage
```

---

## 2. Recorrido operativo del asesor en API Cazador

```mermaid
flowchart TB
    startNode([Inicio]) --> login["POST auth/login"]
    login --> tokenCheck{"Token valido?"}
    tokenCheck -->|"No"| login
    tokenCheck -->|"Si"| profile["GET/PUT me\nPUT me/pin"]
    profile --> ownClients["Gestionar clientes propios"]
    ownClients --> tickets["Crear tickets de atencion"]
    tickets --> projects["Consultar proyectos"]
    projects --> lots["Consultar lotes disponibles"]
    lots --> canReserve{"Lote en LIBRE?"}
    canReserve -->|"No"| lots
    canReserve -->|"Si"| prereq["Registrar pre-reserva\ncon voucher y monto"]
    prereq --> waitAdmin([Esperar revision del backoffice])
```

---

## 3. Flujo detallado de pre-reserva desde la app

```mermaid
sequenceDiagram
    participant seller as Asesor
    participant api as APICazador
    participant db as BaseDeDatos
    participant storage as Storage
    participant web as InmoproWeb
    participant admin as Administrador

    seller->>api: POST /lots/{lot}/pre-reservations
    api->>api: Validar token del asesor
    api->>api: Validar cliente propio
    api->>api: Validar lote enviado y proyecto
    api->>api: Validar estado LIBRE
    api->>api: Validar que no exista pre-reserva activa
    api->>storage: Guardar voucher
    api->>db: Crear lot_pre_reservation PENDIENTE
    api->>db: Actualizar lote a PRERESERVA
    api->>db: Asignar client_id y advisor_id al lote
    api->>seller: 201 pre-reserva registrada

    admin->>web: Abrir bandeja de pre-reservas
    web->>db: Consultar con filtros y relaciones
    admin->>web: Aprobar o rechazar
    alt aprobacion
        web->>db: Pre-reserva -> APROBADA
        web->>db: Lote -> RESERVADO
    else rechazo
        web->>db: Pre-reserva -> RECHAZADA
        web->>db: Lote -> LIBRE
        web->>db: Limpiar cliente y asesor del lote
    end
```

---

## 4. Flujo actual de transferencia con aprobacion posterior

```mermaid
sequenceDiagram
    participant user as UsuarioConPermiso
    participant web as InmoproWeb
    participant db as BaseDeDatos
    participant storage as Storage
    participant reviewer as Revisor

    user->>web: Abrir lote RESERVADO
    user->>web: Entrar a confirmar transferencia
    web->>web: Verificar permiso confirm-lot-transfer
    web->>web: Verificar lote RESERVADO
    web->>web: Verificar que no exista transferencia PENDIENTE
    user->>web: Subir evidencia
    web->>storage: Guardar imagen
    web->>db: Crear lot_transfer_confirmation PENDIENTE
    web->>db: Cambiar lote a TRANSFERIDO
    web->>user: Redirigir a bandeja / detalle

    reviewer->>web: Abrir bandeja de transferencias
    web->>db: Listar lotes RESERVADO o TRANSFERIDO con ultima revision
    reviewer->>web: Aprobar o rechazar
    alt aprobacion
        web->>db: Revision -> APROBADA
        web->>db: Registrar reviewed_by y reviewed_at
        web->>db: Crear comisiones si no existen
    else rechazo
        web->>db: Revision -> RECHAZADA
        web->>db: Registrar motivo de rechazo
        web->>db: Regresar lote a RESERVADO
    end
```

---

## 5. Doble estado del lote y de la revision de transferencia

```mermaid
flowchart TB
    libre["Lote LIBRE"] --> prere["Lote PRERESERVA"]
    prere -->|"Aprobado"| reservado["Lote RESERVADO"]
    prere -->|"Rechazado"| libre

    reservado -->|"Registrar evidencia"| transferPending["Lote TRANSFERIDO\nRevision PENDIENTE"]
    transferPending -->|"Aprobar revision"| transferApproved["Lote TRANSFERIDO\nRevision APROBADA"]
    transferPending -->|"Rechazar revision"| transferRejected["Lote RESERVADO\nRevision RECHAZADA"]
    reservado -->|"Crear cronograma"| cuotas["Lote CUOTAS"]

    transferRejected -->|"Nueva evidencia"| transferPending
```

---

## 6. Mapa de estados del lote con puntos de control

```mermaid
stateDiagram-v2
    [*] --> LIBRE
    LIBRE --> PRERESERVA: API registra pre-reserva
    PRERESERVA --> LIBRE: Backoffice rechaza
    PRERESERVA --> RESERVADO: Backoffice aprueba
    RESERVADO --> TRANSFERIDO: Se registra transferencia
    RESERVADO --> CUOTAS: Se crean cuotas / financiamiento
    TRANSFERIDO --> RESERVADO: Revision de transferencia rechazada
    CUOTAS --> [*]
    TRANSFERIDO --> [*]
```

---

## 7. Cobranza y cuentas por cobrar por lote

```mermaid
flowchart TB
    activeLot["Lote RESERVADO / TRANSFERIDO / CUOTAS"] --> receivableIndex["Bandeja accounts-receivable"]
    receivableIndex --> filters["Filtrar por proyecto, estado o busqueda"]
    filters --> detail["Ver detalle de cuotas y pagos"]
    detail --> createInstallment["Registrar cuota"]
    detail --> createPayment["Registrar pago"]
    createInstallment --> installmentStatus["Calcular estado de cuota\nPENDIENTE / PAGADA / VENCIDA"]
    createPayment --> paymentLink["Asociar caja y forma de pago"]
    paymentLink --> paymentTotals["Actualizar total pagado y saldo"]
    paymentTotals --> summary["Resumen de cartera\nprogramado, cobrado, pendiente, vencido"]
```

---

## 8. Flujo de comisiones a partir de transferencias aprobadas

```mermaid
flowchart LR
    approval["Transferencia APROBADA"] --> validateAdvisor{"Lote tiene asesor?"}
    validateAdvisor -->|"No"| stopNode["No genera comision"]
    validateAdvisor -->|"Si"| readLevel["Leer nivel del asesor"]
    readLevel --> direct["Crear comision DIRECTA"]
    readLevel --> pyramidCheck{"Tiene superior?"}
    pyramidCheck -->|"Si"| pyramid["Crear comision PIRAMIDAL"]
    pyramidCheck -->|"No"| pendingOnly["Solo comision directa"]
    direct --> pendingStatus["Estado inicial PENDIENTE"]
    pyramid --> pendingStatus
    pendingOnly --> pendingStatus
    pendingStatus --> payFlow["Backoffice marca como PAGADO"]
```

---

## 9. Ciclo de clientes en backoffice y Excel

```mermaid
flowchart TB
    clientIndex["/inmopro/clients"] --> clientFilters["Buscar por nombre, DNI o telefono"]
    clientIndex --> dimensionFilters["Filtrar por tipo, ciudad y asesor"]
    clientIndex --> exportExcel["Exportar Excel filtrado"]
    clientIndex --> importExcel["Importar Excel masivo"]
    importExcel --> validateExcel["Validar archivo xlsx/xls"]
    validateExcel --> mapFields["Mapear nombre, dni, telefono, email,\ntipo, ciudad y asesor"]
    mapFields --> upsertClient["Crear o actualizar por DNI"]
    upsertClient --> clientList["Actualizar directorio"]
    clientList --> clientShow["Ver cliente y lotes asociados"]
```

---

## 10. Flujo de membresias de asesores

```mermaid
flowchart TB
    configTypes["Configurar tipos de membresia\nmeses, precio, estado"] --> advisors["Ir al modulo de asesores"]
    advisors --> assignMembership["Asignar membresia al asesor"]
    assignMembership --> membershipRecord["Crear advisor_membership"]
    membershipRecord --> schedule["Generar control de vigencia\ninicio, fin y monto"]
    schedule --> payments["Registrar abonos de membresia"]
    payments --> receivables["Actualizar cuentas por cobrar internas"]
    receivables --> reviewAdvisor["Consultar membresias activas,\nproximas a vencer o vencidas"]
```

---

## 11. Tickets de atencion y firma de escritura

```mermaid
sequenceDiagram
    participant seller as Asesor
    participant api as APICazador
    participant db as BaseDeDatos
    participant web as InmoproWeb
    participant staff as Operador

    seller->>api: Crear attention-ticket por proyecto
    api->>db: Registrar ticket
    staff->>web: Abrir calendario / listado
    web->>db: Consultar tickets y estados
    staff->>web: Programar o actualizar seguimiento
    staff->>web: Generar delivery deed
    web->>db: Marcar firma de escritura cuando corresponda
```

---

## 12. Caja, bancos y movimientos operativos

```mermaid
flowchart LR
    cashAccounts["Cuentas de caja activas"] --> registerEntry["Registrar ingreso o egreso"]
    registerEntry --> ledger["Libro de movimientos"]
    payments["Pagos de lotes / membresias"] --> linkCash["Vincular pago a caja"]
    linkCash --> ledger
    ledger --> balances["Saldos por cuenta"]
    balances --> finance["Vista financiera y reportes"]
```

---

## 13. Vista ejecutiva de modulos Inmopro

```mermaid
flowchart TB
    dashboard["Dashboard"] --> masters["Maestros"]
    dashboard --> sales["Ventas y clientes"]
    dashboard --> operations["Operacion"]
    dashboard --> finance["Finanzas"]
    dashboard --> reports["Reportes"]

    masters --> projects["Proyectos y lotes"]
    masters --> catalogs["Tipos cliente, ciudades,\nestados y niveles"]
    sales --> clients["Clientes"]
    sales --> advisors["Asesores y membresias"]
    operations --> prereTab["Pre-reservas"]
    operations --> transferTab["Transferencias"]
    operations --> tickets["Tickets"]
    finance --> receivableTab["Cuentas por cobrar"]
    finance --> cashTab["Caja y bancos"]
    finance --> commissions["Comisiones"]
    reports --> pdfReports["Reportes PDF"]
    reports --> processDocs["Diagramas del sistema"]
```

---

## 14. Resumen actualizado de rutas por proceso

| Proceso | Canal | Rutas principales |
|---------|-------|-------------------|
| Login vendedor | API | `POST /api/v1/cazador/auth/login` |
| Perfil vendedor | API | `GET/PUT /me`, `PUT /me/pin` |
| Clientes del asesor | API | `GET/POST /clients`, `GET/PUT /clients/{id}` |
| Tickets del asesor | API | `GET/POST /attention-tickets`, `POST /attention-tickets/{id}/cancel` |
| Catalogo de proyectos y lotes | API | `GET /projects`, `GET /projects/{id}`, `GET /lots`, `GET /lots/{id}` |
| Pre-reserva | API | `POST /lots/{lot}/pre-reservations` |
| Directorio de clientes | Web | `/inmopro/clients`, `GET /clients/export-excel`, `POST /clients/import-from-excel` |
| Inventario y detalle de lote | Web | `/inmopro/lots`, `GET /lots/{id}`, `GET /lots/export-pdf` |
| Pre-reservas backoffice | Web | `/inmopro/lot-pre-reservations`, `POST /approve`, `POST /reject` |
| Transferencias | Web | `/inmopro/lot-transfer-confirmations`, `GET/POST /inmopro/lots/{id}/transfer-confirmation`, `POST /approve`, `POST /reject` |
| Cobranza | Web | `/inmopro/accounts-receivable`, `POST /lots/{lot}/installments`, `POST /lots/{lot}/payments` |
| Caja y bancos | Web | `/inmopro/cash-accounts`, `POST /cash-accounts`, `POST /cash-accounts/{id}/entries` |
| Comisiones | Web | `/inmopro/commissions`, `POST /commissions/{id}/mark-as-paid` |
| Membresias | Web | `/inmopro/advisor-memberships`, `POST /advisor-memberships/{id}/payments` |
| Tickets de atencion admin | Web | `/inmopro/attention-tickets`, calendario, delivery deed, firma |
| Reportes y diagramas | Web | `/inmopro/reports`, `/inmopro/reports/pdf`, `/inmopro/process-diagrams` |

---

Para generar una imagen a partir de un diagrama Mermaid se puede usar [mermaid.live](https://mermaid.live) o la extensión Mermaid en VS Code / Cursor.
