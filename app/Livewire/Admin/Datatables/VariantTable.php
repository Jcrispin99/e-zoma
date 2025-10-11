<?php

namespace App\Livewire\Admin\Datatables;

use App\Models\Inventory;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Variant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\Views\Columns\ImageColumn;

class VariantTable extends DataTableComponent
{
    protected $model = Variant::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas(
            [
                'after-wrapper' => [
                    'admin.variants.modal',
                ],
            ]
        );
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
            Column::make("Atributos")
                ->label(function ($row, Column $column) {
                    return $row->attributeValues->map(function ($attributeValue) {
                        return $attributeValue->value;
                    })->implode(' / ');
                })
                ->searchable(function (Builder $query, $searchTerm) {
                    $query->orWhereHas('attributeValues', function ($query) use ($searchTerm) {
                        $query->where('value', 'like', '%' . $searchTerm . '%');
                    });
                })
                ->html(),
            Column::make("Sku", "sku")
                ->sortable(),
            Column::make("Precio", "price")
                ->sortable(),
            Column::make("Stock", "stock")
                ->sortable()
                ->format(function ($value, $row) {
                    return view(
                        'admin.variants.stock',
                        [
                            'stock' => $value,
                            'variant' => $row,
                        ]
                    );
                }),
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
        return Variant::query()->with(['product', 'images', 'attributeValues']);
    }

    //propiedades
    public $openModal = false;
    public $inventories = [];

    //metodos
    public function showStock($variant_id)
    {
        $this->openModal = true;

        $latestInventory =  Inventory::where('variant_id', $variant_id)
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('warehouse_id')
            ->pluck('id');

        $this->inventories = Inventory::whereIn('id', $latestInventory)
            ->with(['warehouse'])
            ->get();
    }
}
