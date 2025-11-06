<?php

namespace App\Livewire\Admin\purchaseOrders;

use App\Models\Journal;
use App\Models\Tax;
use App\Models\Variant;
use Livewire\Component;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use App\Services\SequenceService;

class PurchaseOrderCreate extends Component
{
    public $journals = [];
    public $journal_id;
    public $correlative;

    public $date;
    public $supplier_id;
    public $total = 0;
    public $observation;

    public $variant_id;
    public $variants = [];
    public $taxes = [];

    public function boot()
    {
        //Verificar si hay errores de validación previos
        $this->withValidator(function ($validator) {
            if ($validator->fails()) {

                $errors = $validator->errors()->toArray();

                $html = "<ul class='text-left'>";

                foreach ($errors as $error) {
                    $html .= "<li>{$error[0]}</li>";
                }

                $html .= "</ul>";

                $this->dispatch('swal', [
                    'icon' => 'error',
                    'title' => 'Error de validación',
                    'html' => $html,
                ]);
            }
        });
    }
    public function mount()
    {
        $this->date = now()->format('Y-m-d');

        // 1. Cargar journals de tipo 'purchase-order'
        $this->journals = Journal::where('type', 'purchase-order') // Asegúrate de tener journals de este tipo en tu seeder
            ->with('sequence')
            ->orderBy('name')
            ->get();

        // Cargar impuestos activos desde BD para la vista
        $this->taxes = Tax::active()
            ->orderBy('name')
            ->get(['id', 'name', 'invoice_label', 'rate_percent', 'is_price_inclusive'])
            ->toArray();

        $journalsCol = collect($this->journals);
        if ($journalsCol->isNotEmpty()) {
            $first = $journalsCol->first();
            // 2. Establecer el primer journal y actualizar la vista previa del correlativo
            $this->journal_id = $first ? $first->id : null;
            $this->updatePreview();
        }
    }

