<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\LoyaltyProgram;
use Illuminate\Database\Eloquent\Builder;

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
            Column::make('Id', 'id')->sortable(),
            Column::make('Compañía', 'company.name')
                ->label(fn($row) => optional($row->company)->name ?? '—'),
            Column::make('Tipo', 'program_type')->sortable(),
            Column::make('Aplicación', 'applies_on')->sortable(),
            Column::make('Disparo', 'trigger')->sortable(),
            Column::make('Desde', 'date_from')->sortable(),
            Column::make('Hasta', 'date_to')->sortable(),
            Column::make('Activo')
                ->label(fn($row) => $row->active ? 'Sí' : 'No'),
            Column::make('Canales')
                ->label(function ($row) {
                    $channels = [];
                    if ($row->sale_ok) $channels[] = 'Venta';
                    if ($row->ecommerce_ok) $channels[] = 'E-commerce';
                    if ($row->pos_ok) $channels[] = 'POS';
                    return implode(', ', $channels);
                }),
            Column::make('Acciones')
                ->label(function ($row, Column $column) {
                    return view('admin.loyalty-programs.actions', ['program' => $row]);
                }),
        ];
    }

    public function builder(): Builder
    {
        return LoyaltyProgram::query()->with(['company']);
    }
}
