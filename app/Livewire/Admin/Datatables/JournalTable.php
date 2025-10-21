<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Journal;
use Illuminate\Database\Eloquent\Builder;

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
            Column::make("Secuencia", "sequence.next_number")
                ->sortable(),
            Column::make("Compañía", "company.name")
                ->sortable(),
            Column::make("Acciones")
                ->label(function ($row, Column $column) {
                    return view('admin.journals.actions', ['journal' => $row]);
                })
        ];
    }

    public function builder(): Builder
    {
        // Cargar las relaciones 'sequence' y 'company' para evitar N+1
        return Journal::query()->with(['sequence', 'company']);
    }
}
