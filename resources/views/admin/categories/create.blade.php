<x-admin-layout title="CAtegorias" :breadcrumbs="[
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
    [
        'name' => 'Nuevo',
    ],
]">

    <x-wire-card>
        @livewire('admin.categories.category-form')
    </x-wire-card>

</x-admin-layout>