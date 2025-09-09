<x-admin-layout 
title="CAtegorias"
:breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Categorias',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.categories.index'),
    ],
    [
        'name' => 'Editar',
    ]
]">

    <div>
        <h1 class="text-3xl font-bold">Dashboard</h1>
    </div>
</x-admin-layout>
