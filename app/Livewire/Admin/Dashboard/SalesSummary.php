<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Sale;
use Illuminate\Support\Carbon;
use Livewire\Component;

class SalesSummary extends Component
{
    public string $month;
    public string $incomeTaxRegime = 'rmt_annual_10';

    public float $totalSales = 0.0;
    public int $ordersCount = 0;
    public float $avgTicket = 0.0;

    // Impuesto a la renta
    public float $netSales = 0.0;          // Base neta (ventas - NC + ND)
    public float $incomeTaxRate = 0.0;     // Tasa configurable (por defecto 1.5%)
    public float $incomeTax = 0.0;         // Importe calculado

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
        // Cargar régimen por defecto desde ENV si existe
        $this->incomeTaxRegime = (string) env('INCOME_TAX_DEFAULT_REGIME', 'rmt_annual_10');
        $this->loadData();
    }

    public function updatedMonth(): void
    {
        $this->loadData();
    }

    public function updatedIncomeTaxRegime(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $start = Carbon::parse($this->month . '-01')->startOfMonth();
        $end = Carbon::parse($this->month . '-01')->endOfMonth();

        $query = Sale::query()->whereBetween('date', [$start, $end]);

        $this->ordersCount = (int) $query->count();
        $this->totalSales = (float) ($query->sum('total') ?? 0);
        $this->avgTicket = $this->ordersCount > 0 ? $this->totalSales / $this->ordersCount : 0.0;

        // Cálculo de base neta considerando devoluciones (NC) y notas de débito (ND)
        $normalSalesSum = (float) Sale::query()
            ->whereBetween('date', [$start, $end])
            ->whereHas('journal', function ($q) {
                $q->whereIn('document_type_code', ['01', '03']); // Factura, Boleta
            })
            ->sum('total');

        $creditNotesSum = (float) Sale::query()
            ->whereBetween('date', [$start, $end])
            ->whereHas('journal', function ($q) {
                $q->where('document_type_code', '07'); // Nota de crédito
            })
            ->sum('total');

        $debitNotesSum = (float) Sale::query()
            ->whereBetween('date', [$start, $end])
            ->whereHas('journal', function ($q) {
                $q->where('document_type_code', '08'); // Nota de débito
            })
            ->sum('total');

        // Si las NC se almacenan con monto negativo, restar su valor absoluto evita sumar en lugar de restar
        $creditAdj = abs($creditNotesSum);
        $debitAdj = abs($debitNotesSum);
        $this->netSales = max(0.0, $normalSalesSum - $creditAdj + $debitAdj);

        // Asignar tasa según régimen seleccionado
        $options = $this->incomeTaxRegimeOptions();
        $this->incomeTaxRate = (float) ($options[$this->incomeTaxRegime]['rate'] ?? 0.015);
        $this->incomeTax = round($this->netSales * $this->incomeTaxRate, 2);
    }

    /**
     * Opciones de régimen y tasas de referencia (Perú).
     * Nota: La base legal distingue pago a cuenta mensual vs. tasa anual.
     */
    protected function incomeTaxRegimeOptions(): array
    {
        return [
            // Régimen MYPE Tributario (RMT): pago a cuenta mensual 1% de renta neta
            'rmt_monthly_1' => [
                'label' => 'Régimen MYPE Tributario — Pago a cuenta 1% (mensual)',
                'rate' => 0.01,
            ],
            // RMT: tasa anual 10% sobre renta neta hasta 15 UIT (estimación)
            'rmt_annual_10' => [
                'label' => 'Régimen MYPE Tributario — IR anual 10% (estimación)',
                'rate' => 0.10,
            ],
            // Régimen Especial de Renta (RER): 1.5% mensual sobre ingresos netos
            'rer_1_5' => [
                'label' => 'Régimen Especial de Renta — 1.5% (mensual)',
                'rate' => 0.015,
            ],
            // Régimen General (RG): pago a cuenta estándar 1% cuando no aplica coeficiente
            'general_1' => [
                'label' => 'Régimen General — Pago a cuenta 1% (mensual)',
                'rate' => 0.01,
            ],
        ];
    }

    public function render()
    {
        return view('livewire.admin.dashboard.sales-summary', [
            'regimes' => $this->incomeTaxRegimeOptions(),
        ]);
    }
}
