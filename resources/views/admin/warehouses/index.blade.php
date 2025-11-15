<x-admin-layout title="Almacenes" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Almacenes',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.warehouses.index'),
    ],
]">

    <x-slot name="action">
        @can('create_warehouses')
        <x-wire-button href="{{ route('admin.warehouses.create') }}" wire:navigate green>
            Nuevo
        </x-wire-button>
        @endcan
    </x-slot>

    @livewire('admin.datatables.warehouse-table')
</x-admin-layout>