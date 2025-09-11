# Sistema de Atributos y Variantes - Documentación Técnica

## Introducción

Este documento explica la implementación completa del sistema de gestión de atributos y variantes para productos en Laravel con Livewire. El sistema permite crear productos con múltiples atributos (como color, talla, etc.) y generar automáticamente todas las combinaciones posibles como variantes.

## Arquitectura del Sistema

### Modelos de Base de Datos

#### 1. Product (Producto)
```php
- id
- name (nombre del producto)
- description
- price (precio base)
- category_id
```

#### 2. Attribute (Atributo)
```php
- id
- name (ej: "Color", "Talla", "Material")
```

#### 3. AttributeValue (Valor de Atributo)
```php
- id
- attribute_id (FK a attributes)
- value (ej: "Rojo", "M", "Algodón")
```

#### 4. Variant (Variante)
```php
- id
- product_id (FK a products)
- name (nombre completo: "Camiseta - Rojo M")
- sku (código único)
- price
- stock
```

#### 5. Tabla Pivot: variant_attribute_values
```php
- variant_id (FK a variants)
- attribute_value_id (FK a attribute_values)
```

### Relaciones del Modelo

```php
// Product.php
public function variants()
{
    return $this->hasMany(Variant::class);
}

// Variant.php
public function product()
{
    return $this->belongsTo(Product::class);
}

public function attributeValues()
{
    return $this->belongsToMany(AttributeValue::class, 'variant_attribute_values');
}

// AttributeValue.php
public function attribute()
{
    return $this->belongsTo(Attribute::class);
}

public function variants()
{
    return $this->belongsToMany(Variant::class, 'variant_attribute_values');
}
```

## Componentes Livewire

### 1. Create.php - Creación de Productos

#### Propiedades Principales
```php
public $productAttributes = []; // Atributos dinámicos del producto
public $generatedVariants = []; // Variantes generadas
public $showVariants = false;   // Control de visualización
```

#### Flujo de Creación

1. **Definición de Atributos**: El usuario define atributos y sus valores
2. **Generación de Variantes**: Se crean todas las combinaciones posibles
3. **Guardado**: Se persisten producto, atributos, valores y variantes

#### Métodos Clave

##### `generateVariants()`
```php
public function generateVariants()
{
    $this->generatedVariants = [];
    
    // Filtrar atributos válidos
    $validAttributes = collect($this->productAttributes)
        ->filter(function ($attr) {
            return !empty($attr['name']) && !empty($attr['selectedValues']);
        })
        ->map(function ($attr) {
            return [
                'name' => $attr['name'],
                'values' => $attr['selectedValues']
            ];
        })
        ->toArray();

    if (empty($validAttributes)) {
        $this->showVariants = false;
        return;
    }

    // Generar combinaciones
    $combinations = $this->generateCombinations($validAttributes);

    foreach ($combinations as $combination) {
        // Concatenar nombre del producto con valores de atributos
        $variantName = $this->name . ' - ' . implode(' ', $combination);
        
        $this->generatedVariants[] = [
            'name' => $variantName,
            'sku' => '',
            'price' => $this->price ?: 0,
            'stock' => 0,
            'attributes' => $combination
        ];
    }

    $this->showVariants = true;
}
```

##### `generateCombinations()` - Algoritmo Recursivo
```php
private function generateCombinations($attributes)
{
    if (empty($attributes)) {
        return [[]];
    }

    $first = array_shift($attributes);
    $rest = $this->generateCombinations($attributes);
    $combinations = [];

    foreach ($first['values'] as $value) {
        foreach ($rest as $combination) {
            $combinations[] = array_merge([$value], $combination);
        }
    }

    return $combinations;
}
```

**Ejemplo de Funcionamiento:**
- Atributos: Color=["Rojo", "Azul"], Talla=["S", "M"]
- Combinaciones generadas: 
  - ["Rojo", "S"]
  - ["Rojo", "M"]
  - ["Azul", "S"]
  - ["Azul", "M"]

##### `generateDefaultVariant()` - Productos Sin Atributos
```php
public function generateDefaultVariant($product)
{
    return Variant::create([
        'product_id' => $product->id,
        'name' => $product->name, // Solo el nombre del producto
        'sku' => $this->generateDefaultSku($product),
        'price' => $product->price,
        'stock' => 0,
    ]);
}
```

### 2. Edit.php - Edición de Productos

#### Características Especiales

