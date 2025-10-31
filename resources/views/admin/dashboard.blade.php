<x-admin-layout 
title="Dashboard"
:breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Contenido',
        'href' => '#',
        'icon' => 'fa-regular fa-file-lines',
    ],
]">

    <div>
        <h1 class="text-3xl font-bold">Dashboard</h1>
    </div>
</x-admin-layout>
