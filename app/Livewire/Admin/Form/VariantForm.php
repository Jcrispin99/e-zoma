<?php

namespace App\Livewire\Admin\Form;

use Livewire\Component;
use App\Models\Variant;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class VariantForm extends Component
{
    public ?int $variantId = null;
    public bool $isEditing = true;
    public bool $redirectAfterSave = true;

    public string $productName = '';
    public string $sku = '';
    public string $barcode = '';
    public string $price = '';

    protected function rules(): array
    {
        return [
            'sku' => ['nullable', 'string'],
            'barcode' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function mount(?int $variantId = null): void
    {
        if ($variantId) {
            $this->variantId = $variantId;
            $variant = Variant::with(['product', 'images'])->findOrFail($variantId);
            $this->productName = $variant->product ? $variant->product->name : '';
            $this->sku = (string)($variant->sku ?? '');
            $this->barcode = (string)($variant->barcode ?? '');
            $this->price = (string)($variant->price ?? '');
            $this->isEditing = true;
        } else {
            $this->isEditing = false;
        }
    }

    public function save()
    {
        $this->validate();

        if (!$this->variantId) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No se encontró la variante para actualizar.',
            ]);
            return redirect()->route('admin.variants.index');
        }

        DB::transaction(function () {
            $variant = Variant::findOrFail($this->variantId);

            $barcode = trim($this->barcode) !== '' ? $this->barcode : Variant::generateUniqueBarcode();

            $variant->update([
                'sku' => $this->sku !== '' ? $this->sku : $variant->sku,
                'barcode' => $barcode,
                'price' => $this->price !== '' ? $this->price : $variant->price,
            ]);

            session()->flash('swalt', [
                'icon' => 'success',
                'title' => 'Bien',
                'text' => 'Variant actualizado correctamente.',
            ]);
        });

        if ($this->redirectAfterSave) {
            return redirect()->route('admin.variants.index');
        }

        return null;
    }

    public function render()
    {
        $variant = null;
        if ($this->isEditing && $this->variantId) {
            $variant = Variant::with(['product', 'images'])->find($this->variantId);
        }

        return view('livewire.admin.form.variant-form', [
            'variant' => $variant,
        ]);
    }
}