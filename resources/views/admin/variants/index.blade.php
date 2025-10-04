<x-admin-layout title="Variantes" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Variantes',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.variants.index'),
    ],
]">
    @push('css')
        <style>
            table th span,
            table td {
                font-size: 0.75rem !important;
            }

            .image-product {
                width: 2.5rem;
                height: 2.5rem;
                object-fit: cover;
                object-position: center;
            }
        </style>
    @endpush

    <x-slot name="action">
        <x-wire-button href="{{ route('admin.products.create') }}" green>
            Nuevo
        </x-wire-button>
    </x-slot>
    @livewire('admin.datatables.variant-table')

</x-admin-layout>
