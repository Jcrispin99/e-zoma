# Reglas de Edición y Gestión de Estados - Sistema de Ventas

## Estados del Sistema

### 1. Estado Principal (`status`)

| Estado     | Descripción                               | Color Badge |
|------------|-------------------------------------------|-------------|
| `draft`    | Documento en borrador, editable           | Slate       |
| `posted`   | Documento contabilizado, genera movimientos | Emerald     |
| `cancelled`| Documento anulado, inmutable              | Rose        |

### 2. Estado de Pago (`payment_status`)

| Estado    | Descripción            | % Pagado | Color Badge |
|-----------|------------------------|----------|-------------|
| `unpaid`  | Sin pagos registrados  | 0%       | Slate       |
| `partial` | Pago parcial           | 1-99%    | Amber       |
| `paid`    | Pago completo          | 100%     | Emerald     |

### 3. Estado SUNAT (`sunat_status`)

| Estado       | Descripción                       | Color Badge |
|--------------|-----------------------------------|-------------|
| `pending`    | Pendiente de envío a SUNAT        | Slate       |
| `queued`     | En cola para envío automático     | Blue        |
| `processing` | Enviando a SUNAT (en proceso)     | Amber       |
| `accepted`   | Aceptado por SUNAT                | Emerald     |
| `rejected`   | Rechazado por SUNAT               | Rose        |
| `observed`   | Aceptado con observaciones        | Orange      |
| `error`      | Error técnico al enviar           | Red         |
| `cancelled`  | Dado de baja en SUNAT             | Purple      |

## Matriz de Permisos

| `status`   | `sunat_status` | Editar                  | Anular            | Enviar SUNAT | Registrar Pago | Crear NC/ND |
|------------|-----------------|-------------------------|-------------------|--------------|----------------|-------------|
| `draft`    | `pending`       | Sí                      | Sí                | No           | No             | No          |
| `posted`   | `pending`       | Limitado (no fiscal)    | Sí                | Sí           | Sí             | No          |
| `posted`   | `queued`        | No                      | No                | No           | Sí (cautela)   | No          |
| `posted`   | `processing`    | No                      | No                | No           | Sí (cautela)   | No          |
| `posted`   | `accepted`      | No                      | No (vía NC + baja)| No           | Sí             | Sí          |
| `posted`   | `rejected`      | Sí                      | Sí                | Sí           | Sí             | No          |
| `posted`   | `observed`      | No                      | No                | Sí           | Sí             | Sí          |
| `posted`   | `error`         | Sí                      | Sí                | Sí           | Sí             | No          |
| `posted`   | `cancelled`     | No                      | No                | No           | No             | No          |
| `cancelled`| `*`             | No                      | No                | No           | No             | No          |

Notas:
- Limitado: solo campos no fiscales (observación, almacén). No se pueden modificar productos, cantidades ni precios.
- "No (vía NC + baja)": la anulación de una factura aceptada por SUNAT se realiza mediante Nota de Crédito (07) y comunicación de baja.

## Reglas Aplicadas en la UI/Backend

- Guardar:
  - Bloqueado si `status = cancelled` o `sunat_status = cancelled`.
  - Limitado si `status = posted` y `sunat_status ∈ {pending, skipped}`.
  - Completo en `draft` o `posted` con `sunat_status ∈ {error, rejected}`.
- Reabrir:
  - Permitido desde `posted` si `sunat_status ∉ {accepted, queued, processing, cancelled, sent, observed}`.
- Cancelar:
  - Permitido en `draft`.
  - En `posted` si `sunat_status ∈ {pending, error, rejected}` y sin pagos (`payment_status ∉ {partial, paid}`).
  - Reversa de inventario al cancelar una venta publicada.
- Registrar Pago:
  - Permitido si `status = posted` y `payment_status ≠ paid`.
- Enviar a SUNAT:
  - Permitido si `status = posted` y `sunat_status ∈ {pending, error, rejected, observed}`.
- Crear NC/ND:
  - Permitido si `status = posted` y `sunat_status ∈ {accepted, observed}`.

## Flujo de Estados (Resumen)

1. `draft` → `posted` al contabilizar.
2. `posted` → `cancelled` si permitido (reversa inventario y sin pagos).
3. `posted` → `draft` (reabrir) si el estado SUNAT lo permite.
4. SUNAT: `pending` → `queued`/`processing` → `accepted`/`rejected`/`observed`/`error`.

Este documento resume la implementación vigente en el módulo de Ventas para facilitar mantenimiento y futuras mejoras.