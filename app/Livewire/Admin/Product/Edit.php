<?php

namespace App\Livewire\Admin\Product;

use Livewire\Component;
use App\Models\Category;
use App\Models\Product;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Variant;

class Edit extends Component
{
    public $tab = 'product';
    public $product;

    // Propiedades del producto
    public $name = '';
    public $description = '';
    public $price = '';
    public $category_id = '';

    // Propiedades para atributos dinámicos
    public $productAttributes = [];
    public $generatedVariants = [];
    public $showVariants = false;
    public $variantPrices = [];
    public $variantSkus = [];
    
    // Propiedades auxiliares para búsqueda y selección múltiple
    public $searchValues = [];
    public $availableValues = [];

    public function setTab($tab)
    {
        $this->tab = $tab;
    }

    public function mount($id)
    {
        // Cargar producto con eager loading
        $this->product = Product::with(['variants.attributeValues.attribute', 'category'])->findOrFail($id);

        // Cargar los datos del producto
        $this->name = $this->product->name;
        $this->description = $this->product->description;
        $this->price = $this->product->price;
        $this->category_id = $this->product->category_id;

        // Cargar atributos existentes del producto
        $this->loadExistingAttributes();

        // Cargar variantes existentes
        $this->loadExistingVariants();
    }

    public function render()
    {
        $categories = Category::all();
        return view('livewire.admin.product.edit', [
            'categories' => $categories
        ]);
    }

