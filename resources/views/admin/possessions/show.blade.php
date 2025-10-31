<x-admin-layout title="Detalle de sesión POS" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Configuración de POS',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.posconfig.index'),
    ],
    [
        'name' => 'Sesiones',
        'href' => route('admin.posconfig.sessions', ['posConfig' => $posSession->pos_config_id]),
    ],
    [
        'name' => 'Sesión #' . $posSession->id,
    ],
]">
    <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="border rounded p-3 text-sm">
            <div class="text-gray-500">Usuario</div>
            <div class="font-medium">{{ $posSession->user->name ?? ('#'.$posSession->user_id) }}</div>
        </div>
        <div class="border rounded p-3 text-sm">
            <div class="text-gray-500">Apertura</div>
            <div class="font-medium">{{ optional($posSession->opened_at)->format('d/m/Y H:i') }}</div>
        </div>
        <div class="border rounded p-3 text-sm">
            <div class="text-gray-500">Cierre</div>
            <div class="font-medium">{{ $posSession->closed_at ? optional($posSession->closed_at)->format('d/m/Y H:i') : '—' }}</div>
        </div>
        <div class="border rounded p-3 text-sm">
            <div class="text-gray-500">Estado</div>
            <div class="font-medium">{{ $posSession->status === 'open' && !$posSession->closed_at ? 'Abierta' : 'Cerrada' }}</div>
        </div>
    </div>

    @livewire('admin.pos.session-orders', ['sessionId' => $posSession->id])
</x-admin-layout>
