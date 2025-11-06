<x-wire-card>
    <div class="flex items-end justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold">Resumen de ventas</h2>
            <p class="text-sm text-gray-500">Métricas del mes seleccionado</p>
        </div>
        <div class="flex items-end gap-3">
            <div class="w-52">
                <x-wire-datetime-picker label="Mes" placeholder="Selecciona mes" without-time display-format="MMM YYYY"
                    parse-format="YYYY-MM" icon="calendar" wire:model.live="month" />
            </div>
            <div class="w-72">
                <x-wire-native-select label="Régimen IR" wire:model.live="incomeTaxRegime">
                    @foreach(($regimes ?? []) as $key => $opt)
                        <option value="{{ $key }}">{{ $opt['label'] }}</option>
                    @endforeach
                </x-wire-native-select>
            </div>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="border rounded-lg p-4 bg-white shadow-sm">
            <div class="text-gray-500 text-sm">Total vendido</div>
            <div class="mt-1 text-2xl font-semibold">S/ {{ number_format($totalSales, 2) }}</div>
        </div>
        <div class="border rounded-lg p-4 bg-white shadow-sm">
            <div class="text-gray-500 text-sm">Órdenes</div>
            <div class="mt-1 text-2xl font-semibold">{{ number_format($ordersCount) }}</div>
        </div>
        <div class="border rounded-lg p-4 bg-white shadow-sm">
            <div class="text-gray-500 text-sm">Ticket promedio</div>
            <div class="mt-1 text-2xl font-semibold">S/ {{ number_format($avgTicket, 2) }}</div>
        </div>
        <div class="border rounded-lg p-4 bg-white shadow-sm">
            <div class="text-gray-500 text-sm">Impuesto de renta</div>
            <div class="mt-1 text-2xl font-semibold">S/ {{ number_format($incomeTax, 2) }}</div>
            <div class="mt-1 text-xs text-gray-500">Base neta: S/ {{ number_format($netSales, 2) }} · Tasa: {{ number_format($incomeTaxRate * 100, 2) }}%</div>
        </div>
    </div>

</x-wire-card>