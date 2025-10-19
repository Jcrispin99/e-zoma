<x-admin-layout title="Ventas" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Ventas',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.sales.index'),
    ],
    [
        'name' => 'Editar',
    ],
]">
    @livewire('admin.sales.sale-edit', compact('sale'))

</x-admin-layout>
