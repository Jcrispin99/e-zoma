<x-admin-layout title="Almacenes" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Almacenes',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.warehouses.index'),
    ],
    [
        'name' => 'Nuevo',
    ],
]">

    <x-wire-card>

        <form action="{{ route('admin.warehouses.store') }}" method="post" class="space-y-4">

            @csrf
            <x-wire-input label="Nombre" name="name" placeholder="Nombre del almacen" value="{{ old('name') }}" />
            <x-wire-input label="Ubicación" name="location" placeholder="Ubicación del almacen"
                value="{{ old('location') }}" />
            <div class="flex justify-end">
                <x-wire-button type="submit" green label="Guardar" />
            </div>
        </form>

    </x-wire-card>

</x-admin-layout>
