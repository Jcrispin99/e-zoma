<x-wire-card>
    <div class="flex items-end justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold">Ventas por método de pago</h2>
            <p class="text-sm text-gray-500">Totales y transacciones por mes</p>
        </div>
        <div class="w-52">
            <x-wire-datetime-picker
                label="Mes"
                placeholder="Selecciona mes"
                without-time
                display-format="MMM YYYY"
                parse-format="YYYY-MM"
                icon="calendar"
                wire:model.live="month"
            />
        </div>
    </div>

    <div class="mt-4 overflow-x-auto">
        @if(empty($rows))
            <div class="text-sm text-gray-500">Sin datos para el mes seleccionado.</div>
        @else
            <table class="min-w-full text-sm">
                <thead>
                <tr class="border-b">
                    <th class="text-left py-2 px-3">Método de pago</th>
                    <th class="text-right py-2 px-3">Transacciones</th>
                    <th class="text-right py-2 px-3">Total (S/)</th>
                </tr>
                </thead>
                <tbody>
                @foreach($rows as $item)
                    <tr class="border-b">
                        <td class="py-2 px-3">{{ $item['name'] }}</td>
                        <td class="py-2 px-3 text-right">{{ number_format($item['transactions']) }}</td>
                        <td class="py-2 px-3 text-right">{{ number_format($item['total'], 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-wire-card>