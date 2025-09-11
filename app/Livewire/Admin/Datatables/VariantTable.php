<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Variant;

class VariantTable extends DataTableComponent
{
    protected $model = Variant::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make("Product id", "product_id")
                ->sortable(),
            Column::make("Name", "name")
                ->sortable(),
            Column::make("Sku", "sku")
                ->sortable(),
            Column::make("Price", "price")
                ->sortable(),
            Column::make("Barcode", "barcode")
                ->sortable(),
            Column::make("Acciones")
                ->label(function ($row, Column $column) {
                    return view('admin.variants.actions', ['variant' => $row]);
                }),
        ];
    }
}