    public function addProduct()
    {
        $this->validate([
            'variant_id' => 'required|exists:variants,id',
        ], [], [
            'variant_id' => 'producto',
        ]);

        $existing = collect($this->variants)->firstWhere('id', $this->variant_id);

        if ($existing) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'El producto ya fue agregado',
                'text' => 'El producto ya se encuentra en la fila',
            ]);
            return;
        }

        $variant = Variant::with('product')->find($this->variant_id);

        // Impuesto por defecto (marcado en BD) o el primero activo
        $defaultTax = Tax::where('is_active', true)->where('is_default', true)->first();
        if (!$defaultTax) {
            $defaultTax = Tax::active()->orderBy('name')->first();
        }

        $this->variants[] = [
            'id' => $variant->id,
            'name' => $variant->fullName,
            'quantity' => 1,
            'price' => 0,
            'tax_id' => optional($defaultTax)->id,
            'tax_rate' => optional($defaultTax)->rate_percent ?? 0,
            'tax_inclusive' => (bool) (optional($defaultTax)->is_price_inclusive ?? false),
            'subtotal' => 0,
        ];
        $this->reset('variant_id');
    }

    /**
     * Escanea un código (barcode o SKU) y agrega/incrementa en la tabla.
     */
    public function scanBarcode($code = null)
    {
        $code = trim((string) ($code ?? ''));
        if ($code === '') {
            return;
        }

        $variant = Variant::with('product')
            ->where('barcode', $code)
            ->orWhere('sku', $code)
            ->first();

        if (!$variant) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Producto no encontrado',
                'text' => 'No se encontró un producto con ese código.',
            ]);
            return;
        }

        $existingIndex = collect($this->variants)->search(function ($v) use ($variant) {
            return ($v['id'] ?? null) === $variant->id;
        });

        if ($existingIndex !== false) {
            $this->variants[$existingIndex]['quantity'] = (int) ($this->variants[$existingIndex]['quantity'] ?? 0) + 1;
        } else {
            $defaultTax = Tax::where('is_active', true)->where('is_default', true)->first();
            if (!$defaultTax) {
                $defaultTax = Tax::active()->orderBy('name')->first();
            }

            $this->variants[] = [
                'id' => $variant->id,
                'name' => $variant->fullName,
                'quantity' => 1,
                'price' => 0,
                'tax_id' => optional($defaultTax)->id,
                'tax_rate' => optional($defaultTax)->rate_percent ?? 0,
                'tax_inclusive' => (bool) (optional($defaultTax)->is_price_inclusive ?? false),
                'subtotal' => 0,
            ];
        }
    }

    public function updatedJournalId()
    {
        $this->updatePreview();
    }

    protected function updatePreview()
    {
        if (!$this->journal_id) {
            $this->correlative = '';
            return;
        }

        $journal = collect($this->journals)->first(function ($j) {
            if (is_array($j)) {
                return ($j['id'] ?? null) == $this->journal_id;
            }
            return ($j->id ?? null) == $this->journal_id;
        });

        // Obtener datos de secuencia según sea array u objeto
        if (is_array($journal)) {
            $sequence = $journal['sequence'] ?? null;
            $next = $sequence['next_number'] ?? null;
            $size = $sequence['sequence_size'] ?? null;
        } else {
            $sequence = $journal ? $journal->sequence : null;
            $next = $sequence ? $sequence->next_number : null;
            $size = $sequence ? $sequence->sequence_size : null;
        }

        if (!$next || !$size) {
            $this->correlative = '';
            return;
        }

        // Previsualizar correlativo sin consumir la secuencia
        $this->correlative = str_pad((string)$next, (int)$size, '0', STR_PAD_LEFT);
    }

    public function save()
    {
        $this->validate(
            [
                'journal_id' => 'required|exists:journals,id',
                'date' => 'nullable|date',
                'supplier_id' => 'required|exists:suppliers,id',
                'total' => 'required|numeric|min:0',
                'observation' => 'nullable|string|max:255',
                'variants' => 'required|array|min:1',
                'variants.*.id' => 'required|exists:variants,id',
                'variants.*.quantity' => 'required|numeric|min:1',
                'variants.*.price' => 'required|numeric|min:0',
                'variants.*.tax_id' => 'required|exists:taxes,id',
            ],
            [],
            [
                'journal_id' => 'serie',
                'supplier_id' => 'proveedor',
                'observation' => 'observación',
                'variants.*.id' => 'producto',
                'variants.*.quantity' => 'cantidad',
                'variants.*.price' => 'precio',
                'variants.*.tax_id' => 'impuesto',
            ]
        );

        $activeCompanyId = session('active_company_id');

        if (!$activeCompanyId) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No hay una compañía activa seleccionada. Por favor, seleccione una compañía antes de crear una orden de compra.',
            ]);
            return;
        }

        $totalCalculado = 0;
        $linesPivot = [];
        foreach ($this->variants as $variant) {
            $qty = (float) $variant['quantity'];
            $price = (float) $variant['price'];

            $tax = Tax::find($variant['tax_id']);
            $rate = (float) optional($tax)->rate_percent ?? 0.0;
            $inclusive = (bool) optional($tax)->is_price_inclusive ?? false;

            $gross = $qty * $price;
            if ($inclusive && $rate > 0) {
                $base = $gross / (1 + ($rate / 100));
                $taxAmount = $gross - $base;
                $lineTotal = $gross;
            } else {
                $base = $gross;
                $taxAmount = $base * ($rate / 100);
                $lineTotal = $base + $taxAmount;
            }

            $totalCalculado += $lineTotal;

            $linesPivot[] = [
                'variant_id' => $variant['id'],
                'quantity' => $qty,
                'price' => $price,
                'tax_rate' => $rate,
                'subtotal' => $base,
            ];
        }

        // Obtener serie y correlativo con consumo de secuencia
        $parts = app(SequenceService::class)->getNextParts($this->journal_id);

        $purchaseOrder = PurchaseOrder::create([
            'serie' => $parts['serie'], // 3. Usar la serie del servicio
            'correlative' => $parts['correlative'],
            'date' => $this->date ?? now(),
            'supplier_id' => $this->supplier_id,
            'total' => $totalCalculado, // Usar el total calculado en el backend
            'observation' => $this->observation,
            'company_id' => $activeCompanyId,
            'journal_id' => $this->journal_id,
        ]);

        foreach ($linesPivot as $line) {
            $purchaseOrder->variants()->attach($line['variant_id'], [
                'quantity' => $line['quantity'],
                'price' => $line['price'],
                'tax_rate' => $line['tax_rate'],
                'subtotal' => $line['subtotal'],
            ]);
        }

        // Calcular cantidad ordenada desde las líneas (pivot) y confirmar la OC
        $orderedQty = DB::table('variantables')
            ->where('variantable_type', PurchaseOrder::class)
            ->where('variantable_id', $purchaseOrder->id)
            ->sum('quantity');

        $purchaseOrder->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'ordered_qty_total' => (float) $orderedQty,
        ]);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Orden de compra creada exitosamente.',
        ]);

        return redirect()->route('admin.purchases-orders.edit', $purchaseOrder);
    }

    public function render()
    {
        return view('livewire.admin.purchaseOrders.purchase-order-create');
    }
}
