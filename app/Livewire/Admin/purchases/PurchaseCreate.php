<?php

namespace App\Livewire\Admin\purchases;

use App\Facades\Kardex;
use App\Models\Purchase;
use App\Models\Journal;
use App\Models\Variant;
use App\Models\Warehouse;
use Livewire\Component;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use App\Services\SequenceService;

class PurchaseCreate extends Component
{
    public $journals = [];
    public $journal_id;
    public $correlative;

    public $date;

    public $purchase_order_id;

    public $warehouse_id;

    public $supplier_id;
    public $total = 0;
    public $observation;

    public $variant_id;
    public $variants = [];

    public function mount($purchase_order_id = null)
    {
        $this->purchase_order_id = $purchase_order_id;
        $this->date = now()->format('Y-m-d');

        // 1. Cargar journals de tipo 'purchase'
        $this->journals = Journal::where('type', 'purchase')
            ->with('sequence')
            ->orderBy('name')
            ->get();

        // 2. Establecer el primer journal como predeterminado y actualizar la vista previa
        $journalsCol = collect($this->journals);
        if ($journalsCol->isNotEmpty()) {
            $first = $journalsCol->first();
            $this->journal_id = $first ? $first->id : null;
            $this->updatePreview();
        }


        if ($this->purchase_order_id) {
            $purchaseOrder = PurchaseOrder::find($this->purchase_order_id);
            if ($purchaseOrder) {
                $this->supplier_id = $purchaseOrder->supplier_id;
                // El modelo PurchaseOrder no tiene warehouse_id, se asignará más abajo.

                $purchaseOrder->load('supplier', 'variants');

                $this->variants = $purchaseOrder->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'name' => $variant->fullName,
                        'quantity' => $variant->pivot->quantity,
                        'price' => $variant->pivot->price,
                        'tax_rate' => $variant->pivot->tax_rate,
                        'subtotal' => $variant->pivot->subtotal,
                    ];
                })->toArray();
            }
        }

        // Si después de la lógica anterior no hay un almacén,
        // se asigna el primero de la compañía activa.
        if (!$this->warehouse_id) {
            $activeCompanyId = session('active_company_id');

            if ($activeCompanyId) {
                $firstWarehouse = Warehouse::where('company_id', $activeCompanyId)
                    ->orderBy('id', 'asc')
                    ->first();
                $this->warehouse_id = $firstWarehouse?->id;
            }
        }
    }

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

    public function updatedJournalId()
    {
        $this->updatePreview();
    }

    public function addProduct()
    {
        $this->validate([
            'variant_id' => 'required|exists:variants,id',
            'warehouse_id' => 'required|exists:warehouses,id',
        ], [], [
            'variant_id' => 'producto',
            'warehouse_id' => 'almacén',
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
        $lastRecord = Kardex::getLastRecord($variant->id, $this->warehouse_id);


        $this->variants[] = [
            'id' => $variant->id,
            'name' => $variant->fullName,
            'quantity' => 1,
            'price' => $lastRecord['cost'],
            'tax_rate' => 18,
            'subtotal' => $lastRecord['cost'] * 1,
        ];
        $this->reset('variant_id');
    }

    public function scanBarcode($code = null)
    {
        $code = trim($code ?? '');
        if ($code === '') {
            return;
        }

        $variant = Variant::where('barcode', $code)
            ->orWhere('sku', $code)
            ->first();

        if (! $variant) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Código no encontrado',
                'text' => 'No se encontró ningún producto para ese código o SKU.',
            ]);
            return;
        }

        // Si ya existe en la tabla, incrementar cantidad
        foreach ($this->variants as $index => $row) {
            if (($row['id'] ?? null) === $variant->id) {
                $current = (int) ($this->variants[$index]['quantity'] ?? 0);
                $this->variants[$index]['quantity'] = $current + 1;
                // Actualizar subtotal si existe la propiedad
                if (array_key_exists('subtotal', $this->variants[$index])) {
                    $price = (float) ($this->variants[$index]['price'] ?? 0);
                    $qty = (int) ($this->variants[$index]['quantity'] ?? 0);
                    $this->variants[$index]['subtotal'] = $price * $qty;
                }
                return;
            }
        }

        // Caso contrario, usar la lógica existente de addProduct
        $this->variant_id = $variant->id;
        $this->addProduct();
        $this->reset('variant_id');
    }

    public function save()
    {
        $this->validate(
            [
                'journal_id' => 'required|exists:journals,id',
                'date' => 'nullable|date',
                'purchase_order_id' => 'nullable|exists:purchase_orders,id',
                'supplier_id' => 'required|exists:suppliers,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'total' => 'required|numeric|min:0',
                'observation' => 'nullable|string|max:255',
                'variants' => 'required|array|min:1',
                'variants.*.id' => 'required|exists:variants,id',
                'variants.*.quantity' => 'required|numeric|min:1',
                'variants.*.price' => 'required|numeric|min:0',
                'variants.*.tax_rate' => 'required|numeric|min:0',
            ],
            [],
            [
                'journal_id' => 'serie',
                'supplier_id' => 'proveedor',
                'observation' => 'observación',
                'variants.*.id' => 'producto',
                'variants.*.quantity' => 'cantidad',
                'variants.*.price' => 'precio',
                'variants.*.tax_rate' => 'IGV',
            ]
        );

        $activeCompanyId = session('active_company_id');

        if (!$activeCompanyId) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No hay una compañía activa seleccionada. Por favor, seleccione una compañía antes de crear una compra.',
            ]);
            return redirect()->back();
        }

        // 3. Obtener serie y correlativo del servicio
        $parts = app(SequenceService::class)->getNextParts($this->journal_id);

        $purchase = Purchase::create([
            'journal_id' => $this->journal_id,
            'serie' => $parts['serie'],
            'correlative' => $parts['correlative'],
            'date' => $this->date ?? now(),
            'purchase_order_id' => $this->purchase_order_id,
            'supplier_id' => $this->supplier_id,
            'warehouse_id' => $this->warehouse_id,
            'total' => $this->total,
            'observation' => $this->observation,
            'company_id' => $activeCompanyId,
        ]);

        foreach ($this->variants as $variant) {
            $purchase->variants()->attach($variant['id'], [
                'quantity' => $variant['quantity'],
                'price' => $variant['price'],
                'tax_rate' => $variant['tax_rate'],
                'subtotal' => $variant['quantity'] * $variant['price'],
            ]);

            //Kardex
            Kardex::registerEntry($purchase, $variant, $this->warehouse_id, 'Compra');
        }

        // Marcar la compra como publicada (posted) y estado de pago inicial
        $purchase->update([
            'status' => 'posted',
            // Si no está definido, dejar en 'unpaid' como estado inicial
            'payment_status' => $purchase->payment_status ?? 'unpaid',
        ]);

        // Recalcular métricas de la OC relacionada (si aplica)
        if ($purchase->purchase_order_id) {
            $po = $purchase->purchaseOrder;
            if ($po) {
                $this->recalcPoMetrics($po);
            }
        }
        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'La compra fue creada exitosamente.',
        ]);

        return redirect()->route('admin.purchases.index');
    }

    public function render()
    {
        return view('livewire.admin.purchases.purchase-create');
    }

    // 4. Añadir método para previsualizar el correlativo
    protected function updatePreview()
    {
        if (!$this->journal_id) {
            $this->correlative = '';
            return;
        }

        $journal = collect($this->journals)->firstWhere('id', $this->journal_id);
        $sequence = $journal?->sequence;

        // Previsualizar correlativo sin consumir la secuencia
        $this->correlative = $sequence ? str_pad($sequence->next_number, $sequence->sequence_size, '0', STR_PAD_LEFT) : '';
    }

    /**
     * Recalcular totales e indicadores de facturación de la Orden de Compra.
     */
    private function recalcPoMetrics(PurchaseOrder $po): void
    {
        // Cantidad ordenada (suma de líneas del PO)
        $orderedQty = DB::table('variantables')
            ->where('variantable_type', PurchaseOrder::class)
            ->where('variantable_id', $po->id)
            ->sum('quantity');

        // Cantidad facturada (suma de líneas de compras no canceladas asociadas al PO)
        $billedQty = DB::table('variantables')
            ->join('purchases', 'variantables.variantable_id', '=', 'purchases.id')
            ->where('variantables.variantable_type', Purchase::class)
            ->where('purchases.purchase_order_id', $po->id)
            ->where('purchases.status', '<>', 'cancelled')
            ->sum('variantables.quantity');

        // Cantidad de compras (excluyendo canceladas)
        $purchasesCount = Purchase::query()
            ->where('purchase_order_id', $po->id)
            ->where('status', '<>', 'cancelled')
            ->count();

        $billingStatus = $billedQty <= 0
            ? 'none'
            : ($billedQty < $orderedQty ? 'partial' : 'complete');

        $po->update([
            'ordered_qty_total' => (float) $orderedQty,
            'billed_qty_total' => (float) $billedQty,
            'purchases_count' => $purchasesCount,
            'billing_status' => $billingStatus,
            'billed_at' => $billingStatus === 'complete' ? now() : null,
        ]);
    }
}
