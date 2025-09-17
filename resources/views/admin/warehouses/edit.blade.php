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
        'name' => 'Editar',
    ],
]">

    <x-wire-card>

        <form action="{{ route('admin.warehouses.update', $warehouse) }}" method="post" class="space-y-4">

            @csrf
            @method('put')
            <x-wire-input label="Nombre" name="name" placeholder="Nombre del almacen"
                value="{{ old('name', $warehouse->name) }}" />
            <x-wire-input label="Ubicación" name="location" placeholder="Ubicación del almacen"
                value="{{ old('location', $warehouse->location) }}" />

            <div class="flex justify-end">
                <x-button type="submit">
                    Actualizar
                </x-button>
            </div>

        </form>

    </x-wire-card>

</x-admin-layout>
