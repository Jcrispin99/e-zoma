<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Customer;

class CustomerTable extends DataTableComponent
{
    protected $model = Customer::class;

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
            Column::make("Typo doc", "identity.name")
                ->sortable(),
            Column::make("Num doc", "document_number")
                ->searchable()
                ->sortable(),
            Column::make("Razon social", "name")
                ->searchable()
                ->sortable(),
            Column::make("Correo", "email")
                ->sortable(),
            Column::make("Telefono", "phone")
                ->sortable(),
            Column::make("Acciones")
                ->label(function ($row, Column $column) {
                    return view('admin.customers.actions', ['customer' => $row]);
                })
        ];
    }
    public function builder(): Builder
    {
        return Customer::query()->with(['identity']);
    }
}
