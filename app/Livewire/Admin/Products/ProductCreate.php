<?php

namespace App\Livewire\Admin\Products;

use Livewire\Component;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Variant;

class ProductCreate extends Component
{
    public $name = '';
    public $description = '';
    public $price = '';
    public $category_id = '';

    public $generalSku = '';
    public $selectedAttributes = [];
    public $variantsData = [];

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

    public function updatedGeneralSku()
    {
        // Propagar el SKU general a variantes que no tengan SKU definido
        foreach ($this->variantsData as $index => $variant) {
            if (empty($variant['sku'])) {
                $this->variantsData[$index]['sku'] = $this->generalSku;
            }
        }
    }

    public function updatedPrice()
    {
        // Cuando cambie el precio base, actualizar el precio de variantes no modificadas manualmente
        foreach ($this->variantsData as $index => $variant) {
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
            $newVariantsData[] = [
                'sku' => $this->generalSku, // default desde Información General
                'price' => $this->price,
                'barcode' => '',
                'stock' => 0,
                'attribute_values' => [],
                'description' => 'Default',
            ];
        } else {
            foreach ($combinations as $combination) {
                $attributeValues = AttributeValue::whereIn('id', $combination)->get();
                $description = $attributeValues->pluck('value')->implode(' / ');

                $newVariantsData[] = [
                    'sku' => $this->generalSku, // default desde Información General
                    'price' => $this->price,
                    'barcode' => '',
                    'stock' => 0,
                    'attribute_values' => $combination,
                    'description' => $description,
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

    public function save()
    {
        $this->validate();

        // Si no hay variantes generadas, crear una por defecto
        if (empty($this->variantsData)) {
            $this->variantsData = [[
                'sku' => $this->generalSku,
                'price' => $this->price,
                'barcode' => '',
                'stock' => 0,
                'attribute_values' => [],
                'description' => 'Default',
            ]];
        }

        // Crear producto
        $product = Product::create([
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'category_id' => $this->category_id,
        ]);

        // Crear variantes
        foreach ($this->variantsData as $variantData) {
            $combinationIds = $variantData['attribute_values'] ?? [];
            $sku = ($variantData['sku'] ?? '') !== '' ? $variantData['sku'] : ($this->generalSku !== '' ? $this->generalSku : null);

            $variant = $product->variants()->create([
                'sku' => $sku,
                'price' => (isset($variantData['price']) && $variantData['price'] !== '') ? $variantData['price'] : $this->price,
                'barcode' => ($variantData['barcode'] ?? '') !== '' ? $variantData['barcode'] : Variant::generateUniqueBarcode(),
                'stock' => 0,
            ]);

            if (!empty($combinationIds)) {
                $variant->attributeValues()->attach($combinationIds);
            }
        }

        session()->flash('message', 'Producto creado exitosamente con ' . count($this->variantsData) . ' variante(s)');
        return redirect()->route('admin.products.index');
    }

    // Mantener método por compatibilidad, aunque no se usa para generación automática
    private function generateVariantSku($product, $attributeValues)
    {
        $productId = $product ? str_pad($product->id, 3, '0', STR_PAD_LEFT) : '000';
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
        return view('livewire.admin.products.product-create', [
            'categories' => Category::all(),
        ]);
    }
}
