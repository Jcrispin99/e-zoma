<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Warehouse;

class WarehouseTable extends DataTableComponent
{
    protected $model = Warehouse::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make("Name", "name")
                ->sortable(),
            Column::make("Location", "location")
                ->sortable(),
            Column::make("Acciones")
                ->label(function ($row, Column $column) {
                    return view('admin.warehouses.actions', ['warehouse' => $row]);
                }),
            
        ];
    }
}
