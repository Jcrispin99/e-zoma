# Plan de Desarrollo del Módulo TPV (Punto de Venta)

Este documento describe los pasos para implementar la funcionalidad completa del TPV en la aplicación.

## 1. Estructura de la Base de Datos

Se crearán las siguientes tablas para gestionar la configuración, sesiones, órdenes y pagos del TPV.

### 1.1. `pos_configs`

Almacenará la configuración específica para cada punto de venta.

- `id` (PK)
- `name` (string) - Nombre del punto de venta (Ej: "Caja Principal").
- `warehouse_id` (FK a `warehouses`) - Almacén desde donde se descontará el stock.
- `receipt_sequence_id` (FK a `sequences`) - Serie para los recibos.
- `invoice_sequence_id` (FK a `sequences`) - Serie para las facturas.
- `default_customer_id` (FK a `customers`) - Cliente por defecto para ventas rápidas.
- `is_active` (boolean) - Indica si el TPV está operativo.

### 1.2. `payment_methods`

Métodos de pago disponibles en el TPV.

- `id` (PK)
- `name` (string) - (Ej: "Efectivo", "Tarjeta de Crédito", "Transferencia").
- `is_active` (boolean)

### 1.3. `sequences`

Gestionará los números de secuencia para recibos y facturas.

- `id` (PK)
- `name` (string) - Nombre de la secuencia (Ej: "Recibos TPV-1").
- `prefix` (string) - Prefijo del número (Ej: "REC-").
- `sequence_size` (integer) - Número de dígitos (Ej: 8).
- `step` (integer) - Incremento (normalmente 1).
- `next_number` (integer) - Siguiente número a utilizar.

### 1.4. `pos_sessions`

Registrará el ciclo de vida de una sesión de caja (apertura, cierre).

- `id` (PK)
- `user_id` (FK a `users`) - Usuario que abre la sesión.
- `pos_config_id` (FK a `pos_configs`) - TPV utilizado.
- `opening_balance` (decimal) - Saldo inicial en caja.
- `closing_balance` (decimal, nullable) - Saldo final al cerrar.
- `opened_at` (datetime) - Fecha y hora de apertura.
- `closed_at` (datetime, nullable) - Fecha y hora de cierre.
- `status` (string) - (Ej: "abierta", "cerrada").

### 1.5. `pos_orders`

Cabecera de cada venta realizada en el TPV.

- `id` (PK)
- `pos_session_id` (FK a `pos_sessions`) - Sesión en la que se realizó la venta.
- `customer_id` (FK a `customers`) - Cliente de la venta.
- `total_amount` (decimal) - Monto total de la venta.
- `status` (string) - (Ej: "pagado", "pendiente", "cancelado").
- `created_at` (datetime)

### 1.6. `pos_order_lines`

Detalle de los productos en cada orden.

- `id` (PK)
- `pos_order_id` (FK a `pos_orders`) - Orden a la que pertenece.
- `variant_id` (FK a `variants`) - Producto vendido.
- `quantity` (integer)
- `price` (decimal) - Precio unitario en el momento de la venta.
- `subtotal` (decimal)

## 2. Modelos y Migraciones

Se generarán los modelos Eloquent y sus correspondientes migraciones para cada una de las tablas definidas anteriormente.

- `php artisan make:model PosConfig -m`
- `php artisan make:model PaymentMethod -m`
- `php artisan make:model Sequence -m`
- `php artisan make:model PosSession -m`
- `php artisan make:model PosOrder -m`
- `php artisan make:model PosOrderLine -m`

## 3. Lógica de Negocio y API

### 3.1. `KardexServices`

Se integrarán las ventas del TPV con el `KardexServices` existente para registrar las salidas de inventario. La llamada a `Kardex::registerExit()` se realizará desde el controlador de órdenes del TPV.

### 3.2. Controladores de la API

Se crearán los siguientes controladores para gestionar las operaciones del TPV:

- `PosSessionController`:
    - `openSession(Request $request)`: Inicia una nueva sesión de caja.
    - `closeSession(Request $request, PosSession $session)`: Cierra la sesión y calcula el descuadre.
    - `getActiveSession()`: Devuelve la sesión activa para el usuario.
- `PosOrderController`:
    - `store(Request $request)`: Crea una nueva orden, sus líneas, registra el pago y actualiza el Kardex.
- `PaymentMethodController`:
    - `index()`: Devuelve la lista de métodos de pago activos.

### 3.3. Rutas de la API

Se definirán las rutas en `routes/api.php` para exponer los endpoints de los controladores.

## 4. Interfaz de Usuario (Frontend)

La interfaz de usuario se construirá con Vue.js y se comunicará con la API.

- **Componente de Apertura de Caja**: Un formulario para iniciar la sesión con un saldo inicial.
- **Vista Principal del TPV**:
    - Lista de productos/variantes.
    - Carrito de compras.
    - Selección de cliente.
    - Selección de método de pago.
    - Botón para finalizar y registrar la venta.
- **Componente de Cierre de Caja**: Un resumen de la sesión con el total de ventas, saldo esperado y campo para el saldo real.