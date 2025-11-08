<x-admin-layout title="Ordenes de Compra" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Ordenes de Compra',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.purchases-orders.index'),
    ],
    [
        'name' => 'Nuevo',
    ],
]">

    @livewire('admin.form.purchase-order-form', ['mode' => 'create'])

</x-admin-layout>
