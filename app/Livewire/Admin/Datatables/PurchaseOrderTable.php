<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderTable extends DataTableComponent
{
    // protected $model = PurchaseOrder::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make("Date", "date")
                ->sortable()
                ->format(fn($value) => $value->format('Y/m/d')),

            Column::make("Serie", "serie")
                ->sortable(),
            Column::make("Correlative", "correlative")
                ->sortable(),

            Column::make("Documento", "supplier.document_number")
                ->sortable(),
            Column::make("Razon social", "supplier.name")
                ->sortable(),

            Column::make("Total", "total")
                ->sortable()
                ->format(fn($value) => number_format($value, 2, ',', '.')),

            Column::make("Acciones")
                ->label(fn($row) => view('admin.purchases-orders.actions', [
                    'purchaseOrder' => $row,
                ])),
        ];
    }
    public function builder(): Builder
    {
        return PurchaseOrder::query()->with(['supplier']);
    }
}
