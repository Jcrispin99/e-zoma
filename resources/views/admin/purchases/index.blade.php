<x-admin-layout title="Compras" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Compras',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.purchases.index'),
    ],
]">
    <x-slot name="action">
        <x-wire-button href="{{ route('admin.purchases.create') }}" green>
            Nuevo
        </x-wire-button>
    </x-slot>
    @livewire('admin.datatables.purchase-table')

</x-admin-layout>
