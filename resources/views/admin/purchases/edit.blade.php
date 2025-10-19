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
        'name' => 'Editar',
    ],
]">
    <x-wire-card>
        @livewire('admin.purchases.purchase-edit', ['purchase' => $purchase])
    </x-wire-card>
</x-admin-layout>
