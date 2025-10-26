<x-admin-layout title="Compras" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Compras',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.purchases.index'),
    ],
    [
        'name' => 'Nuevo',
    ],
]">

    @livewire('admin.purchases.purchase-create', ['purchase_order_id' => request()->get('purchase_order_id')])

</x-admin-layout>
