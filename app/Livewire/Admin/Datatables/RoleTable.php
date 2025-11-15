<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Builder;

class RoleTable extends DataTableComponent
{
    protected $model = Role::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id')->sortable(),
            Column::make('Nombre', 'name')->sortable(),
            Column::make('N° Usuarios')
                ->label(function ($row, Column $column) {
                    return (string) ($row->users_count ?? 0);
                }),
            Column::make('Guard', 'guard_name')->sortable(),
            Column::make('Permisos')
                ->label(function ($row, Column $column) {
                    return view('admin.roles.permissions-cell', ['role' => $row]);
                }),

            Column::make('Acciones')
                ->label(function ($row, Column $column) {
                    return view('admin.roles.actions', ['role' => $row]);
                }),
        ];
    }

    public function builder(): Builder
    {
        return Role::query()->with('permissions')->withCount('users');
    }
}
