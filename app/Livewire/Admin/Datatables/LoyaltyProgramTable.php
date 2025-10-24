<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\LoyaltyProgram;

class LoyaltyProgramTable extends DataTableComponent
{
    protected $model = LoyaltyProgram::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make("Name", "name")
                ->sortable(),
            Column::make("Code", "code")
                ->sortable(),
            Column::make("Type", "type")
                ->sortable(),
            Column::make("Scope", "scope")
                ->sortable(),
            Column::make("Is active", "is_active")
                ->sortable(),
            Column::make("Valid from", "valid_from")
                ->sortable(),
            Column::make("Valid to", "valid_to")
                ->sortable(),
            Column::make("Acciones")
                ->label(function ($row, Column $column) {
                    return view('admin.loyalty-programs.actions', ['program' => $row]);
                }),
        ];
    }
}
