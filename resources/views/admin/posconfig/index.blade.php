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
]">
    <x-slot name="action">
        @can('create_posconfig')
        <x-wire-button href="{{ route('admin.posconfig.create') }}" green>
            Nuevo
        </x-wire-button>
        @endcan
    </x-slot>
    @livewire('admin.datatables.posconfig-table')

</x-admin-layout>