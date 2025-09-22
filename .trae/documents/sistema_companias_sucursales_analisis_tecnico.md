# Análisis Técnico: Sistema de Compañías y Sucursales

## 1. Análisis de la Estructura Actual

### 1.1 Tablas Relacionadas con Almacenes
Basándome en las migraciones analizadas, el sistema actual tiene:

**Tabla `warehouses`:**
- `id` (Primary Key)
- `name` (Nombre del almacén)
- `location` (Ubicación del almacén)
- `created_at`, `updated_at`

**Relaciones identificadas:**
- `purchases.warehouse_id` → `warehouses.id`
- `sales.warehouse_id` → `warehouses.id` 
- `movements.warehouse_id` → `warehouses.id`
- `transfers.origin_warehouse_id` → `warehouses.id`
- `transfers.destination_warehouse_id` → `warehouses.id`
- `inventories.warehouse_id` → `warehouses.id`

### 1.2 Estructura de Usuarios
**Tabla `users`:**
- `id`, `name`, `email`, `password`
- Campos de autenticación estándar de Laravel

### 1.3 Entidades de Negocio
**Tablas `suppliers` y `customers`:**
- Ambas referencian `identity_id`
- Tienen `document_number` único
- Campos de contacto opcionales

### 1.4 Modelo Odoo de Referencia
Odoo utiliza un modelo jerárquico unificado en la tabla `res_company` con:
- `parent_id`: Referencia al padre (NULL para empresas matriz)
- `parent_path`: Optimización para consultas jerárquicas (ej: "1/3/7/")
- Campos adicionales: `name`, `currency_id`, `logo`, etc.
- Ventajas: Flexibilidad multi-nivel, consultas eficientes, menor complejidad

### 1.2 Problemática Identificada

* No existe concepto de compañía o empresa

* Los almacenes son entidades independientes sin agrupación

* Las operaciones están limitadas a nivel de almacén individual

* No hay estructura para manejar múltiples sucursales

## 2. Propuesta de Solución: Modelo Unificado Estilo Odoo

### 2.1 Enfoque Recomendado
Propongo una **arquitectura de tabla única jerárquica** inspirada en Odoo:

1. **Tabla `companies`**: Modelo unificado para empresas y sucursales
2. **Campo `parent_id`**: Establece la jerarquía (NULL = empresa matriz)
3. **Campo `parent_path`**: Optimización para consultas jerárquicas
4. **Modificación de `warehouses`**: Agregar relación con companies
5. **Modificación de `users`**: Agregar relación con companies

### 2.2 Ventajas del Modelo Unificado

**Simplicidad:**
- Una sola tabla para toda la jerarquía organizacional
- Menos joins en consultas complejas
- Modelo probado en producción (Odoo)

**Flexibilidad:**
- Soporte nativo para múltiples niveles jerárquicos
- Empresas pueden tener sub-empresas, sucursales, departamentos, etc.
- Fácil reorganización de la estructura

**Eficiencia:**
- Campo `parent_path` optimiza consultas de ancestros/descendientes
- Consultas jerárquicas más rápidas
- Menor complejidad en el código de aplicación

**Compatibilidad:**
- No afecta las migraciones existentes
- Los almacenes mantienen su estructura actual
- Adición incremental de funcionalidad

```
Company (Compañía)
├── Branch (Sucursal) [Opcional]
│   └── Warehouse (Almacén)
└── Warehouse (Almacén Directo) [Si no tiene sucursales]
```

### 2.2 Arquitectura del Modelo Unificado

Implementaremos un sistema jerárquico con una sola tabla `companies`:

#### Tabla `companies` (Modelo Unificado)

```sql
CREATE TABLE companies (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    parent_id BIGINT UNSIGNED NULL,
    parent_path VARCHAR(255) NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('company', 'branch', 'department') DEFAULT 'company',
    code VARCHAR(20) UNIQUE,
    document_type VARCHAR(20) NOT NULL, -- RUC, DNI, etc.
    document_number VARCHAR(20) UNIQUE NOT NULL,
    address TEXT NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    logo_path VARCHAR(2048) NULL,
    manager_name VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (parent_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_parent_id (parent_id),
    INDEX idx_parent_path (parent_path),
    INDEX idx_type (type)
);
```

**Campos clave:**
- `parent_id`: NULL para empresas matriz, referencia al padre para sucursales
- `parent_path`: Ruta jerárquica optimizada (ej: "1/3/7/")
- `type`: Tipo de entidad (company, branch, department)
- `code`: Código único para identificación rápida

