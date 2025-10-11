<?php

namespace App\Livewire\Admin;

use App\Facades\Kardex;
use App\Models\Inventory;
use App\Models\Variant;
use Livewire\Component;
use App\Models\Movement;

class MovementCreate extends Component
{
    public $type = 1;
    public $serie = 'M001';
    public $correlative;

    public $date;
    public $warehouse_id;

    public $reason_id;

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
        $this->correlative = Movement::max('correlative') + 1;
    }

    public function updated($property, $value)
    {
        if ($property == 'type') {
            $this->reset('reason_id');
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

        $lastRecord = Inventory::where('variant_id', $variant->id)
            ->where('warehouse_id', $this->warehouse_id)
            ->latest('id')
            ->first();
        $costBalance = $lastRecord ? $lastRecord->cost_balance : 0;

        $this->variants[] = [
            'id' => $variant->id,
            'name' => $variant->fullName,
            'quantity' => 1,
            'price' => $costBalance,
            'subtotal' => 1 * $costBalance,
        ];
        $this->reset('variant_id');
    }

    public function save()
    {
        $this->validate(
            [
                'type' => 'required|in:1,2',
                'serie' => 'required|string|max:10',
                'correlative' => 'required|numeric|min:1',
                'date' => 'nullable|date',
                'warehouse_id' => 'required|exists:warehouses,id',
                'reason_id' => 'required|exists:reasons,id',
                'total' => 'required|numeric|min:0',
                'observation' => 'nullable|string|max:255',
                'variants' => 'required|array|min:1',
                'variants.*.id' => 'required|exists:variants,id',
                'variants.*.quantity' => 'required|numeric|min:1',
                'variants.*.price' => 'required|numeric|min:0',
            ],
            [],
            [
                'type' => 'tipo de movimiento',
                'serie' => 'serie',
                'correlative' => 'correlativo',
                'date' => 'fecha',
                'warehouse_id' => 'almacen',
                'reason_id' => 'motivo',
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

        $movement = Movement::create([
            'type' => $this->type,
            'serie' => $this->serie,
            'correlative' => $this->correlative,
            'date' => $this->date ?? now(),
            'warehouse_id' => $this->warehouse_id,
            'reason_id' => $this->reason_id,
            'total' => $this->total,
            'observation' => $this->observation,
            'company_id' => $activeCompanyId,
        ]);

        foreach ($this->variants as $variant) {
            $movement->variants()->attach($variant['id'], [
                'quantity' => $variant['quantity'],
                'price' => $variant['price'],
                'subtotal' => $variant['quantity'] * $variant['price'],
            ]);
            //Kardex
            if ($this->type == 1) {
                Kardex::registerEntry($movement, $variant, $this->warehouse_id, 'Movimiento');
            } else if ($this->type == 2) {
                Kardex::registerExit($movement, $variant, $this->warehouse_id, 'Movimiento');
            }
        }

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Cotización creada exitosamente.',
        ]);

        return redirect()->route('admin.movements.index');
    }

    public function render()
    {
        return view('livewire.admin.movement-create');
    }
}
