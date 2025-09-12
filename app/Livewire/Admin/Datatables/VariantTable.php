<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Variant;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Columns\ImageColumn;

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
            ImageColumn::make("Imagen")
                ->location(
                    fn($row) => $row->image
                )
                ->attributes(
                    fn($row) =>
                    [
                        'class' => 'image-product',
                    ]
                ),
            Column::make("Nombre", "product.name")
                ->sortable()
                ->searchable()
                ->format(fn($value, $row) => $value . ' - '),
            Column::make("Sku", "sku")
                ->sortable(),
            Column::make("Precio", "price")
                ->sortable(),
            Column::make("Barcode", "barcode")
                ->sortable(),
            Column::make("Acciones")
                ->label(function ($row, Column $column) {
                    return view('admin.variants.actions', ['variant' => $row]);
                }),
        ];
    }
    public function builder(): Builder
    {
        return Variant::query()->with(['product', 'images']);
    }
}
