<?php

namespace App\Livewire\Admin\Reports;

use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class FrequentCustomers extends Component
{
    public string $month;

    /** @var array<int, array{customer_id:int,name:string,orders:int,revenue:float}> */
    public array $rows = [];

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
        $this->loadData();
    }

    public function updatedMonth(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $start = Carbon::parse($this->month . '-01')->startOfMonth();
        $end = Carbon::parse($this->month . '-01')->endOfMonth();

        $rows = Sale::query()
            ->whereBetween('date', [$start, $end])
            ->select([
                'customer_id',
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total) as revenue'),
            ])
            ->groupBy('customer_id')
            ->orderByDesc('orders')
            ->limit(15)
            ->with('customer')
            ->get();

        $this->rows = $rows->map(function ($row) {
            return [
                'customer_id' => (int) $row->customer_id,
                'name' => (string) ($row->customer?->name ?? 'Cliente #' . $row->customer_id),
                'orders' => (int) ($row->orders ?? 0),
                'revenue' => (float) ($row->revenue ?? 0),
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.admin.reports.frequent-customers');
    }
}