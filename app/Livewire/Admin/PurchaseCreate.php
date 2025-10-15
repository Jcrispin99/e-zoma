<?php

namespace App\Livewire\Admin;

use App\Facades\Kardex;
use App\Models\Purchase;
use App\Models\Variant;
use Livewire\Component;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class PurchaseCreate extends Component
{
    public $voucher_type = 1;
    public $serie;

    public $correlative;

    public $date;

    public $purchase_order_id;

    public $warehouse_id;

    public $supplier_id;
    public $total = 0;
    public $observation;

    public $variant_id;
    public $variants = [];

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

    public function updated($property, $value)
    {
        if ($property == 'purchase_order_id') {
            $purchaseOrder = PurchaseOrder::find($value);
            if ($purchaseOrder) {

                $this->voucher_type = $purchaseOrder->voucher_type;
                $this->supplier_id = $purchaseOrder->supplier_id;

                $this->variants = $purchaseOrder->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'name' => $variant->product->name,
                        'quantity' => $variant->pivot->quantity,
                        'price' => $variant->pivot->price,
                        'subtotal' => $variant->pivot->subtotal,
                    ];
                })->toArray();
            }
        }
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
            'subtotal' => $lastRecord['cost'] * 1,
        ];
        $this->reset('variant_id');
    }

    public function save()
    {
        $this->validate(
            [
                'voucher_type' => 'required|in:1,2',
                'serie' => 'required|string|max:10',
                'correlative' => 'required|numeric|max:14',
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
            ],
            [],
            [
                'voucher_type' => 'tipo de comprobante',
                'supplier_id' => 'proveedor',
                'observation' => 'observación',
                'variants.*.id' => 'producto',
                'variants.*.quantity' => 'cantidad',
                'variants.*.price' => 'precio',
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

        $purchase = Purchase::create([
            'voucher_type' => $this->voucher_type,
            'serie' => $this->serie,
            'correlative' => $this->correlative,
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
        return view('livewire.admin.purchase-create');
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
