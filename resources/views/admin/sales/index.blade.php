<x-admin-layout title="Ventas" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Ventas',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.sales.index'),
    ],
]">
    <x-slot name="action">
        <x-wire-button href="{{ route('admin.sales.create') }}" green>
            Nuevo
        </x-wire-button>
    </x-slot>
    @livewire('admin.datatables.sale-table')

</x-admin-layout>
