<?php

namespace App\Livewire\Admin\Reports;

use App\Models\PosPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SalesByPaymentMethod extends Component
{
    public string $month;

    /** @var array<int, array{payment_method_id:int,name:string,transactions:int,total:float}> */
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

        $rows = PosPayment::query()
            ->whereBetween('created_at', [$start, $end])
            ->select([
                'payment_method_id',
                DB::raw('COUNT(*) as transactions'),
                DB::raw('SUM(amount) as total'),
            ])
            ->groupBy('payment_method_id')
            ->orderByDesc('total')
            ->limit(15)
            ->with('paymentMethod')
            ->get();

        $this->rows = $rows->map(function ($row) {
            return [
                'payment_method_id' => (int) $row->payment_method_id,
                'name' => (string) ($row->paymentMethod?->name ?? 'Método #' . $row->payment_method_id),
                'transactions' => (int) ($row->transactions ?? 0),
                'total' => (float) ($row->total ?? 0),
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.admin.reports.sales-by-payment-method');
    }
}