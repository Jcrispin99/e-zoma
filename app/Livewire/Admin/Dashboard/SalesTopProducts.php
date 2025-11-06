<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Sale;
use App\Models\Variantable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SalesTopProducts extends Component
{
    public string $month;

    /** @var array<int, array{variant_id:int,name:string,sku:?string,qty:int,revenue:float}> */
    public array $topProducts = [];

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function loadData(): void
    {
        $this->topProducts = $this->fetchTopProducts();
        $this->dispatch('top-products:update', $this->topProducts);
    }

    public function updatedMonth(): void
    {
        $this->loadData();
    }

    /**
     * Obtiene los productos más vendidos del mes seleccionado.
     * Fuente: pivot Variantable asociado a Sales.
     *
     * @return array<int, array{variant_id:int,name:string,sku:?string,qty:int,revenue:float}>
     */
    protected function fetchTopProducts(): array
    {
        try {
            // Parsear el mes seleccionado (YYYY-MM)
            $start = Carbon::parse($this->month . '-01')->startOfMonth();
            $end = Carbon::parse($this->month . '-01')->endOfMonth();

            $rows = Variantable::query()
                ->where('variantable_type', Sale::class)
                ->whereHas('variantable', function ($q) use ($start, $end) {
                    $q->whereBetween('date', [$start, $end]);
                })
                ->with(['variant.product', 'variant.attributeValues'])
                ->select([
                    'variant_id',
                    DB::raw('SUM(quantity) as total_qty'),
                    DB::raw('SUM(subtotal) as total_revenue'),
                ])
                ->groupBy('variant_id')
                ->orderByDesc('total_qty')
                ->limit(10)
                ->get();

            return $rows->map(function ($row) {
                $variant = $row->variant;

                return [
                    'variant_id' => (int) $row->variant_id,
                    'name' => (string) ($variant?->fullName ?? $variant?->product?->name ?? 'Variante #' . $row->variant_id),
                    'sku' => $variant?->sku,
                    'qty' => (int) ($row->total_qty ?? 0),
                    'revenue' => (float) ($row->total_revenue ?? 0),
                ];
            })->toArray();
        } catch (\Throwable $e) {
            // En skeleton, ante error devolvemos vacío; en producción podrías loguear.
            return [];
        }
    }

    public function render()
    {
        return view('livewire.admin.dashboard.sales-top-products');
    }
}