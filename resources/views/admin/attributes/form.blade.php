<x-admin-layout title="Atributos" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Atributos',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.attributes.index'),
    ],
    [
        'name' => isset($attribute) ? 'Editar' : 'Nuevo',
    ],
]">

    <x-wire-card>
        <livewire:admin.form.attribute-form :attributeId="isset($attribute) ? $attribute->id : null" />
    </x-wire-card>

</x-admin-layout>