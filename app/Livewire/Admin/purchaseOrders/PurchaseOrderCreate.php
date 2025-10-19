<?php

namespace App\Livewire\Admin\purchaseOrders;

use App\Models\Variant;
use Livewire\Component;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class PurchaseOrderCreate extends Component
{
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
        $this->correlative = PurchaseOrder::max('correlative') + 1;
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
            'name' => $variant->fullName,
            'quantity' => 1,
            'price' => 0,
            'tax_rate' => 0, // Valor inicial para el impuesto
            'subtotal' => 0,
        ];
        $this->reset('variant_id');
    }

    public function save()
    {
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
                'variants.*.tax_rate' => 'required|numeric|in:0,10,18', // Validar impuesto
            ],
            [],
            [
                'voucher_type' => 'tipo de comprobante',
                'supplier_id' => 'proveedor',
                'observation' => 'observación',
                'variants.*.id' => 'producto',
                'variants.*.quantity' => 'cantidad',
                'variants.*.price' => 'precio',
                'variants.*.tax_rate' => 'impuesto', // Mensaje para impuesto
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

        // Calcular el total en el backend para seguridad
        $totalCalculado = 0;
        foreach ($this->variants as $variant) {
            $subtotal = $variant['quantity'] * $variant['price'];
            $totalCalculado += $subtotal * (1 + $variant['tax_rate'] / 100);
        }

        $purchaseOrder = PurchaseOrder::create([
            'voucher_type' => $this->voucher_type,
            'serie' => $this->serie,
            'correlative' => $this->correlative,
            'date' => $this->date ?? now(),
            'supplier_id' => $this->supplier_id,
            'total' => $totalCalculado, // Usar el total calculado en el backend
            'observation' => $this->observation,
            'company_id' => $activeCompanyId,
        ]);

        foreach ($this->variants as $variant) {
            $purchaseOrder->variants()->attach($variant['id'], [
                'quantity' => $variant['quantity'],
                'price' => $variant['price'],
                'tax_rate' => $variant['tax_rate'], // Guardar el impuesto
                'subtotal' => $variant['quantity'] * $variant['price'],
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

        return redirect()->route('admin.purchases-orders.index');
    }

    public function render()
    {
        return view('livewire.admin.purchaseOrders.purchase-order-create');
    }
}
