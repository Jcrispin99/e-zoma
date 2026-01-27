## Diagnóstico
- El checkout del POS permite seleccionar Factura sin validar el documento del cliente.
- El backend (/api/pos-sessions/{id}/sync) acepta voucher_type=invoice sin validar que el cliente tenga RUC.

## Objetivo
- Si el cliente tiene DNI (8 dígitos), no debe poder seleccionar Factura.
- Si el cliente tiene RUC (11 dígitos), sí debe poder seleccionar Factura.

## Implementación (Frontend)
1. En PaymentPage, agregar un computed `canInvoice` basado en `customer.document_number` (regex ^\d{11}$).
2. Deshabilitar el botón “Factura” cuando `canInvoice` sea false (y opcionalmente cuando no exista `invoiceJournalCode`).
3. Agregar un watch: si `docType==='factura'` y `canInvoice===false`, forzar `docType='boleta'`.
4. (Opcional) Mostrar una nota/tooltip breve “Factura solo para RUC” cuando esté deshabilitado.

## Implementación (Backend)
1. En PosSessionController::sync, antes de asignar el journal/crear Sale, validar:
   - Si voucher_type === 'invoice', el customer.document_number debe ser RUC (11 dígitos) o identity.name === 'RUC'.
2. Si no cumple, devolver 422 con mensaje claro.
3. Validar también que exista `posConfig->invoice_journal_id` cuando voucher_type sea invoice.

## Mejoras de datos (Opcional)
- Incluir `identity_id` (y/o identity.name) en `default_customer` del bootstrap para no depender sólo de longitud.

## Verificación
- Probar en /pos/{id}/checkout con cliente DNI: Factura deshabilitada y docType forzado a Boleta.
- Probar con cliente RUC: Factura habilitada y sync crea Sale con journal de invoice.
- Probar intento de enviar invoice con DNI: backend responde 422.
