<div class="mt-4" wire:poll.10s>
    <div class="flex items-center justify-between mb-2">
        <h3 class="text-base font-semibold">Sesiones abiertas</h3>
        <span class="text-sm text-gray-600">Caja #{{ $sessions->first()->pos_config_id ?? $posConfigId }}</span>
    </div>

    @if ($sessions->isEmpty())
    <div class="border rounded p-3 text-sm text-gray-600">No hay sesiones abiertas actualmente.</div>
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
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
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
                    <td class="px-4 py-2 text-sm">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">Abierta</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
