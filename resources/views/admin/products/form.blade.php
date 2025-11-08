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
    [
        'name' => isset($product) ? 'Editar' : 'Nuevo',
    ],
]">

    <x-wire-card>
        <livewire:admin.form.product-form :productId="isset($product) ? $product->id : null" />
    </x-wire-card>

</x-admin-layout>