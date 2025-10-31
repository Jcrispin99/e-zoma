<x-admin-layout title="Movimientos" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Movimientos',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.movements.index'),
    ],
]">
    <x-slot name="action">
        <x-wire-button href="{{ route('admin.movements.create') }}" green>
            Nuevo
        </x-wire-button>
    </x-slot>
    @livewire('admin.datatables.movement-table')

</x-admin-layout>
