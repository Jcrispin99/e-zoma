<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TopSellingPos extends Component
{
    public string $month;

    /** @var array<int, array{pos_config_id:int,name:string,orders:int,revenue:float}> */
    public array $topPos = [];

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function loadData(): void
    {
        $this->topPos = $this->fetchTopPos();
    }

    public function updatedMonth(): void
    {
        $this->loadData();
    }

    /**
     * Retorna los POS con mayor facturación (ventas) del mes seleccionado.
     * Se agrupa por configuración de POS usando las ventas asociadas a órdenes POS.
     */
    protected function fetchTopPos(): array
    {
        try {
            $start = Carbon::parse($this->month . '-01')->startOfMonth();
            $end = Carbon::parse($this->month . '-01')->endOfMonth();

            $rows = Sale::query()
                ->whereNotNull('pos_order_id')
                ->whereBetween('date', [$start, $end])
                ->leftJoin('pos_orders', 'pos_orders.id', '=', 'sales.pos_order_id')
                ->leftJoin('pos_sessions', 'pos_sessions.id', '=', 'pos_orders.pos_session_id')
                ->leftJoin('pos_configs', 'pos_configs.id', '=', 'pos_sessions.pos_config_id')
                ->select([
                    'pos_sessions.pos_config_id as pos_config_id',
                    DB::raw('COALESCE(pos_configs.name, CONCAT("POS #", pos_sessions.pos_config_id)) as name'),
                    DB::raw('COUNT(sales.id) as orders'),
                    DB::raw('SUM(sales.total) as revenue'),
                ])
                ->groupBy('pos_sessions.pos_config_id', 'pos_configs.name')
                ->orderByDesc('revenue')
                ->limit(15)
                ->get();

            return $rows->map(function ($row) {
                return [
                    'pos_config_id' => (int) ($row->pos_config_id ?? 0),
                    'name' => (string) ($row->name ?? ('POS #' . ($row->pos_config_id ?? ''))),
                    'orders' => (int) ($row->orders ?? 0),
                    'revenue' => (float) ($row->revenue ?? 0),
                ];
            })->toArray();
        } catch (\Throwable $e) {
            // En caso de error, devolvemos vacío para no romper el dashboard.
            return [];
        }
    }

    public function render()
    {
        return view('livewire.admin.dashboard.top-selling-pos');
    }
}