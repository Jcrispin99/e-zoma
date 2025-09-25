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

    // Propiedades de atributos
    public $selectedAttributes = [];
    public $selectedAttributeValues = [];

    // Estado del componente
    public $showAttributeSection = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'category_id' => 'required|exists:categories,id',
        'selectedAttributes' => 'array',
        'selectedAttributeValues' => 'array',
    ];

    public function mount()
    {
        // Ya no necesitamos cargar atributos aquí, se cargarán via API
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
    }

    public function updatedSelectedAttributes($value, $key)
    {
        // Cuando cambia un atributo, limpiar sus valores
        if (str_contains($key, '.attribute_id')) {
            $index = explode('.', $key)[0];
            $this->selectedAttributes[$index]['values'] = [];
        }
    }

    public function getVariantsPreview()
    {
        // Calcular preview de variantes que se generarán
        $combinations = $this->calculateCombinations();
        return count($combinations);
    }

    private function calculateCombinations()
    {
        $validAttributes = array_filter($this->selectedAttributes, function ($attr) {
            return !empty($attr['attribute_id']) && !empty($attr['values']);
        });

        if (empty($validAttributes)) {
            return [[]]; // Una variante por defecto
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

        // Crear el producto
        $product = Product::create([
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'category_id' => $this->category_id,
        ]);

        // Generar variantes
        $this->generateVariants($product);

        session()->flash('message', 'Producto creado exitosamente con ' . $product->variants()->count() . ' variante(s)');

        return redirect()->route('admin.products.edit', $product);
    }

    private function generateVariants($product)
    {
        $combinations = $this->calculateCombinations();

        foreach ($combinations as $combination) {
            $variant = $product->variants()->create([
                'sku' => $this->generateVariantSku($product, $combination),
                'price' => $product->price,
                'stock' => 0,
            ]);

            if (!empty($combination)) {
                $variant->attributeValues()->attach($combination);
            }
        }
    }

    private function generateVariantSku($product, $combination)
    {
        $baseSku = 'PROD-' . str_pad($product->id, 3, '0', STR_PAD_LEFT);

        if (empty($combination)) {
            return $baseSku . '-DEFAULT';
        }

        $attributeValues = AttributeValue::whereIn('id', $combination)
            ->get()
            ->pluck('value')
            ->map(fn($value) => strtoupper(str_replace(' ', '', $value)))
            ->toArray();

        return $baseSku . '-' . implode('-', $attributeValues);
    }

    public function render()
    {
        return view('livewire.admin.product-create', [
            'categories' => Category::all(),
            'variantsCount' => $this->getVariantsPreview()
        ]);
    }
}
