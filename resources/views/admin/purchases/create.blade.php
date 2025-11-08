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
        'name' => 'Crear',
    ],
]">
    <x-wire-card>
        @livewire('admin.form.purchase-form', ['mode' => 'create', 'purchase_order_id' => request('purchase_order_id')])
    </x-wire-card>
</x-admin-layout>
