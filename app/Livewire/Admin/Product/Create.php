<?php

namespace App\Livewire\Admin\Product;

use Livewire\Component;
use App\Models\Category;
use App\Models\Product;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Variant;

class Create extends Component
{
    public $tab = 'product';

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
    
    // Propiedades para búsqueda de valores
    public $searchValues = [];
    public $availableValues = [];

    public function setTab($tab)
    {
        $this->tab = $tab;
    }

    public function mount()
    {
        // Inicializar con una fila de atributo vacía
        $this->productAttributes = [
            ['name' => '', 'values' => [], 'selectedValues' => []]
        ];
    }

    public function render()
    {
        $categories = Category::all();
        return view('livewire.admin.product.create', [
            'categories' => $categories
        ]);
    }

    public function saveProduct()
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
                'productAttributes.*.selectedValues.*' => 'nullable|string|max:255',
            ]);
        }

        // Crear el producto
        $product = Product::create([
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'category_id' => $this->category_id,
        ]);

        // Procesar atributos y crear variantes si existen
        $this->processAttributesAndVariants($product);

        // Mostrar mensaje de éxito
        $variantCount = count($this->generatedVariants);
        $message = 'Producto ' . $this->name . ' ha sido creado';
        if ($variantCount > 0) {
            $message .= ' con ' . $variantCount . ' variante(s)';
        }

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => $message,
        ]);

        // Redirigir a la página de edición del producto
        return redirect()->route('admin.products.edit', $product->id);
    }

    public function addAttributeRow()
    {
        $this->productAttributes[] = ['name' => '', 'values' => [], 'selectedValues' => []];
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
            
            // Usar el precio base del producto
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
            'productAttributes.*.selectedValues.*' => 'required|string|max:255',
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
        // Filtrar atributos válidos
        $validAttributes = collect($this->productAttributes)
            ->filter(function ($attr) {
                return !empty($attr['name']) && !empty($attr['selectedValues']);
            });

        // Si no hay atributos válidos, crear variante por defecto
        if ($validAttributes->isEmpty()) {
            $this->generateDefaultVariant($product);
            return;
        }

        $attributeModels = [];

        // Crear o encontrar atributos y sus valores
        foreach ($validAttributes as $attributeData) {
            // Crear o encontrar el atributo
            $attribute = Attribute::firstOrCreate([
                'name' => $attributeData['name']
            ]);

            $attributeModels[$attribute->id] = $attribute;
            $attributeValueIds = [];

            // Procesar valores seleccionados del atributo
            foreach ($attributeData['selectedValues'] as $value) {
                if (!empty($value)) {
                    $attributeValue = $this->createAttributeValue($attribute->id, $value);
                    $attributeValueIds[] = $attributeValue->id;
                }
            }
        }

        // Generar y crear variantes si hay atributos
        if (!empty($this->generatedVariants)) {
            $this->createVariants($product, $attributeModels);
        } else {
            // Si no se generaron variantes, crear una por defecto
            $this->generateDefaultVariant($product);
        }
    }

    private function createVariants($product, $attributeModels)
    {
        foreach ($this->generatedVariants as $index => $variantData) {
            // Crear la variante usando los arrays de precios y SKUs
            $variant = Variant::create([
                'product_id' => $product->id,
                'name' => $variantData['name'],
                'sku' => isset($this->variantSkus[$index]) && !empty($this->variantSkus[$index]) 
                    ? $this->variantSkus[$index] 
                    : $this->generateSku($product, $variantData),
                'price' => isset($this->variantPrices[$index]) && !empty($this->variantPrices[$index]) 
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

    // Métodos auxiliares para búsqueda de atributos y valores
    public function searchAttributes($query)
    {
        if (empty($query) || strlen($query) < 2) {
            return [];
        }

        // Buscar atributos existentes
        $existingAttributes = Attribute::where('name', 'LIKE', '%' . $query . '%')
            ->get()
            ->map(function ($attribute) {
                return [
                    'label' => $attribute->name,
                    'value' => $attribute->name,
                    'is_new' => false
                ];
            })
            ->toArray();

        // Si no hay coincidencias exactas, agregar opción para crear nuevo
        $exactMatch = collect($existingAttributes)->firstWhere('value', $query);
        if (!$exactMatch) {
            $existingAttributes[] = [
                'label' => $query . ' (Crear nuevo)',
                'value' => $query,
                'is_new' => true
            ];
        }

        return $existingAttributes;
    }

    public function searchAttributeValues($query, $attributeIndex)
    {
        if (empty($query) || strlen($query) < 2) {
            return [];
        }

        // Buscar valores existentes
        $attributeName = $this->productAttributes[$attributeIndex]['name'] ?? '';
        if (empty($attributeName)) {
            return [];
        }

        // Buscar atributo existente
        $attribute = Attribute::where('name', $attributeName)->first();
        if (!$attribute) {
            return [
                [
                    'label' => $query . ' (Crear nuevo)',
                    'value' => $query,
                    'is_new' => true
                ]
            ];
        }

        // Buscar valores existentes
        $existingValues = AttributeValue::where('attribute_id', $attribute->id)
            ->where('value', 'LIKE', '%' . $query . '%')
            ->get()
            ->map(function ($value) {
                return [
                    'label' => $value->value,
                    'value' => $value->value,
                    'is_new' => false
                ];
            })
            ->toArray();

        // Si no hay coincidencias exactas, agregar opción para crear nuevo
        $exactMatch = collect($existingValues)->firstWhere('value', $query);
        if (!$exactMatch) {
            $existingValues[] = [
                'label' => $query . ' (Crear nuevo)',
                'value' => $query,
                'is_new' => true
            ];
        }

        return $existingValues;
    }

    public function createAttributeValue($attributeId, $value)
    {
        // Buscar si ya existe el valor
        $existingValue = AttributeValue::where('value', $value)->first();
        
        if ($existingValue) {
            return $existingValue;
        }
        
        // Si no existe, crear nuevo AttributeValue
        return AttributeValue::create([
            'attribute_id' => $attributeId,
            'value' => $value
        ]);
    }

    public function generateDefaultVariant($product)
    {
        return Variant::create([
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $this->generateDefaultSku($product),
            'price' => $product->price,
            'stock' => 0,
        ]);
    }

    private function generateDefaultSku($product)
    {
        $baseSku = strtoupper(substr($product->name, 0, 3));
        return $baseSku . '-STD-' . rand(100, 999);
    }

    public function updatedProductAttributes($value, $key)
    {
        // Regenerar variantes cuando cambien los atributos
        if (str_contains($key, 'selectedValues')) {
            // Procesar valores seleccionados para crear nuevos si es necesario
            $this->processSelectedValues();
            $this->generateVariants();
        }
    }
    
    public function processSelectedValues()
    {
        foreach ($this->productAttributes as $index => &$attribute) {
            if (!empty($attribute['selectedValues'])) {
                $processedValues = [];
                
                foreach ($attribute['selectedValues'] as $value) {
                    // Si el valor no existe en la base de datos, crearlo
                    $existingValue = AttributeValue::where('value', $value)->first();
                    
                    if (!$existingValue) {
                        // Crear nuevo AttributeValue temporal (se guardará definitivamente al crear el producto)
                        $processedValues[] = $value;
                    } else {
                        $processedValues[] = $value;
                    }
                }
                
                $attribute['selectedValues'] = $processedValues;
            }
        }
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
