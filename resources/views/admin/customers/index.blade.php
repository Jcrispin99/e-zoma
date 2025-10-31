<x-admin-layout title="Clientes" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Clientes',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.customers.index'),
    ],
]">
    @push('css')
        <style>
            table th span,
            table td {
                font-size: 0.75rem !important;
            }
        </style>
    @endpush
    <x-slot name="action">
        <x-wire-button href="{{ route('admin.customers.create') }}" green>
            Nuevo
        </x-wire-button>
    </x-slot>
    @livewire('admin.datatables.customer-table')

</x-admin-layout>
