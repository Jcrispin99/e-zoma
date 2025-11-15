<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\PosConfig;
use App\Models\PosSession;
use Illuminate\Database\Eloquent\Builder;
use App\Livewire\Traits\CompanyFilterable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Carbon;

class PosconfigTable extends DataTableComponent
{
    use CompanyFilterable;

    protected $model = PosConfig::class;

    protected $listeners = ['company-changed' => '$refresh'];

    public function configure(): void
    {
        $this->setPrimaryKey('id');

        // Quitamos las clases por defecto (incluye overflow-y-auto) y definimos las nuestras
        $this->setTableWrapperAttributes([
            'default' => false,
            'class' => 'shadow border-b border-gray-200 dark:border-gray-700 sm:rounded-lg overflow-visible'
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make("Name", "name")
                ->sortable(),
            Column::make("Warehouse", "warehouse.name")
                ->sortable(),
            Column::make("Is active", "is_active")
                ->sortable(),
            Column::make("Acciones")
                ->label(function ($row, Column $column) {
                    $hasOpen = PosSession::query()
                        ->where('pos_config_id', $row->id)
                        ->where('status', 'open')
                        ->whereNull('closed_at')
                        ->exists();
                    return view('admin.posconfig.actions', ['posconfig' => $row, 'hasOpen' => $hasOpen]);
                })
        ];
    }

    public function builder(): Builder
    {
        // Cargar relación para mostrar el nombre del almacén y evitar N+1
        $query = PosConfig::query()->with(['warehouse']);
        return $this->applyCompanyFilter($query);
    }

    public function openSession(int $posConfigId): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        /** @var PosConfig $posConfig */
        $posConfig = PosConfig::query()->findOrFail($posConfigId);

        if (! Gate::allows('read_pos_sessions', $posConfig)) {
            return;
        }

        // Si hay una sesión abierta para esta caja, continuar en esa
        $existing = PosSession::query()
            ->where('pos_config_id', $posConfig->id)
            ->where('status', 'open')
            ->whereNull('closed_at')
            ->orderByDesc('opened_at')
            ->first();

        if ($existing) {
            $this->redirect('/pos/' . $existing->id);
            return;
        }

        $session = PosSession::create([
            'pos_config_id' => $posConfig->id,
            'user_id' => $userId,
            'status' => 'open',
            'opening_balance' => 0,
            'opened_at' => Carbon::now(),
        ]);

        $this->redirect('/pos/' . $session->id);
    }
}
