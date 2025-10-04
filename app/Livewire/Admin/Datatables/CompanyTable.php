<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Company;

class CompanyTable extends DataTableComponent
{
    protected $model = Company::class;

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
            Column::make("Pertenece", "parent.name")
                ->sortable(),
            Column::make("Nombre", "name")
                ->sortable(),
            Column::make("Document number", "document_number")
                ->sortable(),
            Column::make("Phone", "phone")
                ->sortable(),
            Column::make("Acciones")
                ->label(function ($row, Column $column) {
                    return view('admin.companies.actions', ['company' => $row]);
                })

        ];
    }
}
