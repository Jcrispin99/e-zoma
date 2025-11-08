<x-admin-layout title="Variantes" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Variantes',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.variants.index'),
    ],
    [
        'name' => 'Editar',
    ],
]">

    <x-wire-card>
        <livewire:admin.form.variant-form :variantId="$variant->id" />
    </x-wire-card>

</x-admin-layout>