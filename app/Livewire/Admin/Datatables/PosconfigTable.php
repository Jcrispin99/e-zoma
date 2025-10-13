<?php

namespace App\Livewire\Admin\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\PosConfig;
use App\Models\PosSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class PosconfigTable extends DataTableComponent
{
    protected $model = PosConfig::class;

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
            Column::make("Company id", "company_id")
                ->sortable(),
            Column::make("Warehouse id", "warehouse_id")
                ->sortable(),
            Column::make("Receipt sequence id", "receipt_sequence_id")
                ->sortable(),
            Column::make("Invoice sequence id", "invoice_sequence_id")
                ->sortable(),
            Column::make("Default customer id", "default_customer_id")
                ->sortable(),
            Column::make("Is active", "is_active")
                ->sortable(),
            Column::make("Acciones")
                ->label(function ($row, Column $column) {
                    return view('admin.posconfig.actions', ['posconfig' => $row]);
                })
        ];
    }

    public function openSession(int $posConfigId): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        /** @var PosConfig $posConfig */
        $posConfig = PosConfig::query()->findOrFail($posConfigId);

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
