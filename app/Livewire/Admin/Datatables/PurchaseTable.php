<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Builder;

class PurchaseTable extends DataTableComponent
{
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
                ->sortable()
                ->format(fn($value) => $value->format('Y/m/d')),
            Column::make("Purchase order id", "purchase_order_id")
                ->sortable(),
            Column::make("Supplier id", "supplier.name")
                ->sortable(),
            Column::make("Warehouse id", "warehouse.name")
                ->sortable(),
            Column::make("Total", "total")
                ->sortable(),
        ];
    }
    public function builder(): Builder
    {
        return Purchase::query()->with(['supplier', 'warehouse']);
    }
}