### 2.3 Modificaciones a Tablas Existentes

#### Modificación: warehouses

```sql
ALTER TABLE warehouses 
ADD COLUMN company_id BIGINT UNSIGNED NULL AFTER location,
ADD FOREIGN KEY fk_warehouses_company (company_id) REFERENCES companies(id) ON DELETE SET NULL,
ADD INDEX idx_warehouses_company_id (company_id);
```

#### Modificación: users

```sql
ALTER TABLE users 
ADD COLUMN company_id BIGINT UNSIGNED NULL AFTER email_verified_at,
ADD FOREIGN KEY fk_users_company (company_id) REFERENCES companies(id) ON DELETE SET NULL,
ADD INDEX idx_users_company_id (company_id);
```

**Nota:** En el modelo unificado, tanto almacenes como usuarios se relacionan directamente con `companies.id`, que puede representar tanto empresas matriz como sucursales según el campo `type`.

## 3. Estrategia de Migración

### 3.1 Migración de Datos Existentes

1. **Crear Compañía por Defecto**:

```sql
INSERT INTO companies (name, document_type, document_number, is_active) 
VALUES ('Empresa Principal', 'RUC', '00000000000', TRUE);
```

1. **Asignar Almacenes Existentes**:

```sql
UPDATE warehouses 
SET company_id = 1, is_main = TRUE 
WHERE id = 1; -- Primer almacén como principal

UPDATE warehouses 
SET company_id = 1 
WHERE company_id IS NULL;
```

1. **Asignar Usuarios Existentes**:

```sql
UPDATE users 
SET company_id = 1, role = 'admin' 
WHERE id = 1; -- Primer usuario como admin

UPDATE users 
SET company_id = 1, role = 'employee' 
WHERE company_id IS NULL;
```

### 3.2 Orden de Ejecución de Migraciones

1. `create_companies_table.php` (modelo unificado)
2. `add_company_fields_to_warehouses_table.php`
3. `add_company_fields_to_users_table.php`
4. `migrate_existing_data_to_company_structure.php`

## 4. Casos de Uso y Relaciones

### 4.1 Empresa sin Sucursales
```sql
-- Crear empresa matriz
INSERT INTO companies (parent_id, name, type, code, document_type, document_number, address, phone, parent_path) 
VALUES (NULL, 'Empresa ABC S.A.C.', 'company', 'ABC', 'RUC', '20123456789', 'Av. Principal 123', '01-234-5678', '1/');

-- Asignar almacén directamente a la empresa
UPDATE warehouses SET company_id = 1 WHERE id = 1;
```

### 4.2 Empresa con Múltiples Sucursales
```sql
-- Crear empresa matriz
INSERT INTO companies (parent_id, name, type, code, document_type, document_number, parent_path) 
VALUES (NULL, 'Corporación XYZ S.A.', 'company', 'XYZ', 'RUC', '20987654321', '1/');

-- Crear sucursales (hijas de la empresa matriz)
INSERT INTO companies (parent_id, name, type, code, address, parent_path) VALUES 
(1, 'Sucursal Lima Norte', 'branch', 'XYZ-LN', 'Av. Túpac Amaru 456', '1/2/'),
(1, 'Sucursal Lima Sur', 'branch', 'XYZ-LS', 'Av. Pachacútec 789', '1/3/');

-- Asignar almacenes a sucursales
UPDATE warehouses SET company_id = 2 WHERE id = 2; -- Almacén en sucursal Lima Norte
UPDATE warehouses SET company_id = 3 WHERE id = 3; -- Almacén en sucursal Lima Sur
```

### 4.3 Estructura Multi-nivel (Ventaja del Modelo Unificado)
```sql
-- Crear estructura: Corporación > Región > Sucursal
INSERT INTO companies (parent_id, name, type, code, parent_path) VALUES 
(NULL, 'Corporación Nacional', 'company', 'CN', '1/'),
(1, 'Región Norte', 'department', 'CN-N', '1/2/'),
(1, 'Región Sur', 'department', 'CN-S', '1/3/'),
(2, 'Sucursal Trujillo', 'branch', 'CN-N-TRU', '1/2/4/'),
(2, 'Sucursal Chiclayo', 'branch', 'CN-N-CHI', '1/2/5/');
```

### 4.4 Consultas Jerárquicas Optimizadas

