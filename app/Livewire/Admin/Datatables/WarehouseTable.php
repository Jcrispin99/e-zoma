<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Warehouse;
use App\Livewire\Traits\CompanyFilterable;
use Illuminate\Database\Eloquent\Builder;

class WarehouseTable extends DataTableComponent
{
    use CompanyFilterable;

    protected $model = Warehouse::class;

    protected $listeners = ['company-changed' => '$refresh'];

    public function builder(): Builder
    {
        return $this->applyCompanyFilter(Warehouse::query());
    }

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
