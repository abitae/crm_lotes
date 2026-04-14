# Flujos por áreas – Lotes en Remate (con responsables)

## 1. Compra de terrenos para lotizar
```mermaid
flowchart TD
    INI((Inicio))
    A1["Identificar terreno<br/><b>Responsable:</b> Operaciones / Gerencia / Administracion"]
    A2["Evaluar ubicación, precio y demanda<br/><b>Responsable:</b> Operaciones"]
    D1{"¿Rentable?<br/><b>Responsable:</b> Gerencia"}
    A3["Evaluación técnica<br/><b>Responsable:</b> Operaciones / Gerencia"]
    A4["Validación legal<br/><b>Responsable:</b> Gerencia / Legal"]
    D2{"¿Saneado?<br/><b>Responsable:</b> Operaciones / Gerencia"}
    A5["Negociación<br/><b>Responsable:</b> Gerencia"]
    A6["Aprobación gerencia<br/><b>Responsable:</b> Gerencia"]
    A7["Firma contrato<br/><b>Responsable:</b> Gerencia / Legal"]
    A8["Pago<br/><b>Responsable:</b> Administración / Gerencia"]
    A9["Notaría y SUNARP<br/><b>Responsable:</b> Gerencia / Legal"]
    FIN((Terreno adquirido))
    X1["Descartar<br/><b>Responsable:</b> Gerencia"]
    X2["Regularizar o descartar<br/><b>Responsable:</b> Legal / Gerencia"]

    INI --> A1 --> A2 --> D1
    D1 -- No --> X1
    D1 -- Sí --> A3 --> A4 --> D2
    D2 -- No --> X2
    D2 -- Sí --> A5 --> A6 --> A7 --> A8 --> A9 --> FIN
```

## 2. Registro de lotes en CRM
```mermaid
flowchart TD
    INI((Inicio))
    A1["Crear proyecto<br/><b>Responsable:</b> Contabilidad / Administracion"]
    A2["Registrar lotes y precios<br/><b>Responsable:</b> Contabilidad / Operaciones"]
    A3["Estado: Disponible<br/><b>Responsable:</b> Administración / Contabilidad"]
    A4["Adjuntar info: mapa, fotos y ubicación<br/><b>Responsable:</b> Operaciones / Marketing"]
    D1{"¿Completo?<br/><b>Responsable:</b> Administración / CRM"}
    A5["Corregir datos<br/><b>Responsable:</b> Contabilidad"]
    A6["Publicar inventario<br/><b>Responsable:</b> Contabilidad"]
    FIN((Lotes activos))

    INI --> A1 --> A2 --> A3 --> A4 --> D1
    D1 -- No --> A5 --> A4
    D1 -- Sí --> A6 --> FIN
```
## 3. Marketing

```mermaid
flowchart TD
    INI((Inicio))
    A1["Definir oferta<br/><b>Responsable:</b> Contabilidad / Marketing"]
    A2["Crear contenido<br/><b>Responsable:</b> Marketing"]
    A3["Lanzar campañas<br/><b>Responsable:</b> Marketing"]
    A4["Captar leads<br/><b>Responsable:</b> Marketing"]
    D1{"¿Leads buenos?<br/><b>Responsable:</b> Marketing / Líder Comercial"}
    A5["Optimizar campañas<br/><b>Responsable:</b> Marketing"]
    A6["Enviar leads a CRM<br/><b>Responsable:</b> Marketing / CRM"]
    FIN((Leads generados))

    INI --> A1 --> A2 --> A3 --> A4 --> D1
    D1 -- No --> A5 --> A3
    D1 -- Sí --> A6 --> FIN
```

## 4. Ventas (Cazadores)

```mermaid
flowchart TD
    INI((Inicio))
    A1["Recibir lead<br/><b>Responsable:</b> Cazador"]
    A2["Contactar cliente<br/><b>Responsable:</b> Cazador"]
    A3["Calificar<br/><b>Responsable:</b> Cazador"]
    D1{"¿Calificado?<br/><b>Responsable:</b> Cazador / Líder Comercial"}
    A4["Agendar visita<br/><b>Responsable:</b> Cazador"]
    A5["Visita guiada<br/><b>Responsable:</b> Cazador / Líder Comercial"]
    A6["Propuesta<br/><b>Responsable:</b> Cazador"]
    D2{"¿Compra?<br/><b>Responsable:</b> Cliente / Cazador"}
    A7["Seguimiento<br/><b>Responsable:</b> Cazador"]
    A8["Separación<br/><b>Responsable:</b> Cliente / Administración"]
    FIN((Cliente separado))

    INI --> A1 --> A2 --> A3 --> D1
    D1 -- No --> A7
    D1 -- Sí --> A4 --> A5 --> A6 --> D2
    D2 -- No --> A7
    D2 -- Sí --> A8 --> FIN
```

## 5. Administración / Caja

```mermaid
flowchart TD
    INI((Inicio))
    A1["Validar pago<br/><b>Responsable:</b> Administración / Contabilidad"]
    A2["Registrar en sistema<br/><b>Responsable:</b> Contabilidad"]
    A3["Generar contrato<br/><b>Responsable:</b> Contabilidad"]
    A4["Controlar pagos<br/><b>Responsable:</b> Contabilidad"]
    D1{"¿Pagos completos?<br/><b>Responsable:</b> Contabilidad / Administración"}
    A5["Enviar recordatorio de pago<br/><b>Responsable:</b> Contabilidad"]
    A6["Realizar cierre administrativo<br/><b>Responsable:</b> Administración"]
    FIN((Listo para legal))

    INI --> A1 --> A2 --> A3 --> A4 --> D1
    D1 -- No --> A5 --> A4
    D1 -- Sí --> A6 --> FIN
```

## 6. Legal / Notaría

```mermaid
flowchart TD
    INI((Inicio))
    A1["Revisar expediente<br/><b>Responsable:</b> Legal"]
    A2["Preparar minuta<br/><b>Responsable:</b> Legal  / Contabilidad"]
    A3["Coordinar notaría<br/><b>Responsable:</b> Legal / Contabilidad"]
    A4["Firma cliente<br/><b>Responsable:</b> Cliente / Notaría"]
    A5["Formalización<br/><b>Responsable:</b> Legal"]
    FIN((Proceso legal finalizado))

    INI --> A1 --> A2 --> A3 --> A4 --> A5 --> FIN
```

## 7. Postventa

```mermaid
flowchart TD
    INI((Inicio))
    A1["Entrega documentos<br/><b>Responsable:</b> Cazador"]
    A2["Entrega lote<br/><b>Responsable:</b> Operaciones / Cazador"]
    A3["Acta de entrega<br/><b>Responsable:</b> Operaciones / Cliente"]
    A4["Seguimiento Día 7<br/><b>Responsable:</b> Cazador / Postventa"]
    A5["Seguimiento Día 30<br/><b>Responsable:</b> Cazador / Postventa"]
    A6["Seguimiento Día 60<br/><b>Responsable:</b> Cazador / Postventa"]
    A7["Testimonio<br/><b>Responsable:</b> Marketing / Cazador"]
    A8["Referidos<br/><b>Responsable:</b> Cazador / Postventa"]
    FIN((Cliente fidelizado))

    INI --> A1 --> A2 --> A3 --> A4 --> A5 --> A6 --> A7 --> A8 --> FIN
```
