<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Columns\ImageColumn;

class ProductTable extends DataTableComponent
{
    // protected $model = Product::class;

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
            Column::make("Nombre", "name")
                ->searchable()
                ->sortable(),
            Column::make("Categoria", "category.full_name")
                ->searchable()
                ->sortable(),
            Column::make("Barcode", "barcode")
                ->sortable(),
            Column::make("Precio", "price")
                ->sortable(),
            Column::make("Acciones")
                ->label(function ($row, Column $column) {
                    return view('admin.products.actions', ['product' => $row]);
                })
        ];
    }

    public function builder(): Builder
    {
        return Product::query()->with(['category', 'images']);
    }
}
