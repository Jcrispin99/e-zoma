<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\AttributeValue;

class ProductCreate extends Component
{
    public $name = '';
    public $description = '';
    public $price = '';
    public $category_id = '';

    public $selectedAttributes = [];
    public $variantsData = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'category_id' => 'required|exists:categories,id',
        'variantsData' => 'array',
        'variantsData.*.sku' => 'nullable|string',
        'variantsData.*.price' => 'required|numeric|min:0',
        'variantsData.*.barcode' => 'nullable|string',
    ];

    public function mount()
    {
        // Inicializamos con una variante por defecto
        $this->generateVariantsPreview();
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

    public function updatedPrice()
    {
        // Si el precio base cambia, actualizamos las variantes que no han sido modificadas
        foreach ($this->variantsData as $index => $variant) {
            if ($variant['price'] == $this->price) {
                $this->variantsData[$index]['price'] = $this->price;
            }
        }
    }

    private function generateVariantsPreview()
    {
        $combinations = $this->calculateCombinations();
        $this->variantsData = []; // Reiniciamos el array

        if (empty($combinations)) {
            $this->variantsData[] = [
                'sku' => '',
                'price' => $this->price,
                'barcode' => '',
                'attribute_values' => [],
                'description' => 'Default'
            ];
            return;
        }

        foreach ($combinations as $combination) {
            $attributeValues = AttributeValue::whereIn('id', $combination)->get();

            $this->variantsData[] = [
                'sku' => $this->generateVariantSku(null, $attributeValues),
                'price' => $this->price,
                'barcode' => '',
                'attribute_values' => $attributeValues->pluck('id')->toArray(),
                'description' => $attributeValues->pluck('value')->implode(' / ')
            ];
        }
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

        $product = Product::create([
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'category_id' => $this->category_id,
        ]);

        foreach ($this->variantsData as $variantData) {
            $variant = $product->variants()->create([
                'sku' => $variantData['sku'] ?: $this->generateVariantSku($product, AttributeValue::whereIn('id', $variantData['attribute_values'])->get()),
                'price' => $variantData['price'],
                'barcode' => $variantData['barcode'],
                'stock' => 0,
            ]);

            if (!empty($variantData['attribute_values'])) {
                $variant->attributeValues()->attach($variantData['attribute_values']);
            }
        }

        session()->flash('message', 'Producto creado exitosamente con ' . count($this->variantsData) . ' variante(s)');

        return redirect()->route('admin.products.index');
    }

    private function generateVariantSku($product, $attributeValues)
    {
        $productId = $product ? str_pad($product->id, 3, '0', STR_PAD_LEFT) : 'XXX';
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
        return view('livewire.admin.product-create', [
            'categories' => Category::all(),
        ]);
    }
}
