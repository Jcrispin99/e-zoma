<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Sale;

class SaleTable extends DataTableComponent
{
    protected $model = Sale::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make("Voucher type", "voucher_type")
                ->sortable(),
            Column::make("Serie", "serie")
                ->sortable(),
            Column::make("Correlative", "correlative")
                ->sortable(),
            Column::make("Date", "date")
                ->format(function ($value) {
                    return $value->format('d/m/Y');
                })
                ->sortable(),
            Column::make("Quote id", "quote.correlative")
                ->sortable(),
            Column::make("Customer id", "customer.name")
                ->sortable(),
            Column::make("Warehouse id", "warehouse.name")
                ->sortable(),
            Column::make("Total", "total")
                ->sortable(),
            Column::make("Acciones")
                ->label(function ($row, Column $column) {
                    return view('admin.sales.actions', ['sale' => $row]);
                })
        ];
    }

    public function builder(): Builder
    {
        return Sale::query()->with(['quote', 'customer', 'warehouse']);
    }
}
