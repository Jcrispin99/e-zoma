<div wire:init="loadData">
    <x-wire-card>
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">Top productos vendidos</h2>
                <p class="text-sm text-gray-500">Filtro por mes</p>
            </div>
            <div class="w-52">
                <x-wire-datetime-picker label="Mes" placeholder="Selecciona mes" without-time display-format="MMM YYYY"
                    parse-format="YYYY-MM" icon="calendar" wire:model.live="month" />
            </div>
        </div>

        <!-- Gráfico -->
        <div class="mt-4" wire:ignore>
            <div id="top-products-chart" class="w-full"></div>
        </div>

        <!-- Tabla -->
        <div class="mt-6 overflow-x-auto">
            @if(empty($topProducts))
            <div class="text-sm text-gray-500">Sin datos para el mes seleccionado.</div>
            @else
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-2 px-3">Producto</th>
                        <th class="text-left py-2 px-3">SKU</th>
                        <th class="text-right py-2 px-3">Cantidad</th>
                        <th class="text-right py-2 px-3">Ingresos Netos (S/)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topProducts as $item)
                    <tr class="border-b">
                        <td class="py-2 px-3">
                            {{ $item['name'] }}
                        </td>
                        <td class="py-2 px-3 text-gray-500">{{ $item['sku'] ?? '—' }}</td>
                        <td class="py-2 px-3 text-right">{{ number_format($item['qty'] ?? 0) }}</td>
                        <td class="py-2 px-3 text-right">{{ number_format($item['revenue'] ?? 0, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </x-wire-card>

</div>