<?php

namespace App\Livewire\Admin\Qr;

use Livewire\Component;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Variant;

class QrGenerator extends Component
{
    public string $type;
    public int $id;

    public array $labels = [];
    public array $modalLabels = [];

    // Controles de diseño
    public int $qrSize = 200; // px
    public int $columns = 3;  // columnas por fila
    public bool $showPrice = true;
    public bool $showSku = true;
    public bool $showBarcodeText = true;

    // Modal de cantidades
    public bool $qtyOpen = false;

    public function mount(string $type, int $id)
    {
        $this->type = $type;
        $this->id = $id;

        $variants = $this->loadVariants($type, $id);

        $this->labels = $variants->map(function (Variant $variant) {
            $description = $variant->attributeValues->pluck('value')->implode(' / ') ?: 'Default';
            return [
                'id' => $variant->id,
                'product_name' => optional($variant->product)->name,
                'description' => $description,
                'full_name' => method_exists($variant, 'getFullNameAttribute') ? $variant->fullName : trim((optional($variant->product)->name . ' ' . $description)),
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'price' => $variant->price,
                'payload' => $this->makeQrData($variant),
                'qty' => 1,
            ];
        })->toArray();
    }

    private function loadVariants(string $type, int $id)
    {
        if ($type === 'product') {
            $product = Product::with(['variants.attributeValues', 'variants.product'])->findOrFail($id);
            return $product->variants;
        }
        if ($type === 'purchase') {
            $purchase = Purchase::with(['variants.attributeValues', 'variants.product'])->findOrFail($id);
            return $purchase->variants;
        }
        abort(404);
    }

    private function makeQrData(Variant $variant)
    {
        return $variant->barcode ?: ($variant->sku ?? (string) $variant->id);
    }

    public function inc(int $index): void
    {
        if (!isset($this->modalLabels[$index])) return;
        $current = (int) ($this->modalLabels[$index]['qty'] ?? 0);
        $this->modalLabels[$index]['qty'] = max(0, $current + 1);
    }

    public function dec(int $index): void
    {
        if (!isset($this->modalLabels[$index])) return;
        $current = (int) ($this->modalLabels[$index]['qty'] ?? 0);
        $this->modalLabels[$index]['qty'] = max(0, $current - 1);
    }

    public function setAllZero(): void
    {
        foreach ($this->modalLabels as $index => $label) {
            $this->modalLabels[$index]['qty'] = 0;
        }
    }

    public function saveQty(): void
    {
        $this->labels = $this->modalLabels;
        $this->closeQty();
    }

    public function openQty(): void
    {
        $this->modalLabels = $this->labels;
        $this->qtyOpen = true;
    }

    public function closeQty(): void
    {
        $this->qtyOpen = false;
        $this->modalLabels = [];
    }

    public function render()
    {
        return view('livewire.admin.qr.qr-generator');
    }
}
