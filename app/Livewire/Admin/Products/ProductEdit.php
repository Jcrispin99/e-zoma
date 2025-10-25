<?php

namespace App\Livewire\Admin\Products;

use Livewire\Component;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Variant;

class ProductEdit extends Component
{
    public $productId;
    public $name = '';
    public $description = '';
    public $price = '';
    public $category_id = '';
    public $generalSku = '';

    public $selectedAttributes = [];
    public $variantsData = [];
    public $existingVariants = []; // Para rastrear variantes originales

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'category_id' => 'required|exists:categories,id',
        'generalSku' => 'nullable|string',
        'variantsData' => 'array',
        'variantsData.*.sku' => 'nullable|string',
        // Relax required for variant price; fallback to base price when null
        'variantsData.*.price' => 'nullable|numeric|min:0',
        'variantsData.*.barcode' => 'nullable|string',
    ];

    public function mount($productId)
    {
        $this->productId = $productId;
        $product = Product::with(['variants.attributeValues', 'category'])->findOrFail($productId);

        // Cargar datos básicos
        $this->name = $product->name;
        $this->description = $product->description;
        $this->price = $product->price;
        $this->category_id = $product->category_id;
        $this->generalSku = optional($product->variants->first())->sku ?? '';

        // Reconstruir selectedAttributes desde las variantes existentes
        $this->loadExistingAttributes($product->variants);

        // Cargar variantes existentes
        $this->loadExistingVariants($product->variants);

        // Si no existen variantes, preparar una variante por defecto
        if ($product->variants->isEmpty()) {
            $this->generateVariantsPreview();
        }
    }

    public function updatedGeneralSku()
    {
        // Propagar el SKU general a variantes que no tengan SKU definido
        foreach ($this->variantsData as $index => $variant) {
            if (empty($variant['sku'])) {
                $this->variantsData[$index]['sku'] = $this->generalSku;
            }
        }
    }

    private function loadExistingAttributes($variants)
    {
        $attributeGroups = [];

        foreach ($variants as $variant) {
            foreach ($variant->attributeValues as $attributeValue) {
                $attributeId = $attributeValue->attribute_id;
                if (!isset($attributeGroups[$attributeId])) {
                    $attributeGroups[$attributeId] = [
                        'attribute_id' => $attributeId,
                        'values' => []
                    ];
                }
                if (!in_array($attributeValue->id, $attributeGroups[$attributeId]['values'])) {
                    $attributeGroups[$attributeId]['values'][] = $attributeValue->id;
                }
            }
        }

        $this->selectedAttributes = array_values($attributeGroups);
    }

    private function loadExistingVariants($variants)
    {
        $this->existingVariants = $variants->pluck('id')->toArray();

        foreach ($variants as $variant) {
            $attributeValueIds = $variant->attributeValues->pluck('id')->toArray();
            $description = $variant->attributeValues->pluck('value')->implode(' / ') ?: 'Default';

            $this->variantsData[] = [
                'id' => $variant->id, // Importante: mantener ID para updates
                'sku' => $variant->sku,
                'price' => $variant->price,
                'barcode' => $variant->barcode,
                'stock' => $variant->stock,
                'attribute_values' => $attributeValueIds,
                'description' => $description,
                'is_existing' => true
            ];
        }
    }

    public function addAttribute()
    {
        $this->selectedAttributes[] = [
            'attribute_id' => '',
            'values' => []
        ];
    }

    public function removeAttribute($index)
    {
        unset($this->selectedAttributes[$index]);
        $this->selectedAttributes = array_values($this->selectedAttributes);
        $this->generateVariantsPreview();
    }

    public function updatedSelectedAttributes()
    {
        $this->generateVariantsPreview();
    }

    public function updated($name, $value)
    {
        if (strpos($name, 'variantsData.') === 0) {
            $parts = explode('.', $name);
            if (count($parts) === 3) {
                $index = (int) $parts[1];
                $field = $parts[2];
                if ($field === 'price') {
                    $this->variantsData[$index]['price_manually_changed'] = true;
                } elseif ($field === 'sku') {
                    $this->variantsData[$index]['sku_manually_changed'] = true;
                } elseif ($field === 'barcode') {
                    $this->variantsData[$index]['barcode_manually_changed'] = true;
                }
            }
        }
    }

    public function updatedPrice()
    {
        // Si el precio base cambia, actualizamos las variantes que no han sido modificadas manualmente
        foreach ($this->variantsData as $index => $variant) {
            // Solo actualizar si el precio actual es igual al precio base anterior
            if (!isset($variant['price_manually_changed']) || !$variant['price_manually_changed']) {
                $this->variantsData[$index]['price'] = $this->price;
            }
        }
    }

    private function generateVariantsPreview()
    {
        $combinations = $this->calculateCombinations();
        $newVariantsData = [];

        if (empty($combinations)) {
            // Buscar si existe variante default
            $defaultVariant = collect($this->variantsData)->firstWhere('description', 'Default');

            $newVariantsData[] = [
                'id' => $defaultVariant['id'] ?? null,
                'sku' => $defaultVariant['sku'] ?? $this->generalSku,
                'price' => $defaultVariant['price'] ?? $this->price,
                'barcode' => $defaultVariant['barcode'] ?? '',
                'stock' => $defaultVariant['stock'] ?? 0,
                'attribute_values' => [],
                'description' => 'Default',
                'is_existing' => isset($defaultVariant['id'])
            ];
        } else {
            foreach ($combinations as $combination) {
                $attributeValues = AttributeValue::whereIn('id', $combination)->get();
                $description = $attributeValues->pluck('value')->implode(' / ');

                // Buscar si esta combinación ya existe
                $existingVariant = collect($this->variantsData)->first(function ($variant) use ($combination) {
                    return $variant['attribute_values'] == $combination;
                });

                $newVariantsData[] = [
                    'id' => $existingVariant['id'] ?? null,
                    // SKU editable; usar general por defecto si está vacío
                    'sku' => $existingVariant['sku'] ?? $this->generalSku,
                    'price' => $existingVariant['price'] ?? $this->price,
                    'barcode' => $existingVariant['barcode'] ?? '',
                    'stock' => $existingVariant['stock'] ?? 0,
                    'attribute_values' => $combination,
                    'description' => $description,
                    'is_existing' => isset($existingVariant['id'])
                ];
            }
        }

        $this->variantsData = $newVariantsData;
    }

    private function calculateCombinations()
    {
        $validAttributes = array_filter($this->selectedAttributes, function ($attr) {
            return !empty($attr['attribute_id']) && !empty($attr['values']);
        });

        if (empty($validAttributes)) {
            return [];
        }

        $combinations = [[]];
        foreach ($validAttributes as $attribute) {
            $newCombinations = [];
            foreach ($combinations as $combination) {
                foreach ($attribute['values'] as $valueId) {
                    $newCombinations[] = array_merge($combination, [$valueId]);
                }
            }
            $combinations = $newCombinations;
        }

        return $combinations;
    }

    public function update()
    {
        $this->validate();

        $product = Product::findOrFail($this->productId);

        // Si no hay variantes en memoria, crear una por defecto
        if (empty($this->variantsData)) {
            $this->variantsData = [[
                'id' => null,
                'sku' => $this->generalSku,
                'price' => $this->price,
                'barcode' => '',
                'stock' => 0,
                'attribute_values' => [],
                'description' => 'Default',
                'is_existing' => false,
            ]];
        }

        // Actualizar datos básicos del producto
        $product->update([
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'category_id' => $this->category_id,
        ]);

        // Obtener IDs de variantes actuales
        $currentVariantIds = collect($this->variantsData)
            ->whereNotNull('id')
            ->pluck('id')
            ->toArray();

        // Eliminar variantes que ya no están en la lista
        $variantsToDelete = array_diff($this->existingVariants, $currentVariantIds);
        if (!empty($variantsToDelete)) {
            Variant::whereIn('id', $variantsToDelete)->delete();
        }

        // Procesar cada variante
        foreach ($this->variantsData as $variantData) {
            $combinationIds = $variantData['attribute_values'] ?? [];
            // Usar SKU de la variante o propagar el general si está vacío
            $sku = ($variantData['sku'] ?? '') !== '' ? $variantData['sku'] : ($this->generalSku !== '' ? $this->generalSku : null);

            if (!empty($variantData['is_existing']) && !empty($variantData['id'])) {
                // Actualizar variante existente
                $variant = Variant::find($variantData['id']);
                $variant->update([
                    'sku' => $sku,
                    'price' => (isset($variantData['price']) && $variantData['price'] !== '') ? $variantData['price'] : $this->price,
                    'barcode' => ($variantData['barcode'] ?? '') !== '' ? $variantData['barcode'] : Variant::generateUniqueBarcode(),
                    // No actualizar stock aquí, se maneja por separado
                ]);

                // Actualizar relaciones de atributos con seguridad
                $variant->attributeValues()->sync($combinationIds);
            } else {
                // Crear nueva variante
                $variant = $product->variants()->create([
                    'sku' => $sku,
                    'price' => (isset($variantData['price']) && $variantData['price'] !== '') ? $variantData['price'] : $this->price,
                    'barcode' => ($variantData['barcode'] ?? '') !== '' ? $variantData['barcode'] : Variant::generateUniqueBarcode(),
                    'stock' => 0,
                ]);

                // Adjuntar relaciones de atributos si corresponde
                if (!empty($combinationIds)) {
                    $variant->attributeValues()->attach($combinationIds);
                }
            }
        }

        session()->flash('message', 'Producto actualizado exitosamente con ' . count($this->variantsData) . ' variante(s)');
        return redirect()->route('admin.products.index');
    }

    private function generateVariantSku($product, $attributeValues)
    {
        $productId = $product ? str_pad($product->id, 3, '0', STR_PAD_LEFT) : str_pad($this->productId, 3, '0', STR_PAD_LEFT);
        $baseSku = 'PROD-' . $productId;

        if ($attributeValues->isEmpty()) {
            return $baseSku . '-DEFAULT';
        }

        $valueCodes = $attributeValues
            ->map(fn($value) => strtoupper(substr(str_replace(' ', '', $value->value), 0, 3)))
            ->toArray();

        return $baseSku . '-' . implode('-', $valueCodes);
    }

    public function render()
    {
        return view('livewire.admin.products.product-edit', [
            'categories' => Category::all(),
        ]);
    }
}
