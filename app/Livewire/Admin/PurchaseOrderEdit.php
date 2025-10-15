<?php

namespace App\Livewire\Admin;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Variant;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PurchaseOrderEdit extends Component
{
    public PurchaseOrder $purchaseOrder;

    public $voucher_type = 1;
    public $serie = 'A';
    public $correlative;

    public $date;
    public $supplier_id;
    public $total = 0;
    public $observation;

    public $variant_id;
    public $variants = [];

    public function boot()
    {
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

    public function mount(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder->load('variants.product', 'variants.attributeValues');

        // Pre-cargar datos base
        $this->voucher_type = $purchaseOrder->voucher_type;
        $this->serie = $purchaseOrder->serie;
        $this->correlative = $purchaseOrder->correlative;
        $this->date = optional($purchaseOrder->date)->format('Y-m-d');
        $this->supplier_id = $purchaseOrder->supplier_id;
        $this->observation = $purchaseOrder->observation;

        // Mapear líneas existentes a estructura de variants del form
        $this->variants = $purchaseOrder->variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'name' => $variant->fullName,
                'quantity' => $variant->pivot->quantity,
                'price' => $variant->pivot->price,
                'subtotal' => $variant->pivot->subtotal,
            ];
        })->toArray();



        // Calcular total inicial desde las líneas
        $this->total = collect($this->variants)
            ->reduce(fn($carry, $v) => $carry + ($v['quantity'] * $v['price']), 0);
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
        $this->variants[] = [
            'id' => $variant->id,
            'name' => $variant->product->name,
            'quantity' => 1,
            'price' => 0,
            'subtotal' => 0,
        ];
        $this->reset('variant_id');
    }

    public function save()
    {
        if ($this->purchaseOrder->status === 'cancelled') {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No permitido',
                'text' => 'No se puede editar una orden de compra cancelada.',
            ]);
            return redirect()->route('admin.purchases-orders.index');
        }

        $this->validate(
            [
                'voucher_type' => 'required|in:1,2',
                'date' => 'nullable|date',
                'supplier_id' => 'required|exists:suppliers,id',
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

        // Actualizar datos base (serie/correlativo permanecen sin cambios)
        $this->purchaseOrder->update([
            'voucher_type' => $this->voucher_type,
            'date' => $this->date ?? now(),
            'supplier_id' => $this->supplier_id,
            'total' => $this->total,
            'observation' => $this->observation,
        ]);

        // Sincronizar líneas de la OC
        $syncData = [];
        foreach ($this->variants as $variant) {
            $syncData[$variant['id']] = [
                'quantity' => $variant['quantity'],
                'price' => $variant['price'],
                'subtotal' => $variant['quantity'] * $variant['price'],
            ];
        }
        $this->purchaseOrder->variants()->sync($syncData);

        // Recalcular cantidad ordenada desde pivot
        $orderedQty = DB::table('variantables')
            ->where('variantable_type', PurchaseOrder::class)
            ->where('variantable_id', $this->purchaseOrder->id)
            ->sum('quantity');
        $this->purchaseOrder->update([
            'ordered_qty_total' => (float) $orderedQty,
        ]);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Orden de compra actualizada exitosamente.',
        ]);

        return redirect()->route('admin.purchases-orders.index');
    }

    public function render()
    {
        return view('livewire.admin.purchase-order-edit');
    }
}
