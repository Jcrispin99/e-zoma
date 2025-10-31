<?php

namespace App\Livewire\Admin\Products;

use App\Models\Variant;
use Livewire\Component;
use App\Models\Warehouse;
use App\Models\Inventory;
use Livewire\WithPagination;

class Kardex extends Component
{
    use WithPagination;

    public Variant $variant;

    public $warehouses;

    public $warehouse_id;

    public $fecha_inicial = null;
    public $fecha_final = null;

    public function mount()
    {
        $this->warehouses = Warehouse::all();
        $this->warehouse_id = $this->warehouses->first()->id ?? null;
    }

    public function render()
    {
        $inventories = Inventory::where('variant_id', $this->variant->id)
            ->where('warehouse_id', $this->warehouse_id)
            ->when($this->fecha_inicial, function ($query) {
                $query->whereDate('created_at', '>=', $this->fecha_inicial);
            })
            ->when($this->fecha_final, function ($query) {
                $query->whereDate('created_at', '<=', $this->fecha_final);
            })
            ->paginate(10);

        return view('livewire.admin.products.kardex', compact('inventories'));
    }
}
