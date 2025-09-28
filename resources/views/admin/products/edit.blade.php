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
        'name' => 'Editar',
    ],
]">
     <x-wire-card>
        @livewire('admin.products.product-edit', ['productId' => $product->id])
    </x-wire-card>
</x-admin-layout>
