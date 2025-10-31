<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Sequence;
use Illuminate\Database\Eloquent\Builder;

class SequenceTable extends DataTableComponent
{

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make('Journals', 'id')
                ->label(fn($row) => $row->journals->pluck('code')->implode(', ')),
            Column::make("Step", "step")
                ->sortable(),
            Column::make("Next number", "next_number")
                ->sortable(),
            Column::make("Acciones")
                ->label(function ($row, Column $column) {
                    return view('admin.sequences.actions', ['sequence' => $row]);
                })
        ];
    }
    public function builder(): Builder
    {
        return Sequence::query()->with(['journals']);
    }
}