1. **Carga de Datos Existentes**: Reconstruye atributos desde variantes existentes
2. **Preservación de Datos**: Mantiene SKUs, precios y stock personalizados
3. **Adición Incremental**: Permite agregar nuevos valores sin perder variantes existentes

#### Métodos Específicos de Edición

##### `loadExistingAttributes()`
```php
public function loadExistingAttributes()
{
    $this->productAttributes = [];
    $attributesData = [];

    // Extraer atributos únicos de variantes existentes
    foreach ($this->product->variants as $variant) {
        foreach ($variant->attributeValues as $attributeValue) {
            $attributeName = $attributeValue->attribute->name;
            $value = $attributeValue->value;

            if (!isset($attributesData[$attributeName])) {
                $attributesData[$attributeName] = [];
            }

            if (!in_array($value, $attributesData[$attributeName])) {
                $attributesData[$attributeName][] = $value;
            }
        }
    }

    // Convertir a formato multi-select
    foreach ($attributesData as $name => $values) {
        $this->productAttributes[] = [
            'name' => $name,
            'values' => [],
            'selectedValues' => $values
        ];
    }
}
```

##### `regenerateVariantsWithNewValues()`
```php
public function regenerateVariantsWithNewValues()
{
    $existingVariants = $this->generatedVariants;
    
    // Generar todas las variantes posibles
    $this->generateVariants();
    
    // Combinar evitando duplicados
    $this->generatedVariants = $this->mergeVariants($existingVariants, $this->generatedVariants);
}
```

## Funcionalidades Avanzadas

### 1. Multi-Select con Búsqueda

#### Búsqueda de Valores Existentes
```php
public function searchAttributeValues($query, $attributeId = null)
{
    $values = AttributeValue::query()
        ->when($attributeId, function ($q) use ($attributeId) {
            return $q->where('attribute_id', $attributeId);
        })
        ->where('value', 'like', '%' . $query . '%')
        ->pluck('value')
        ->toArray();

    // Opción para crear nuevo valor si no existe
    $exactMatch = collect($values)->contains(function ($value) use ($query) {
        return strtolower($value) === strtolower($query);
    });

    if (!$exactMatch && !empty($query)) {
        array_unshift($values, 'Crear: ' . $query);
    }

    return $values;
}
```

#### Creación Automática de Valores
```php
public function processSelectedValues($index)
{
    $selectedValues = $this->productAttributes[$index]['selectedValues'];
    $processedValues = [];

    foreach ($selectedValues as $value) {
        // Detectar valores nuevos con prefijo "Crear: "
        if (strpos($value, 'Crear: ') === 0) {
            $newValue = substr($value, 7);
            $processedValues[] = $newValue;
        } else {
            $processedValues[] = $value;
        }
    }

    $this->productAttributes[$index]['selectedValues'] = $processedValues;
}
```

### 2. Generación de SKUs

#### SKU para Variantes con Atributos
```php
private function generateSku($product, $variantData)
{
    $baseSku = strtoupper(substr($product->name, 0, 3));
    $variantSku = strtoupper(substr(str_replace([' ', '-'], '', $variantData['name']), 0, 6));
    return $baseSku . '-' . $variantSku . '-' . rand(100, 999);
}
```

#### SKU para Variante por Defecto
```php
private function generateDefaultSku($product)
{
    $baseSku = strtoupper(substr($product->name, 0, 4));
    return $baseSku . '-STD-' . rand(1000, 9999);
}
```

## Nomenclatura de Variantes

### Reglas de Nomenclatura

1. **Variantes CON atributos**: `"[Nombre del Producto] - [Valor1] [Valor2] [ValorN]"`
   - Ejemplo: `"Camiseta - Rojo M"`
   - Ejemplo: `"Zapatos - Negro 42"`

2. **Variantes SIN atributos**: `"[Nombre del Producto]"`
   - Ejemplo: `"Camiseta"`
   - Ejemplo: `"Accesorio Único"`

### Implementación

```php
// Para variantes con atributos
$variantName = $this->name . ' - ' . implode(' ', $combination);

// Para variante por defecto (sin atributos)
$variantName = $product->name;
```

## Casos de Uso

### Caso 1: Producto con Múltiples Atributos
```
Producto: "Camiseta Deportiva"
Atributos:
- Color: ["Rojo", "Azul", "Verde"]
- Talla: ["S", "M", "L"]
- Material: ["Algodón", "Poliéster"]

Variantes Generadas (18 total):
- "Camiseta Deportiva - Rojo S Algodón"
- "Camiseta Deportiva - Rojo S Poliéster"
- "Camiseta Deportiva - Rojo M Algodón"
- ... (y así sucesivamente)
```

