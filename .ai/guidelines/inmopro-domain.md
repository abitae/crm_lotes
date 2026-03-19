# Dominio Inmopro (CRM lotes)

- La aplicación gestiona **proyectos inmobiliarios**, **lotes**, **clientes**, **asesores**, **comisiones**, **cuotas**, **tickets de atención** y flujos como pre-reservas y confirmación de transferencias.
- Los textos de interfaz y mensajes al usuario deben ir en **español** salvo que el código existente use otro idioma de forma explícita.
- Los cambios de estado **TRANSFERIDO** no deben hacerse editando el lote a mano: el flujo oficial pasa por **confirmación de transferencia** (`LotTransferConfirmation`).
- Las comisiones se generan vía `App\Services\Inmopro\CommissionService::createCommissionsForTransferredLot` cuando corresponde el flujo de negocio (p. ej. tras aprobar transferencia), no asumir que existen comisiones solo por cambiar `lot_status_id` en un formulario.
- En **API Cazador**, los **recordatorios** (`ReminderController`) solo aplican a clientes del asesor con tipo **`PROPIO`** (misma regla que tickets de atención y pre-reservas).
- **DNI** y **teléfono** de cliente son **únicos en todo el sistema** al crear o actualizar (Inmopro web y API Cazador). Si hay conflicto, el mensaje indica el vendedor que registró al cliente existente (`ClientDuplicateRegistrationChecker`).
