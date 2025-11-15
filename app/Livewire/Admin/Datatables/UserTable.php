<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserTable extends DataTableComponent
{
    protected $model = User::class;

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
            Column::make("Email", "email")
                ->sortable(),
            Column::make("Roles")
                ->label(function ($row, Column $column) {
                    return $row->roles->pluck('name')->join(', ');
                }),
            Column::make("Acciones")
                ->label(function ($row, Column $column) {
                    return view('admin.users.actions', ['user' => $row]);
                })
        ];
    }

    public function builder(): Builder
    {
        return User::query()->with('roles');
    }
}
