<div class="space-y-3">
    <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold">Sesiones del POS</h3>
        <div class="text-sm text-gray-600">Caja #{{ request()->route('posConfig')->id ?? '' }}</div>
    </div>

    @if ($sessions->isEmpty())
    <div class="border rounded p-3 text-sm text-gray-600">No hay sesiones registradas para esta caja.</div>
    @else
    <div class="overflow-x-auto border rounded">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Apertura</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Saldo apertura</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Saldo cierre</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cierre</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($sessions as $s)
                <tr>
                    <td class="px-4 py-2 text-sm">{{ $s->id }}</td>
                    <td class="px-4 py-2 text-sm">{{ $s->user->name ?? ('#'.$s->user_id) }}</td>
                    <td class="px-4 py-2 text-sm">{{ optional($s->opened_at)->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-2 text-sm">{{ number_format($s->opening_balance ?? 0, 2) }}</td>
                    <td class="px-4 py-2 text-sm">{{ $s->closing_balance !== null ? number_format($s->closing_balance, 2) : '—' }}</td>
                    <td class="px-4 py-2 text-sm">{{ $s->closed_at ? optional($s->closed_at)->format('d/m/Y H:i') : '—' }}</td>
                    <td class="px-4 py-2 text-sm">
                        @if($s->status === 'open' && !$s->closed_at)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">Abierta</span>
                        @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700">Cerrada</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-sm">
                        @if($s->status === 'open' && !$s->closed_at)
                        <a href="/pos/{{ $s->id }}" class="text-blue-600 hover:underline">Ir a POS</a>
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-2">
        {{ $sessions->links() }}
    </div>
    @endif
</div>
