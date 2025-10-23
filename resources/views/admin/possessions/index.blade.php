<x-admin-layout title="Sesiones de POS" :breadcrumbs="[
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
    ],
]">
    @livewire('admin.pos.sessions-list', ['posConfigId' => $posConfig->id])
</x-admin-layout>
