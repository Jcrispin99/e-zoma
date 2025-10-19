<x-admin-layout title="Ventas" :breadcrumbs="[
<x-admin-layout title=" Ventas" :breadcrumbs="[
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
        'name' => 'Nuevo',
    ],
]">

    @livewire('admin.sale-create')

</x-admin-layout>