**Obtener todas las sucursales de una empresa:**
```sql
-- Usando parent_path para consulta eficiente
SELECT * FROM companies 
WHERE parent_path LIKE '1/%' AND id != 1;
```

**Obtener todos los almacenes de una empresa y sus sucursales:**
```sql
SELECT w.*, c.name as company_name, c.type 
FROM warehouses w
JOIN companies c ON w.company_id = c.id
WHERE c.parent_path LIKE '1/%' OR c.id = 1;
```

**Obtener la jerarquía completa:**
```sql
SELECT 
    c.*,
    REPEAT('  ', (LENGTH(parent_path) - LENGTH(REPLACE(parent_path, '/', '')) - 1)) as indent
FROM companies c
ORDER BY parent_path;
```

## 5. Impacto en Operaciones Comerciales

### 5.1 Modificaciones Necesarias en Transacciones

Todas las operaciones comerciales mantienen su estructura actual pero ahora pueden ser consultadas y agrupadas por compañía:

```sql
-- Ventas por compañía
SELECT c.name, SUM(s.total) as total_sales
FROM sales s
JOIN warehouses w ON s.warehouse_id = w.id
JOIN companies c ON w.company_id = c.id
GROUP BY c.id;

-- Inventario por compañía
SELECT c.name, SUM(i.quantity_balance) as total_inventory
FROM inventories i
JOIN warehouses w ON i.warehouse_id = w.id
JOIN companies c ON w.company_id = c.id
GROUP BY c.id;
```

### 5.2 Transferencias Entre Sucursales

Las transferencias existentes ahora pueden clasificarse:

* **Transferencias Internas**: Entre almacenes de la misma compañía

* **Transferencias Externas**: Entre almacenes de diferentes compañías

```sql
-- Identificar tipo de transferencia
SELECT t.*,
       wo.company_id as origin_company_id,
       wd.company_id as destination_company_id,
       CASE 
           WHEN wo.company_id = wd.company_id THEN 'INTERNAL'
           ELSE 'EXTERNAL'
       END as transfer_type
FROM transfers t
JOIN warehouses wo ON t.origin_warehouse_id = wo.id
JOIN warehouses wd ON t.destination_warehouse_id = wd.id;
```

## 6. Consideraciones de Implementación

### 6.1 Modelos Eloquent

```php
// Company.php
class Company extends Model
{
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }
    
    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }
    
    public function users()
    {
        return $this->hasMany(User::class);
    }
    
    public function mainWarehouse()
    {
        return $this->hasOne(Warehouse::class)->where('is_main', true);
    }
}

// Branch.php
class Branch extends Model
{
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function warehouse()
    {
        return $this->hasOne(Warehouse::class);
    }
}

// Warehouse.php (modificado)
class Warehouse extends Model
{
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
```

### 6.2 Middleware y Scopes

```php
// CompanyScope.php
class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (auth()->check() && auth()->user()->company_id) {
            $builder->whereHas('warehouse', function ($query) {
                $query->where('company_id', auth()->user()->company_id);
            });
        }
    }
}
```

### 6.3 Políticas de Acceso

* **Admin de Compañía**: Acceso completo a todas las sucursales y almacenes de su compañía

* **Manager de Sucursal**: Acceso limitado a su sucursal y almacén asignado

* **Empleado**: Acceso de solo lectura según permisos específicos

## 7. Beneficios de la Solución

### 7.1 Compatibilidad

* ✅ Mantiene toda la estructura existente

* ✅ No rompe funcionalidades actuales

* ✅ Migración gradual posible

### 7.2 Escalabilidad

* ✅ Soporte para múltiples compañías

* ✅ Estructura flexible para sucursales

* ✅ Fácil expansión futura

### 7.3 Funcionalidad

* ✅ Reportes consolidados por compañía

* ✅ Gestión independiente de inventarios

* ✅ Control de acceso granular

* ✅ Transferencias inter-sucursales

## 8. Próximos Pasos

1. **Fase 1**: Crear migraciones para nuevas tablas
2. **Fase 2**: Modificar tablas existentes
3. **Fase 3**: Migrar datos existentes
4. **Fase 4**: Actualizar modelos Eloquent
5. **Fase 5**: Implementar middleware y políticas
6. **Fase 6**: Actualizar interfaces de usuario
7. **Fase 7**: Testing y validación

Esta solución proporciona una base sólida para el crecimiento del sistema manteniendo la compatibilidad con la estructura actual del proyecto e-zoma.
