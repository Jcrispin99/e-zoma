<?php

namespace App\Livewire\Admin\Form;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Category;
use App\Models\AttributeValue;
use App\Models\Variant;

class ProductForm extends Component
{
    public ?int $productId = null;
    public bool $isEditing = false;
    public bool $redirectAfterSave = true;

    public string $name = '';
    public string $description = '';
    public string $price = '';
    public ?int $category_id = null;
    public string $generalSku = '';

    /** @var array<int, array{attribute_id:int|string, values:array<int>}> */
    public array $selectedAttributes = [];
    /** @var array<int, array<string, mixed>> */
    public array $variantsData = [];
    /** @var array<int> */
    public array $existingVariants = [];

    public function mount(?int $productId = null): void
    {
        $this->productId = $productId;
        $this->isEditing = filled($productId);

        if ($this->isEditing) {
            $product = Product::with(['variants.attributeValues', 'category'])->findOrFail($productId);

            $this->name = (string) $product->name;
            $this->description = (string) ($product->description ?? '');
            $this->price = (string) $product->price;
            $this->category_id = $product->category_id;
            $this->generalSku = (string) (optional($product->variants->first())->sku ?? '');

            $this->loadExistingAttributes($product->variants);
            $this->loadExistingVariants($product->variants);

            if ($product->variants->isEmpty()) {
                $this->generateVariantsPreview();
            }
        } else {
            // Estado inicial sin variantes
            $this->variantsData = [];
        }
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'generalSku' => 'nullable|string',
            'variantsData' => 'array',
            'variantsData.*.sku' => 'nullable|string',
            'variantsData.*.price' => 'nullable|numeric|min:0',
            'variantsData.*.barcode' => 'nullable|string',
        ];
    }

    public function addAttribute(): void
    {
        $this->selectedAttributes[] = [
            'attribute_id' => '',
            'values' => [],
        ];
    }

    public function removeAttribute(int $index): void
    {
        unset($this->selectedAttributes[$index]);
        $this->selectedAttributes = array_values($this->selectedAttributes);
        $this->generateVariantsPreview();
    }

    public function updatedSelectedAttributes(): void
    {
        $this->generateVariantsPreview();
    }

    public function updated(string $name, $value): void
    {
        if (str_starts_with($name, 'variantsData.')) {
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

    public function updatedGeneralSku(): void
    {
        foreach ($this->variantsData as $index => $variant) {
            if (empty($variant['sku'])) {
                $this->variantsData[$index]['sku'] = $this->generalSku;
            }
        }
    }

    public function updatedPrice(): void
    {
        foreach ($this->variantsData as $index => $variant) {
            if (!isset($variant['price_manually_changed']) || !$variant['price_manually_changed']) {
                $this->variantsData[$index]['price'] = $this->price;
            }
        }
    }

    private function loadExistingAttributes($variants): void
    {
        $attributeGroups = [];
        foreach ($variants as $variant) {
            foreach ($variant->attributeValues as $attributeValue) {
                $attributeId = $attributeValue->attribute_id;
                if (!isset($attributeGroups[$attributeId])) {
                    $attributeGroups[$attributeId] = [
                        'attribute_id' => $attributeId,
                        'values' => [],
                    ];
                }
                if (!in_array($attributeValue->id, $attributeGroups[$attributeId]['values'])) {
                    $attributeGroups[$attributeId]['values'][] = $attributeValue->id;
                }
            }
        }
        $this->selectedAttributes = array_values($attributeGroups);
    }

    private function loadExistingVariants($variants): void
    {
        $this->existingVariants = $variants->pluck('id')->toArray();
        foreach ($variants as $variant) {
            $attributeValueIds = $variant->attributeValues->pluck('id')->toArray();
            $description = $variant->attributeValues->pluck('value')->implode(' / ') ?: 'Default';

            $this->variantsData[] = [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->price,
                'barcode' => $variant->barcode,
                'stock' => $variant->stock,
                'attribute_values' => $attributeValueIds,
                'description' => $description,
                'is_existing' => true,
            ];
        }
    }

    private function calculateCombinations(): array
    {
        $validAttributes = array_filter($this->selectedAttributes, fn($attr) => !empty($attr['attribute_id']) && !empty($attr['values']));
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

    private function generateVariantsPreview(): void
    {
        $combinations = $this->calculateCombinations();
        $newVariantsData = [];

        if (empty($combinations)) {
            $defaultVariant = collect($this->variantsData)->firstWhere('description', 'Default');
            $newVariantsData[] = [
                'id' => $defaultVariant['id'] ?? null,
                'sku' => $defaultVariant['sku'] ?? $this->generalSku,
                'price' => $defaultVariant['price'] ?? $this->price,
                'barcode' => $defaultVariant['barcode'] ?? '',
                'stock' => $defaultVariant['stock'] ?? 0,
                'attribute_values' => [],
                'description' => 'Default',
                'is_existing' => isset($defaultVariant['id']),
            ];
        } else {
            foreach ($combinations as $combination) {
                $attributeValues = AttributeValue::whereIn('id', $combination)->get();
                $description = $attributeValues->pluck('value')->implode(' / ');

                $existingVariant = collect($this->variantsData)->first(function ($variant) use ($combination) {
                    return ($variant['attribute_values'] ?? []) == $combination;
                });

                $newVariantsData[] = [
                    'id' => $existingVariant['id'] ?? null,
                    'sku' => $existingVariant['sku'] ?? $this->generalSku,
                    'price' => $existingVariant['price'] ?? $this->price,
                    'barcode' => $existingVariant['barcode'] ?? '',
                    'stock' => $existingVariant['stock'] ?? 0,
                    'attribute_values' => $combination,
                    'description' => $description,
                    'is_existing' => isset($existingVariant['id']),
                ];
            }
        }

        $this->variantsData = $newVariantsData;
    }

    public function save()
    {
        $this->validate();

        // Asegurar al menos una variante
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

        DB::transaction(function () {
            if ($this->isEditing) {
                $product = Product::findOrFail($this->productId);

                $product->update([
                    'name' => $this->name,
                    'description' => $this->description,
                    'price' => $this->price,
                    'category_id' => $this->category_id,
                ]);

                $currentVariantIds = collect($this->variantsData)
                    ->whereNotNull('id')
                    ->pluck('id')
                    ->toArray();

                $variantsToDelete = array_diff($this->existingVariants, $currentVariantIds);
                if (!empty($variantsToDelete)) {
                    Variant::whereIn('id', $variantsToDelete)->delete();
                }

                foreach ($this->variantsData as $variantData) {
                    $combinationIds = $variantData['attribute_values'] ?? [];
                    $sku = ($variantData['sku'] ?? '') !== '' ? $variantData['sku'] : ($this->generalSku !== '' ? $this->generalSku : null);

                    if (!empty($variantData['is_existing']) && !empty($variantData['id'])) {
                        $variant = Variant::find($variantData['id']);
                        $variant->update([
                            'sku' => $sku,
                            'price' => (isset($variantData['price']) && $variantData['price'] !== '') ? $variantData['price'] : $this->price,
                            'barcode' => ($variantData['barcode'] ?? '') !== '' ? $variantData['barcode'] : Variant::generateUniqueBarcode(),
                        ]);
                        $variant->attributeValues()->sync($combinationIds);
                    } else {
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
                }

                session()->flash('swalt', [
                    'icon' => 'success',
                    'title' => '¡Bien hecho!',
                    'text' => 'Producto ' . $this->name . ' ha sido actualizado',
                ]);
                // Actualiza el id para posibles redirecciones posteriores
                $this->productId = $product->id;
            } else {
                $product = Product::create([
                    'name' => $this->name,
                    'description' => $this->description,
                    'price' => $this->price,
                    'category_id' => $this->category_id,
                ]);

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

                session()->flash('swalt', [
                    'icon' => 'success',
                    'title' => '¡Bien hecho!',
                    'text' => 'Producto ' . $this->name . ' ha sido creado',
                ]);
                // Establece el id del nuevo producto para redirección posterior
                $this->productId = $product->id;
            }
        });

        $this->dispatch('product:saved', id: $this->productId);

        // Livewire maneja correctamente devolver un RedirectResponse.
        if ($this->redirectAfterSave && $this->productId) {
            return redirect()->route('admin.products.edit', $this->productId);
        }
    }

    public function render()
    {
        return view('livewire.admin.form.product-form', [
            'categories' => Category::all(),
            'product' => $this->isEditing ? Product::with('images')->find($this->productId) : null,
        ]);
    }
}