### Caso 2: Producto Sin Atributos
```
Producto: "Accesorio Único"
Atributos: (ninguno)

Variante Generada:
- "Accesorio Único"
```

### Caso 3: Adición de Nuevos Valores
```
Producto Existente: "Pantalón"
Atributos Actuales:
- Color: ["Negro", "Azul"]
- Talla: ["30", "32"]

Nuevos Valores Agregados:
- Color: ["Gris"] (nuevo)
- Talla: ["34"] (nuevo)

Variantes Nuevas Generadas:
- "Pantalón - Gris 30"
- "Pantalón - Gris 32"
- "Pantalón - Gris 34"
- "Pantalón - Negro 34"
- "Pantalón - Azul 34"
```

## Ventajas del Sistema

1. **Flexibilidad**: Soporta cualquier número de atributos y valores
2. **Escalabilidad**: Maneja eficientemente grandes cantidades de combinaciones
3. **Usabilidad**: Interfaz intuitiva con búsqueda y creación automática
4. **Consistencia**: Nomenclatura estandarizada y predecible
5. **Mantenibilidad**: Código modular y bien estructurado
6. **Preservación de Datos**: No pierde información al editar productos

## Sistema de Precios Personalizados para Variantes

### Introducción

El sistema permite asignar precios personalizados a nivel de atributo, proporcionando flexibilidad para crear variantes con precios diferenciados. Si no se especifica un precio personalizado, la variante heredará automáticamente el precio base del producto.

### Estructura de Datos

#### Extensión de productAttributes
```php
public $productAttributes = [
    [
        'name' => 'Color',
        'selectedValues' => ['Rojo', 'Azul'],
        'price' => null // Nuevo campo para precio personalizado
    ]
];
```

### Lógica de Determinación de Precios

#### En generateVariants()
```php
foreach ($combinations as $combination) {
    // Determinar precio de la variante
    $variantPrice = $this->price; // Precio base por defecto
    
    // Buscar precio personalizado en atributos
    foreach ($validAttributes as $attr) {
        if (!empty($attr['price']) && is_numeric($attr['price'])) {
            $variantPrice = (float) $attr['price'];
            break; // Usar el primer precio personalizado encontrado
        }
    }
    
    $this->generatedVariants[] = [
        'name' => $variantName,
        'sku' => '',
        'price' => $variantPrice, // Precio calculado
        'stock' => 0,
        'attributes' => $combination
    ];
}
```

### Reglas de Precios

1. **Precio Base**: Si no se especifica precio personalizado, usar `$product->price`
2. **Precio Personalizado**: Si se define precio en un atributo, todas las variantes de ese atributo usarán ese precio
3. **Prioridad**: El primer precio personalizado encontrado tiene prioridad
4. **Validación**: Los precios deben ser números positivos o null

### Interfaz de Usuario

#### Columna de Precio Personalizado
```html
<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
    Precio Personalizado
</th>
```

#### Campo de Entrada
```html
<x-wire-input 
    type="number" 
    step="0.01" 
    min="0"
    wire:model="productAttributes.{{ $index }}.price"
    placeholder="{{ $price ? '$' . number_format($price, 2) : '$0.00' }}"
    class="w-full" 
/>
<p class="text-xs text-gray-500 mt-1">
    Vacío = precio base (${{ number_format($price ?? 0, 2) }})
</p>
```

### Validación

#### Reglas de Validación
```php
'productAttributes.*.price' => 'nullable|numeric|min:0'
```

#### Mensajes de Error
```php
'productAttributes.*.price.numeric' => 'El precio debe ser un número válido',
'productAttributes.*.price.min' => 'El precio debe ser mayor o igual a 0'
```

### Casos de Uso

#### Caso 1: Precio Diferenciado por Color
```
Producto: "Camiseta" (Precio base: $20.00)
Atributos:
- Color: ["Básico", "Premium"] - Precio: $25.00
- Talla: ["S", "M", "L"] - Sin precio personalizado

Variantes Generadas:
- "Camiseta - Básico S" - $25.00
- "Camiseta - Básico M" - $25.00
- "Camiseta - Premium S" - $25.00
- "Camiseta - Premium M" - $25.00
```

