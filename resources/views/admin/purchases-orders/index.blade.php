<x-admin-layout title="Ordenes de Compra" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Ordenes de Compra',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.purchases-orders.index'),
    ],
]">
    <x-slot name="action">
        @can('create_purchase-orders')
            <x-wire-button href="{{ route('admin.purchases-orders.create') }}" green>
                Nuevo
            </x-wire-button>
        @endcan
    </x-slot>
    @livewire('admin.datatables.purchase-order-table')

</x-admin-layout>
