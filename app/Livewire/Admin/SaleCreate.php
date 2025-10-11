<?php

namespace App\Livewire\Admin;

use App\Facades\Kardex;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\Variant;
use Livewire\Component;
use App\Models\Quote;
use App\Services\KardexServices;

class SaleCreate extends Component
{
    public $voucher_type = 1;
    public $serie = 'F001';

    public $correlative = 0;

    public $date;

    public $quote_id;

    public $warehouse_id;

    public $customer_id;
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
        $this->correlative = Quote::max('correlative') + 1;
    }


    public function updated($property, $value)
    {
        if ($property == 'quote_id') {
            $quote = Quote::find($value);
            if ($quote) {

                $this->voucher_type = $quote->voucher_type;
                $this->customer_id = $quote->customer_id;

                $this->variants = $quote->variants->map(function ($variant) {
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
            'name' => $variant->fullName,
            'quantity' => 1,
            'price' => $variant->price,
            'subtotal' => $variant->price,
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
                'quote_id' => 'nullable|exists:quotes,id',
                'customer_id' => 'required|exists:customers,id',
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
                'customer_id' => 'cliente',
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
                'text' => 'No hay una compañía activa seleccionada. Por favor, seleccione una compañía antes de crear una venta.',
            ]);
            return redirect()->back();
        }

        $sale = Sale::create([
            'voucher_type' => $this->voucher_type,
            'serie' => $this->serie,
            'correlative' => $this->correlative,
            'date' => $this->date ?? now(),
            'quote_id' => $this->quote_id,
            'customer_id' => $this->customer_id,
            'warehouse_id' => $this->warehouse_id,
            'total' => $this->total,
            'observation' => $this->observation,
            'company_id' => $activeCompanyId,
        ]);

        foreach ($this->variants as $variant) {
            $sale->variants()->attach($variant['id'], [
                'quantity' => $variant['quantity'],
                'price' => $variant['price'],
                'subtotal' => $variant['quantity'] * $variant['price'],
            ]);

            //Kardex
            Kardex::registerExit($sale, $variant, $this->warehouse_id, 'Venta');
        }

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'LA venta creada exitosamente.',
        ]);

        return redirect()->route('admin.sales.index');
    }

    public function render()
    {
        return view('livewire.admin.sale-create');
    }
}