    public function updateProduct()
    {
        // Validar los campos del producto
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
        ]);

        // Validar atributos si existen
        if (!empty($this->productAttributes)) {
            $this->validate([
                'productAttributes.*.name' => 'nullable|string|max:255',
                'productAttributes.*.selectedValues' => 'nullable|array',
                'productAttributes.*.selectedValues.*' => 'string|max:255',
            ]);
        }

        // Actualizar el producto
        $this->product->update([
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'category_id' => $this->category_id,
        ]);

        // Procesar atributos y variantes
        $this->processAttributesAndVariants($this->product);

        // Mostrar mensaje de éxito
        $variantCount = count($this->generatedVariants);
        $message = 'Producto ' . $this->name . ' ha sido actualizado';
        if ($variantCount > 0) {
            $message .= ' con ' . $variantCount . ' variante(s)';
        }

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Actualizado!',
            'text' => $message,
        ]);

        // Redirigir a la lista de productos
        return redirect()->route('admin.products.index');
    }

    public function loadExistingAttributes()
    {
        $this->productAttributes = [];

        // Obtener atributos únicos de las variantes existentes
        $attributesData = [];

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

        // Convertir a formato esperado para multi-select
        foreach ($attributesData as $name => $values) {
            $this->productAttributes[] = [
                'name' => $name,
                'values' => [], // Mantener para compatibilidad
                'selectedValues' => $values, // Array de valores seleccionados
            ];
        }

        // Si no hay atributos, inicializar con una fila vacía
        if (empty($this->productAttributes)) {
            $this->productAttributes = [[
                'name' => '', 
                'values' => [], 
                'selectedValues' => [],
            ]];
        }
    }

    public function loadExistingVariants()
    {
        $this->generatedVariants = [];
        $this->variantPrices = [];
        $this->variantSkus = [];

        foreach ($this->product->variants as $index => $variant) {
            $attributes = [];
            foreach ($variant->attributeValues as $attributeValue) {
                $attributes[] = $attributeValue->value;
            }

            $this->generatedVariants[] = [
                'id' => $variant->id,
                'name' => $variant->name,
                'sku' => $variant->sku,
                'price' => $variant->price,
                'stock' => $variant->stock,
                'attributes' => $attributes
            ];

            // Cargar precios y SKUs existentes
            $this->variantPrices[$index] = $variant->price;
            $this->variantSkus[$index] = $variant->sku;
        }

        $this->showVariants = !empty($this->generatedVariants);
    }

    public function addAttributeRow()
    {
        $this->productAttributes[] = [
            'name' => '', 
            'values' => [], 
            'selectedValues' => [],
        ];
    }

    public function removeAttributeRow($index)
    {
        if (count($this->productAttributes) > 1) {
            unset($this->productAttributes[$index]);
            $this->productAttributes = array_values($this->productAttributes);
        }
    }

    public function generateVariants()
    {
        $this->generatedVariants = [];

        // Filtrar atributos que tengan nombre y valores seleccionados
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

        // Inicializar arrays para precios y SKUs
        $this->variantPrices = [];
        $this->variantSkus = [];

        foreach ($combinations as $index => $combination) {
            // Concatenar nombre del producto con valores de atributos
            $variantName = $this->name . ' - ' . implode(' ', $combination);
            
            // Determinar precio de la variante
            $variantPrice = $this->price ?: 0;
            
            $this->generatedVariants[] = [
                'name' => $variantName,
                'sku' => '',
                'price' => $variantPrice,
                'stock' => 0,
                'attributes' => $combination
            ];

            // Inicializar precio y SKU para esta variante
            $this->variantPrices[$index] = $variantPrice;
            $this->variantSkus[$index] = '';
        }

        $this->showVariants = true;
    }

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

    public function saveAttributes()
    {
        // Validar atributos
        $this->validate([
            'productAttributes.*.name' => 'required|string|max:255',
            'productAttributes.*.selectedValues' => 'required|array|min:1',
            'productAttributes.*.selectedValues.*' => 'string|max:255',
            'variantPrices.*' => 'nullable|numeric|min:0',
            'variantSkus.*' => 'nullable|string|max:255',
        ], [
            'productAttributes.*.name.required' => 'El nombre del atributo es requerido',
            'productAttributes.*.selectedValues.required' => 'Debe seleccionar al menos un valor para el atributo',
            'productAttributes.*.selectedValues.min' => 'Debe seleccionar al menos un valor para el atributo',
            'variantPrices.*.numeric' => 'El precio debe ser un número válido',
            'variantPrices.*.min' => 'El precio no puede ser negativo',
            'variantSkus.*.max' => 'El SKU no puede exceder 255 caracteres',
        ]);

        session()->flash('message', 'Atributos validados correctamente. Genere las variantes para continuar.');
    }

    private function processAttributesAndVariants($product)
    {
        // NO eliminar variantes existentes - solo agregar nuevas
        // Las variantes existentes se mantienen por sus relaciones con ventas

        // Filtrar atributos válidos
        $validAttributes = collect($this->productAttributes)
            ->filter(function ($attr) {
                return !empty($attr['name']) && !empty($attr['selectedValues']);
            });

        if ($validAttributes->isEmpty()) {
            // Crear variante por defecto si no hay atributos
            $this->generatedVariants = [$this->generateDefaultVariant()];
            $this->createVariants($product, []);
            return;
        }

        $attributeModels = [];

        // Procesar valores seleccionados antes de crear atributos
        foreach ($validAttributes as $index => $attributeData) {
            $this->processSelectedValues($index);
        }

        // Crear o encontrar atributos y sus valores
        foreach ($validAttributes as $attributeData) {
            // Crear o encontrar el atributo
            $attribute = Attribute::firstOrCreate([
                'name' => $attributeData['name']
            ]);

            $attributeModels[$attribute->id] = $attribute;

            // Procesar valores seleccionados del atributo
            foreach ($attributeData['selectedValues'] as $value) {
                if (!empty($value)) {
                    $this->createAttributeValue($attribute->id, $value);
                }
            }
        }

        // Generar y crear variantes si hay atributos
        if (!empty($this->generatedVariants)) {
            $this->createVariants($product, $attributeModels);
        }
    }

    private function createVariants($product, $attributeModels)
    {
        foreach ($this->generatedVariants as $index => $variantData) {
            // Verificar si la variante ya existe
            $existingVariant = Variant::where('product_id', $product->id)
                ->where('name', $variantData['name'])
                ->first();
            
            if ($existingVariant) {
                // Si existe, actualizar precio y SKU desde los arrays
                if (isset($this->variantPrices[$index])) {
                    $existingVariant->price = $this->variantPrices[$index];
                }
                if (isset($this->variantSkus[$index]) && !empty($this->variantSkus[$index])) {
                    $existingVariant->sku = $this->variantSkus[$index];
                }
                $existingVariant->save();
                continue;
            }
            
            // Crear nueva variante solo si no existe
            $variant = Variant::create([
                'product_id' => $product->id,
                'name' => $variantData['name'],
                'sku' => isset($this->variantSkus[$index]) && !empty($this->variantSkus[$index]) 
                    ? $this->variantSkus[$index] 
                    : $this->generateSku($product, $variantData),
                'price' => isset($this->variantPrices[$index]) 
                    ? $this->variantPrices[$index] 
                    : $variantData['price'],
                'stock' => $variantData['stock'] ?? 0,
            ]);

            // Asociar valores de atributos con la variante
            $attributeValueIds = [];
            foreach ($variantData['attributes'] as $index => $value) {
                $attributeId = array_keys($attributeModels)[$index] ?? null;
                if ($attributeId) {
                    $attributeValue = AttributeValue::where('attribute_id', $attributeId)
                        ->where('value', $value)
                        ->first();
                    if ($attributeValue) {
                        $attributeValueIds[] = $attributeValue->id;
                    }
                }
            }

            if (!empty($attributeValueIds)) {
                $variant->attributeValues()->sync($attributeValueIds);
            }
        }
    }

    private function generateSku($product, $variantData)
    {
        $baseSku = strtoupper(substr($product->name, 0, 3));
        $variantSku = strtoupper(substr(str_replace([' ', '-'], '', $variantData['name']), 0, 6));
        return $baseSku . '-' . $variantSku . '-' . rand(100, 999);
    }

    // Métodos auxiliares para búsqueda y creación de valores
    public function searchAttributes($query)
    {
        if (empty($query)) {
            return [];
        }

        // Buscar atributos existentes
        $existingAttributes = Attribute::where('name', 'LIKE', "%{$query}%")
            ->limit(10)
            ->get()
            ->map(function ($attribute) {
                return [
                    'label' => $attribute->name,
                    'value' => $attribute->name,
                    'id' => $attribute->id
                ];
            });

        // Si no hay coincidencias exactas, agregar opción para crear nuevo atributo
        $exactMatch = Attribute::where('name', $query)->exists();

        if (!$exactMatch && !empty($query)) {
            $existingAttributes->prepend([
                'label' => "Crear: {$query}",
                'value' => $query,
                'id' => null,
                'create_new' => true
            ]);
        }

        return $existingAttributes->values()->all();
    }

    public function searchAttributeValues($query, $attributeId = null)
    {
        $values = AttributeValue::query()
            ->when($attributeId, function ($q) use ($attributeId) {
                return $q->where('attribute_id', $attributeId);
            })
            ->where('value', 'like', '%' . $query . '%')
            ->pluck('value')
            ->toArray();

        // Si no hay coincidencia exacta, agregar opción para crear nuevo valor
        $exactMatch = collect($values)->contains(function ($value) use ($query) {
            return strtolower($value) === strtolower($query);
        });

        if (!$exactMatch && !empty($query)) {
            array_unshift($values, 'Crear: ' . $query);
        }

        return $values;
    }

    public function createAttributeValue($attributeId, $value)
    {
        // Verificar si el valor ya existe
        $existingValue = AttributeValue::where('attribute_id', $attributeId)
            ->where('value', $value)
            ->first();

        if (!$existingValue) {
            AttributeValue::create([
                'attribute_id' => $attributeId,
                'value' => $value
            ]);
        }

        return $value;
    }

    public function generateDefaultVariant()
    {
        return [
            'name' => $this->name,
            'sku' => $this->generateDefaultSku(),
            'price' => $this->price ?: 0,
            'stock' => 0,
            'attributes' => []
        ];
    }

    private function generateDefaultSku()
    {
        $baseSku = strtoupper(substr($this->name ?: 'PROD', 0, 4));
        return $baseSku . '-STD-' . rand(1000, 9999);
    }

    public function updatedProductAttributes()
    {
        // Procesar valores seleccionados y regenerar variantes automáticamente
        foreach ($this->productAttributes as $index => $attribute) {
            if (!empty($attribute['selectedValues'])) {
                $this->processSelectedValues($index);
            }
        }
        
        $this->generateVariants();
    }

    public function processSelectedValues($index)
    {
        if (!isset($this->productAttributes[$index]['selectedValues'])) {
            return;
        }

        $selectedValues = $this->productAttributes[$index]['selectedValues'];
        $processedValues = [];

        foreach ($selectedValues as $value) {
            // Si el valor comienza con "Crear: ", extraer el valor real
            if (strpos($value, 'Crear: ') === 0) {
                $newValue = substr($value, 7); // Remover "Crear: "
                $processedValues[] = $newValue;
            } else {
                $processedValues[] = $value;
            }
        }

        $this->productAttributes[$index]['selectedValues'] = $processedValues;
    }

    // Método para agregar valores a atributos existentes sin perder variantes
    public function addValuesToExistingAttribute($attributeIndex, $newValues)
    {
        if (!isset($this->productAttributes[$attributeIndex])) {
            return;
        }

        $currentValues = $this->productAttributes[$attributeIndex]['selectedValues'] ?? [];
        $mergedValues = array_unique(array_merge($currentValues, $newValues));
        
        $this->productAttributes[$attributeIndex]['selectedValues'] = $mergedValues;
        
        // Regenerar variantes manteniendo las existentes
        $this->regenerateVariantsWithNewValues();
    }

    public function regenerateVariantsWithNewValues()
    {
        $existingVariants = $this->generatedVariants;
        
        // Generar todas las variantes posibles con los nuevos valores
        $this->generateVariants();
        
        // Combinar variantes existentes con las nuevas, evitando duplicados
        $this->generatedVariants = $this->mergeVariants($existingVariants, $this->generatedVariants);
    }

    private function mergeVariants($existingVariants, $newVariants)
    {
        $merged = $existingVariants;
        
        foreach ($newVariants as $newVariant) {
            $isDuplicate = false;
            
            foreach ($existingVariants as $existingVariant) {
                if ($this->areVariantsEqual($existingVariant, $newVariant)) {
                    $isDuplicate = true;
                    break;
                }
            }
            
            if (!$isDuplicate) {
                $merged[] = $newVariant;
            }
        }
        
        return $merged;
    }

    private function areVariantsEqual($variant1, $variant2)
    {
        if (count($variant1['attributes']) !== count($variant2['attributes'])) {
            return false;
        }
        
        sort($variant1['attributes']);
        sort($variant2['attributes']);
        
        return $variant1['attributes'] === $variant2['attributes'];
    }
}
