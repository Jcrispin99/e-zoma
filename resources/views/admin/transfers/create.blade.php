<x-admin-layout title="Transferencias" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Transferencias',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.transfers.index'),
    ],
    [
        'name' => 'Nuevo',
    ],
]">

    @livewire('admin.transfer-create')

</x-admin-layout>
