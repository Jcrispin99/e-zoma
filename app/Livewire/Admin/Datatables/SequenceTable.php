<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Sequence;

class SequenceTable extends DataTableComponent
{
    protected $model = Sequence::class;

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
            Column::make("Prefix", "prefix")
                ->sortable(),
            Column::make("Sequence size", "sequence_size")
                ->sortable(),
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
}
