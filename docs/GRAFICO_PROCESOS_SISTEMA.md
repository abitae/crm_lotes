# Gráfico de procesos del sistema CRM Lotes

Documento con diagramas de procesos del sistema (Cazador API + Inmopro web). Se pueden visualizar en cualquier visor de Mermaid (GitHub, VS Code, [mermaid.live](https://mermaid.live)).

---

## 1. Vista general: actores y canales

```mermaid
flowchart LR
    subgraph Vendedor["Vendedor (asesor)"]
        APP[App móvil / Cliente]
    end

    subgraph Sistema["CRM Lotes"]
        API["API Cazador\n/api/v1/cazador"]
        WEB["Backend Inmopro\n/inmopro/*"]
        DB[(Base de datos)]
    end

    subgraph Admin["Administración"]
        NAV[Navegador web]
    end

    APP -->|Login PIN, clientes, pre-reservas, lotes| API
    API --> DB
    NAV -->|Proyectos, lotes, pre-reservas, cobranza, reportes| WEB
    WEB --> DB
```

---

## 2. Flujo del vendedor (API Cazador)

```mermaid
flowchart TB
    START([Inicio]) --> LOGIN[Login: username + PIN 6 dígitos]
    LOGIN --> TOKEN{Token OK?}
    TOKEN -->|No| LOGIN
    TOKEN -->|Sí| PERFIL[Consultar / actualizar perfil]
    PERFIL --> CLIENTES[Gestionar clientes propios]
    CLIENTES --> TICKETS[Crear tickets de atención por proyecto]
    TICKETS --> CATALOGO[Consultar proyectos y lotes disponibles]
    CATALOGO --> PRERESERVA[Registrar pre-reserva con voucher]
    PRERESERVA --> END([Esperar aprobación admin])
```

---

## 3. Flujo de pre-reserva y estados del lote

```mermaid
flowchart TB
    subgraph Vendedor["Acción vendedor"]
        A[Lote LIBRE] --> B[POST pre-reserva\ncliente + voucher]
        B --> C[Lote → PRERESERVA]
    end

    subgraph Admin["Acción administración"]
        C --> D{Revisar en\n/inmopro/lot-pre-reservations}
        D -->|Aprobar| E[Pre-reserva → APROBADA\nLote → RESERVADO]
        D -->|Rechazar| F[Pre-reserva → RECHAZADA\nLote → LIBRE]
    end

    subgraph Estados["Estados posteriores del lote"]
        E --> G[RESERVADO]
        G --> H[Confirmación backend con evidencia]
        H --> I[TRANSFERIDO]
        G --> J[Operación financiada]
        J --> K[CUOTAS]
    end

    F --> A
```

---

## 4. Máquina de estados del lote

```mermaid
stateDiagram-v2
    [*] --> LIBRE
    LIBRE --> PRERESERVA: Vendedor registra pre-reserva
    PRERESERVA --> LIBRE: Admin rechaza
    PRERESERVA --> RESERVADO: Admin aprueba
    RESERVADO --> TRANSFERIDO: Confirmación backend con imagen
    RESERVADO --> CUOTAS: Operación financiada
    TRANSFERIDO --> [*]
    CUOTAS --> [*]
```

---

## 5. Procesos del backend Inmopro (administración)

```mermaid
flowchart TB
    subgraph Maestros["Maestros y configuración"]
        M1[Proyectos]
        M2[Lotes]
        M3[Clientes]
        M4[Asesores / equipos]
        M5[Ciudades / tipos cliente]
        M6[Estados de lote / comisión]
        M7[Niveles de asesor]
    end

    subgraph Operaciones["Operaciones"]
        O1[Pre-reservas: aprobar / rechazar]
        O2[Transferencias: confirmar con evidencia]
        O3[Cuentas por cobrar: cuotas y pagos]
        O4[Cuentas de caja y movimientos]
        O5[Comisiones: marcar pagadas]
        O6[Tickets de atención: agenda, escritura, firmas]
    end

    subgraph Reportes["Reportes"]
        R1[Reportes y exportación PDF]
    end

    DASH[Dashboard] --> Maestros
    DASH --> Operaciones
    DASH --> Reportes
    O1 --> M2
    O2 --> M2
    O3 --> M2
    O6 --> M1
```

---

## 6. Flujo detallado: pre-reserva (vendedor → admin)

```mermaid
sequenceDiagram
    participant V as Vendedor
    participant API as API Cazador
    participant DB as Base de datos
    participant WEB as Inmopro Web
    participant A as Administrador

    V->>API: POST /lots/{lot}/pre-reservations (client_id, voucher, amount…)
    API->>API: Validar cliente propio, lote LIBRE, sin pre-reserva activa
    API->>DB: Crear pre-reserva PENDIENTE, lote → PRERESERVA
    API->>V: 201 Pre-reserva registrada

    A->>WEB: Listar pre-reservas
    WEB->>DB: Pre-reservas con filtros
    A->>WEB: Aprobar o Rechazar

    alt Aprobar
        WEB->>DB: Pre-reserva → APROBADA, lote → RESERVADO, asignar cliente/asesor
    else Rechazar
        WEB->>DB: Pre-reserva → RECHAZADA, lote → LIBRE, limpiar asignación
    end
```

---

## 7. Flujo detallado: confirmación de transferencia (backend)

```mermaid
sequenceDiagram
    participant U as Usuario con permiso
    participant WEB as Inmopro Web
    participant DB as Base de datos
    participant FS as Storage

    U->>WEB: Abrir lote RESERVADO
    U->>WEB: Ir a confirmar transferencia
    WEB->>WEB: Validar permiso confirm-lot-transfer
    WEB->>WEB: Validar que el lote siga en RESERVADO
    U->>WEB: Subir imagen + observaciones
    WEB->>FS: Guardar evidencia
    WEB->>DB: Registrar confirmación
    WEB->>DB: Lote -> TRANSFERIDO
    WEB->>DB: Generar comisiones
    WEB->>U: Redirigir al detalle del lote
```

---

## 8. Resumen de rutas por proceso

| Proceso              | Canal   | Rutas principales |
|----------------------|---------|--------------------|
| Login vendedor       | API     | `POST /api/v1/cazador/auth/login` |
| Perfil vendedor      | API     | `GET/PUT /me`, `PUT /me/pin` |
| Clientes del vendedor| API     | `GET/POST /clients`, `GET/PUT /clients/{id}` |
| Tickets de atención  | API     | `GET/POST /attention-tickets`, `POST .../cancel` |
| Proyectos y lotes    | API     | `GET /projects`, `GET /lots` |
| Pre-reserva          | API     | `POST /lots/{lot}/pre-reservations` |
| Aprobar/Rechazar pre-reserva | Web | `POST /inmopro/lot-pre-reservations/{id}/approve|reject` |
| Confirmar transferencia | Web | `GET/POST /inmopro/lots/{id}/transfer-confirmation` |
| Cuentas por cobrar   | Web     | `/inmopro/accounts-receivable`, cuotas y pagos por lote |
| Comisiones           | Web     | `/inmopro/commissions`, marcar pagadas |
| Reportes             | Web     | `/inmopro/reports`, PDF |
| Tickets atención (admin) | Web | `/inmopro/attention-tickets`, calendario, escritura |

---

Para generar una imagen a partir de un diagrama Mermaid se puede usar [mermaid.live](https://mermaid.live) o la extensión Mermaid en VS Code / Cursor.
