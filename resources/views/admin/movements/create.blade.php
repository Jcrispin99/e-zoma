<x-admin-layout title="Movimientos" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Movimientos',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.movements.index'),
    ],
    [
        'name' => 'Nuevo',
    ],
]">

    @livewire('admin.movements.movement-create')

</x-admin-layout>
