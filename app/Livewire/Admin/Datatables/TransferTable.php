<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Transfer;

class TransferTable extends DataTableComponent
{
    protected $model = Transfer::class;

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
            Column::make("Origen", "originWarehouse.name")
                ->sortable(),
            Column::make("Destino", "destinationWarehouse.name")
                ->sortable(),
            Column::make("Total", "total")
                ->sortable(),
            Column::make("Acciones")
                ->label(function ($row, Column $column) {
                    return view('admin.transfers.actions', ['transfer' => $row]);
                })
        ];
    }

    public function builder(): Builder
    {
        return Transfer::query()->with(['originWarehouse', 'destinationWarehouse']);
    }
}
