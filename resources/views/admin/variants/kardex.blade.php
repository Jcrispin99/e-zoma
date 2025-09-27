<x-admin-layout title="Kardex" :breadcrumbs="[
    [
        'name' => 'Kardex',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Variantes',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.variants.index'),
    ],
    [
        'name' => 'Kardex',
    ],
]">

    @livewire('admin.products.kardex', ['variant' => $variant])

</x-admin-layout>