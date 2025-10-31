<?php

namespace App\Livewire\Admin\Qr;

use Livewire\Component;
use App\Models\Product;
use App\Models\QrStyle;
use App\Models\Purchase;
use App\Models\Variant;

class QrGenerator extends Component
{
    public string $type;
    public int $id;

    public array $labels = [];
    public array $modalLabels = [];

    // Estilos de QR
    public $styles;
    public $selectedStyleId;

    // Controles de diseño
    public int $qrSize;
    public string $layout_type;
    public int $label_width;
    public int $label_height;
    public bool $show_product_name;
    public bool $show_description;
    public bool $showPrice;
    public bool $showSku;
    public bool $showBarcodeText;

    // Modal de cantidades
    public bool $qtyOpen = false;

    public function mount(string $type, int $id)
    {
        $this->type = $type;
        $this->id = $id;

        $this->styles = QrStyle::all();
        $defaultStyle = $this->styles->where('is_default', true)->first() ?? $this->styles->first();
        $this->applyStyle($defaultStyle);

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

    public function updatedSelectedStyleId($styleId): void
    {
        $style = $this->styles->find($styleId);
        if ($style) {
            $this->applyStyle($style);
        }
    }

    private function applyStyle(?QrStyle $style): void
    {
        $this->selectedStyleId = $style?->id;
        $this->layout_type = $style?->layout_type ?? 'default';
        $this->label_width = $style?->label_width ?? 50;
        $this->label_height = $style?->label_height ?? 50;
        $this->qrSize = $style?->qr_size ?? 200;
        $this->show_product_name = $style?->show_product_name ?? true;
        $this->show_description = $style?->show_description ?? true;
        $this->showPrice = $style?->show_price ?? true;
        $this->showSku = $style?->show_sku ?? true;
        $this->showBarcodeText = $style?->show_barcode_text ?? true;
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
