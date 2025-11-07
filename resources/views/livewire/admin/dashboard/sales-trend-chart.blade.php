<div wire:init="loadData">
    <x-wire-card>
        <div class="flex flex-col gap-4">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold">Ventas por día</h2>
                    <p class="text-sm text-gray-500">Ingresos diarios del mes seleccionado</p>
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-4">

                <div class="w-56">
                    <label class="block text-sm font-medium text-gray-700">Compañía</label>
                    <select
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        wire:model.live="companyId">
                        <option value="">Todas las compañías</option>
                        @foreach($companies as $c)
                        <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-56">
                    <label class="block text-sm font-medium text-gray-700">Almacén</label>
                    <select
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        wire:model.live="warehouseId">
                        <option value="">Todos los almacenes</option>
                        @foreach($warehouses as $w)
                        <option value="{{ $w['id'] }}">{{ $w['name'] }}</option>
                        @endforeach
                    </select>
                </div>


                <div class="w-52">
                    <x-wire-datetime-picker label="Mes" placeholder="Selecciona mes" without-time
                        display-format="MMM YYYY" parse-format="YYYY-MM" icon="calendar" wire:model.live="month" />
                </div>


            </div>

            <div class="mt-2 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <div id="sales-trend-chart" wire:ignore class="w-full"></div>
                </div>
                <div class="lg:col-span-1">
                    <div class="rounded-lg border bg-white p-4 shadow-sm">
                        <div class="text-sm text-gray-500">Total vendido</div>
                        <div class="mt-1 text-3xl font-semibold">S/ {{ number_format($totalRevenue, 2) }}</div>
                        <div class="mt-2 text-xs text-gray-500">Filtros aplicados</div>
                        @if($warehouseId)
                        <div class="text-xs text-gray-600">Almacén: {{ collect($warehouses)->firstWhere('id',
                            $warehouseId)['name'] ?? 'N/A' }}</div>
                        @else
                        <div class="text-xs text-gray-600">Almacén: Todos</div>
                        @endif
                        @if($companyId)
                        <div class="text-xs text-gray-600">Compañía: {{ collect($companies)->firstWhere('id',
                            $companyId)['name'] ?? 'N/A' }}</div>
                        @else
                        <div class="text-xs text-gray-600">Compañía: Todas</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </x-wire-card>
</div>
@push('js')
<script>
    (function () {
        const el = document.getElementById('sales-trend-chart');
        if (!el) return;

        const baseOptions = {
            chart: { type: 'bar', height: 320, animations: { enabled: true } },
            series: [{ name: 'Ingresos', data: [] }],
            xaxis: { categories: [] },
            colors: ['#3b82f6'],
            dataLabels: { enabled: false },
            grid: { strokeDashArray: 4 },
            tooltip: { y: { formatter: (val) => `S/ ${Number(val).toFixed(2)}` } },
        };

        function ensureChart() {
            if (typeof ApexCharts === 'undefined') return false;
            if (!el.__chart) {
                el.__chart = new ApexCharts(el, baseOptions);
                el.__chart.render();
            }
            return true;
        }

        document.addEventListener('livewire:init', () => {
            ensureChart();
            Livewire.on('sales-trend-chart:update', ({ categories, series }) => {
                if (!ensureChart()) return;
                try {
                    el.__chart.updateOptions({
                        series: [{ name: 'Ingresos', data: Array.isArray(series) ? series : [] }],
                        xaxis: { categories: Array.isArray(categories) ? categories : [] },
                    });
                } catch (e) { /* noop */ }
            });
        });
    })();
</script>
@endpush