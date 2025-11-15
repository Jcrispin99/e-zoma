<x-admin-layout title="Productos" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Productos',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.products.index'),
    ],
]">
    @push('css')
    <style>
        table th span,
        table td {
            font-size: 0.75rem !important;
        }

        .image-product {
            width: 5rem;
            height: 2.5rem;
            object-fit: cover;
            object-position: center;
        }
    </style>
    @endpush

    <x-slot name="action">
        {{-- @can('import_products')
        <x-wire-button href="{{ route('admin.products.import') }}" green>
            <i class="fas fa-file-import""></i>
                Importar
            </x-wire-button>
        @endcan --}}

        @can('create_products')
            <x-wire-button href=" {{ route('admin.products.create') }}" blue>
                <i class="fas fa-plus""></i>
                Nuevo
            </x-wire-button>
        @endcan
    </x-slot>
    @livewire('admin.datatables.product-table')

</x-admin-layout>