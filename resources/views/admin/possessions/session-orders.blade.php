<div class="space-y-3">
    <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold">Órdenes de la sesión</h3>
        <div class="text-sm text-gray-600">Sesión #{{ request()->route('posSession')->id ?? '' }}</div>
    </div>

    @if ($orders->isEmpty())
        <div class="border rounded p-3 text-sm text-gray-600">No hay órdenes registradas para esta sesión.</div>
    @else
        <div class="overflow-x-auto border rounded">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Serie</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Correlativo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pagado</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Creado</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($orders as $o)
                        <tr>
                            <td class="px-4 py-2 text-sm">{{ $o->id }}</td>
                            <td class="px-4 py-2 text-sm">{{ $o->customer->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm">{{ $o->sale?->serie ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm">{{ $o->sale?->correlative ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm">{{ number_format($o->total_amount ?? 0, 2) }}</td>
                            <td class="px-4 py-2 text-sm">{{ number_format($o->payments->sum('amount'), 2) }}</td>
                            <td class="px-4 py-2 text-sm">{{ ucfirst($o->status ?? 'pendiente') }}</td>
                            <td class="px-4 py-2 text-sm">{{ optional($o->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2 text-sm">
                                <button wire:click="toggle({{ $o->id }})" class="text-indigo-600 hover:underline">
                                    {{ $expandedOrderId === $o->id ? 'Ocultar líneas' : 'Ver líneas' }}
                                </button>
                            </td>
                        </tr>
                        @if ($expandedOrderId === $o->id)
                            <tr>
                                <td colspan="7" class="px-4 py-2">
                                    <div class="bg-gray-50 border rounded p-3">
                                        <div class="text-sm font-medium mb-2">Líneas de la orden #{{ $o->id }}</div>
                                        @if ($o->lines->isEmpty())
                                            <div class="text-sm text-gray-600">Sin líneas.</div>
                                        @else
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-200">
                                                    <thead class="bg-white">
                                                        <tr>
                                                            <th class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                                                            <th class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                                            <th class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                                                            <th class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase">Precio</th>
                                                            <th class="px-3 py-1 text-left text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200">
                                                        @foreach ($o->lines as $line)
                                                            <tr>
                                                                <td class="px-3 py-1 text-sm">{{ optional($line->variant)->fullName }}</td>
                                                                <td class="px-3 py-1 text-sm">{{ optional($line->variant)->sku }}</td>
                                                                <td class="px-3 py-1 text-sm">{{ $line->quantity }}</td>
                                                                <td class="px-3 py-1 text-sm">{{ number_format($line->price ?? 0, 2) }}</td>
                                                                <td class="px-3 py-1 text-sm">{{ number_format($line->subtotal ?? 0, 2) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-2">
            {{ $orders->links() }}
        </div>
    @endif
</div>