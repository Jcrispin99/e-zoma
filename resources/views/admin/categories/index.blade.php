<x-admin-layout title="Categorias" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Categorias',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.categories.index'),
    ],
]">
    @can('create_categories')
    <x-slot name="action">
        <x-wire-button href="{{ route('admin.categories.create') }}" wire:navigate green>
            Nuevo
        </x-wire-button>
    </x-slot>
    @endcan
    @livewire('admin.datatables.category-table')

</x-admin-layout>