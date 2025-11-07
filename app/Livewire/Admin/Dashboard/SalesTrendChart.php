<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Sale;
use App\Models\Warehouse;
use App\Models\Company;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;
use Livewire\Component;

class SalesTrendChart extends Component
{
    public string $month;

    /** @var array<int,string> */
    public array $categories = [];

    /** @var array<int,float> */
    public array $series = [];

    public ?int $warehouseId = null;
    public ?int $companyId = null;

    /** @var array<int, array{id:int, name:string}> */
    public array $warehouses = [];
    /** @var array<int, array{id:int, name:string}> */
    public array $companies = [];

    public float $totalRevenue = 0.0;

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
        $this->loadWarehouses();
        $this->companies = Company::select('id', 'name')->orderBy('name')->get()
            ->map(fn($c) => ['id' => (int) $c->id, 'name' => (string) $c->name])->toArray();
    }

    public function loadData(): void
    {
        [$this->categories, $this->series] = $this->fetchDailyRevenue($this->month);
        $this->totalRevenue = array_sum($this->series);
        // Notificar al front para actualizar el gráfico sin scripts inline
        $this->dispatch('sales-trend-chart:update', categories: $this->categories, series: $this->series);
    }

    public function updatedMonth(): void
    {
        $this->loadData();
    }

    public function updatedWarehouseId(): void
    {
        $this->loadData();
    }

    public function updatedCompanyId(): void
    {
        $this->loadWarehouses();
        $this->loadData();
    }

    /**
     * Returns [categories, series] for daily revenue in given month.
     * @return array{0:array<int,string>,1:array<int,float>}
     */
    protected function fetchDailyRevenue(string $ym): array
    {
        $start = Carbon::parse($ym . '-01')->startOfMonth();
        $end = Carbon::parse($ym . '-01')->endOfMonth();

        $rows = Sale::query()
            ->whereBetween('date', [$start, $end])
            ->when($this->warehouseId, fn($q) => $q->where('warehouse_id', $this->warehouseId))
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->selectRaw('DATE(date) as day, SUM(total) as revenue')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $byDay = collect($rows)->keyBy('day');

        $categories = [];
        $series = [];

        foreach (CarbonPeriod::create($start, '1 day', $end) as $date) {
            $day = $date->format('Y-m-d');
            $categories[] = $date->format('d');
            $series[] = (float) ($byDay->get($day)->revenue ?? 0);
        }

        return [$categories, $series];
    }

    protected function loadWarehouses(): void
    {
        $query = Warehouse::select('id', 'name')->orderBy('name');
        if ($this->companyId) {
            $query->where('company_id', $this->companyId);
        }
        $this->warehouses = $query->get()
            ->map(fn($w) => ['id' => (int) $w->id, 'name' => (string) $w->name])
            ->toArray();

        if ($this->warehouseId && !collect($this->warehouses)->contains(fn($w) => $w['id'] === $this->warehouseId)) {
            $this->warehouseId = null;
        }
    }

    public function render()
    {
        return view('livewire.admin.dashboard.sales-trend-chart');
    }
}