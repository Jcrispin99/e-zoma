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
        'name' => isset($category) ? 'Editar' : 'Nuevo',
    ],
]">

    <x-wire-card>
        <livewire:admin.form.category-form :categoryId="isset($category) ? $category->id : null" />
    </x-wire-card>

</x-admin-layout>