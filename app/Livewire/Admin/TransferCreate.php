<?php

namespace App\Livewire\Admin;

use App\Facades\Kardex;
use App\Models\Variant;
use Livewire\Component;
use App\Models\Transfer;

class TransferCreate extends Component
{
    public $serie = 'T001';
    public $correlative;

    public $date;
    public $warehouse_id;
    public $origin_warehouse_id;
    public $destination_warehouse_id;

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
        $this->correlative = Transfer::max('correlative') + 1;
    }
    public function updated($property, $value)
    {
        if ($property == 'origin_warehouse_id') {
            $this->reset('destination_warehouse_id');
        }
    }

    public function addProduct()
    {
        $this->validate([
            'variant_id' => 'required|exists:variants,id',
            'origin_warehouse_id' => 'required|exists:warehouses,id',
            'destination_warehouse_id' => 'required|exists:warehouses,id|different:origin_warehouse_id',
        ], [], [
            'variant_id' => 'producto',
            'origin_warehouse_id' => 'almacen origen',
            'destination_warehouse_id' => 'almacen destino',
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
        $lastRecord = Kardex::getLastRecord($variant->id, $this->origin_warehouse_id);


        $this->variants[] = [
            'id' => $variant->id,
            'name' => $variant->product->name,
            'quantity' => 1,
            'price' => $lastRecord['cost'],
            'subtotal' => $lastRecord['cost'],
        ];
        $this->reset('variant_id');
    }

    public function save()
    {
        $this->validate(
            [
                'serie' => 'required|string|max:10',
                'correlative' => 'required|numeric|min:1',
                'date' => 'nullable|date',
                'origin_warehouse_id' => 'required|exists:warehouses,id',
                //destino diferente de origen
                'destination_warehouse_id' => 'required|exists:warehouses,id|different:origin_warehouse_id',
                'total' => 'required|numeric|min:0',
                'observation' => 'nullable|string|max:255',
                'variants' => 'required|array|min:1',
                'variants.*.id' => 'required|exists:variants,id',
                'variants.*.quantity' => 'required|numeric|min:1',
                'variants.*.price' => 'required|numeric|min:0',
            ],
            [],
            [
                'serie' => 'serie',
                'correlative' => 'correlativo',
                'date' => 'fecha',
                'origin_warehouse_id' => 'almacen origen',
                'destination_warehouse_id' => 'almacen destino',
                'total' => 'total',
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

        $transfer = Transfer::create([
            'serie' => $this->serie,
            'correlative' => $this->correlative,
            'date' => $this->date ?? now(),
            'origin_warehouse_id' => $this->origin_warehouse_id,
            'destination_warehouse_id' => $this->destination_warehouse_id,
            'total' => $this->total,
            'observation' => $this->observation,
            'company_id' => $activeCompanyId,
        ]);

        foreach ($this->variants as $variant) {
            $transfer->variants()->attach($variant['id'], [
                'quantity' => $variant['quantity'],
                'price' => $variant['price'],
                'subtotal' => $variant['quantity'] * $variant['price'],
            ]);

            // registrar salida
            Kardex::registerExit(
                $transfer,
                $variant,
                $this->origin_warehouse_id,
                'Transferencia'
            );
            // registrar entrada
            Kardex::registerEntry(
                $transfer,
                $variant,
                $this->destination_warehouse_id,
                'Transferencia'
            );
        }

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Transferencia creada exitosamente.',
        ]);

        return redirect()->route('admin.transfers.index');
    }

    public function render()
    {
        return view('livewire.admin.transfer-create');
    }
}