#### Caso 2: Sin Precio Personalizado
```
Producto: "Pantalón" (Precio base: $35.00)
Atributos:
- Color: ["Negro", "Azul"] - Sin precio personalizado
- Talla: ["30", "32"] - Sin precio personalizado

Variantes Generadas:
- "Pantalón - Negro 30" - $35.00 (precio base)
- "Pantalón - Negro 32" - $35.00 (precio base)
- "Pantalón - Azul 30" - $35.00 (precio base)
- "Pantalón - Azul 32" - $35.00 (precio base)
```

#### Caso 3: Múltiples Atributos con Precios
```
Producto: "Zapatos" (Precio base: $50.00)
Atributos:
- Material: ["Cuero", "Sintético"] - Precio: $75.00
- Color: ["Negro", "Marrón"] - Precio: $60.00
- Talla: ["40", "41", "42"] - Sin precio personalizado

Variantes Generadas:
- Todas las variantes usarán $75.00 (primer precio personalizado encontrado)
```

### Caso Complejo: Precios Diferenciados por Combinación

#### Escenario del Pantalón con Precios Variables

Este caso demuestra cómo el sistema maneja precios complejos donde diferentes combinaciones de atributos tienen precios únicos:

```
Producto: "Pantalón" (Precio base: $30.00)

Configuración de Atributos:
1. Talla: ["L", "XL"] - Precio personalizado: $40.00
2. Color: ["Negro", "Azul"] - Sin precio personalizado
3. Talla Especial: ["XL"] - Precio personalizado: $55.00
4. Combinación Premium: ["Negro"] - Precio personalizado: $60.00

Resultado de Variantes:
- "Pantalón - L Negro" → $40.00 (primer precio encontrado: Talla)
- "Pantalón - L Azul" → $40.00 (primer precio encontrado: Talla)
- "Pantalón - XL Negro" → $40.00 (primer precio encontrado: Talla)
- "Pantalón - XL Azul" → $40.00 (primer precio encontrado: Talla)
```

#### Implementación Recomendada para Casos Complejos

Para manejar el caso específico mencionado (XL=$55, L=$40, Negro XL=$60), se recomienda:

**Opción 1: Configuración por Filas Separadas**
```
Fila 1: Talla ["L"] - Precio: $40.00
Fila 2: Talla ["XL"] - Precio: $55.00  
Fila 3: Color ["Negro"] + Talla ["XL"] - Precio: $60.00
Fila 4: Color ["Azul"] - Sin precio (usa precio base $30.00)
```

**Opción 2: Ajuste Manual Post-Generación**
```
1. Generar variantes con precios base por atributo
2. Ajustar manualmente precios específicos en la tabla de variantes
3. Guardar con precios personalizados por variante
```

#### Limitaciones Actuales del Sistema

1. **Prioridad de Precios**: Solo se aplica el primer precio personalizado encontrado
2. **Combinaciones Específicas**: No hay lógica automática para precios de combinaciones múltiples
3. **Jerarquía**: No existe sistema de precedencia entre atributos

#### Recomendaciones para Casos Complejos

1. **Planificación**: Definir estructura de precios antes de crear atributos
2. **Simplicidad**: Usar un atributo por nivel de precio cuando sea posible
3. **Flexibilidad**: Aprovechar la edición manual de variantes para casos específicos
4. **Documentación**: Mantener registro de lógica de precios aplicada

### Ventajas del Sistema de Precios

1. **Flexibilidad**: Permite precios diferenciados por atributo
2. **Simplicidad**: Fallback automático al precio base
3. **Consistencia**: Lógica uniforme en Create y Edit
4. **Usabilidad**: Interfaz clara con indicadores visuales
5. **Validación**: Controles de entrada robustos
6. **Escalabilidad**: Maneja casos simples y complejos
7. **Transparencia**: Lógica de precios clara y predecible

## Consideraciones de Rendimiento

1. **Eager Loading**: Se cargan relaciones de forma eficiente
2. **Validación**: Se validan datos antes de procesamiento
3. **Transacciones**: Operaciones atómicas para consistencia
4. **Indexación**: Campos clave indexados en base de datos

## Conclusión

Este sistema proporciona una solución completa y robusta para la gestión de productos con atributos variables y precios personalizados, permitiendo una experiencia de usuario fluida tanto para administradores como para clientes finales. La funcionalidad de precios diferenciados añade flexibilidad comercial manteniendo la simplicidad de uso.