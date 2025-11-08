<x-admin-layout title="Configuración de POS" :breadcrumbs="[
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
        'name' => isset($posConfig) ? 'Editar' : 'Nuevo',
    ],
]">

    <x-wire-card>
        <livewire:admin.form.pos-config-form :posConfigId="isset($posConfig) ? $posConfig->id : null" />
    </x-wire-card>

</x-admin-layout>