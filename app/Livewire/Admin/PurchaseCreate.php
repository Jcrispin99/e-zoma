<?php

namespace App\Livewire\Admin;

use App\Models\Inventory;
use App\Models\Purchase;
use App\Models\Variant;
use Livewire\Component;
use App\Models\PurchaseOrder;

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
        ]);

        foreach ($this->variants as $variant) {
            $purchase->variants()->attach($variant['id'], [
                'quantity' => $variant['quantity'],
                'price' => $variant['price'],
                'subtotal' => $variant['quantity'] * $variant['price'],
            ]);

            //Kardex
            $lastRecord = Inventory::where('variant_id', $variant['id'])
                ->where('warehouse_id', $this->warehouse_id)
                ->latest('id')
                ->first();
            $lastQuantityBalance = $lastRecord ? $lastRecord->quantity_balance : 0;
            $lastTotalBalance = $lastRecord ? $lastRecord->total_balance : 0;

            $newQuantityBalance = $lastQuantityBalance + $variant['quantity'];
            $newTotalBalance = $lastTotalBalance + ($variant['quantity'] * $variant['price']);
            $newCostBalance = $newTotalBalance / ($newQuantityBalance ?: 1);

            Inventory::create([
                'detail' => 'Compra',
                'quantity_in' => $variant['quantity'],
                'cost_in' => $variant['price'],
                'total_in' => $variant['quantity'] * $variant['price'],
                'quantity_balance' => $newQuantityBalance,
                'cost_balance' => $newCostBalance,
                'total_balance' => $newTotalBalance,
                'variant_id' => $variant['id'],
                'warehouse_id' => $this->warehouse_id,
                'inventoryable_id' => $purchase->id,
                'inventoryable_type' => Purchase::class,
            ]);
            // $purchase->inventories()->create([
            //     'detail' => 'Compra',
            //     'quantity_in' => $variant['quantity'],
            //     'cost_in' => $variant['price'],
            //     'total_in' => $variant['quantity'] * $variant['price'],
            //     'quantity_balance' => $newQuantityBalance,
            //     'cost_balance' => $newCostBalance,
            //     'total_balance' => $newTotalBalance,
            //     'variant_id' => $variant['id'],
            //     'warehouse_id' => $this->warehouse_id,
            // ]);
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
}
