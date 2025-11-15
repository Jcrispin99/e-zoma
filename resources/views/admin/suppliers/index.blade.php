<x-admin-layout title="Proveedores" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Proveedores',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.suppliers.index'),
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
    @can('create_suppliers')
    <x-slot name="action">
        <x-wire-button href="{{ route('admin.suppliers.create') }}" green>
            Nuevo
        </x-wire-button>
    </x-slot>
    @endcan
    @livewire('admin.datatables.supplier-table')

</x-admin-layout>
