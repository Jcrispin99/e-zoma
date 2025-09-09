<x-admin-layout 
title="CAtegorias"
:breadcrumbs="[
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
    <x-slot name="action">
        <x-wire-button href="{{ route('admin.categories.create') }}" green>
            Nuevo
        </x-wire-button>
    </x-slot>
 @livewire('admin.datatables.category-table')
</x-admin-layout>
