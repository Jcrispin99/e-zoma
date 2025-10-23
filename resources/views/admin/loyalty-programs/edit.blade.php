<x-admin-layout title="Programas de Lealtad" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Programas de Lealtad',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.loyalty-programs.index'),
    ],
    [
        'name' => 'Editar',
    ],
]">
    <x-wire-card>
        @livewire('admin.loyalty.loyalty-program-edit', ['program' => $program])
    </x-wire-card>
</x-admin-layout>
