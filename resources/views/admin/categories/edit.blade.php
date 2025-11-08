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
    [
        'name' => 'Editar',
    ],
]">

    <x-wire-card>
        @livewire('admin.categories.category-form', ['categoryId' => $category->id])
    </x-wire-card>

</x-admin-layout>