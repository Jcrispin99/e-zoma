<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Journal;

class JournalTable extends DataTableComponent
{
    protected $model = Journal::class;

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
            Column::make("Sequence id", "sequence_id")
                ->sortable(),
            Column::make("Company id", "company_id")
                ->sortable(),
            Column::make("Acciones")
                ->label(function ($row, Column $column) {
                    return view('admin.journals.actions', ['journal' => $row]);
                })
        ];
    }
}
