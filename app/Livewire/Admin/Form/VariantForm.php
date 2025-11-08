<?php

namespace App\Livewire\Admin\Form;

use App\Models\Variant;
use Livewire\Component;

class VariantForm extends Component
{
    public ?Variant $variant = null;
    public ?int $variantId = null;

    // Campos editables
    public ?string $sku = null;
    public ?string $barcode = null;
    public float $price = 0.0;
    public ?float $stock = null; // Solo informativo si se usa por inventarios

    public bool $isEditing = true;

    public function mount(?int $variantId = null): void
    {
        $this->variantId = $variantId;
        if ($variantId) {
            $this->variant = Variant::with(['product', 'attributeValues', 'images'])->findOrFail($variantId);
            $this->sku = $this->variant->sku;
            $this->barcode = $this->variant->barcode;
            $this->price = (float) $this->variant->price;
            $this->stock = $this->variant->stock;
        }
    }

    public function rules(): array
    {
        return [
            'sku' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'numeric'],
        ];
    }

    public function generateBarcode(): void
    {
        $this->barcode = Variant::generateUniqueBarcode();
    }

    public function save(): void
    {
        $this->validate();

        if (!$this->variant) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No se encontró la variante a actualizar.',
            ]);
            return;
        }

        $data = [
            'sku' => $this->sku,
            'price' => $this->price,
            'barcode' => $this->barcode,
            'stock' => $this->stock,
        ];

        // Generar código si está vacío
        if (empty($data['barcode'])) {
            $data['barcode'] = Variant::generateUniqueBarcode();
            $this->barcode = $data['barcode'];
        }

        $this->variant->update($data);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Bien',
            'text' => 'Variante actualizada correctamente.',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.form.variant-form');
    }
}