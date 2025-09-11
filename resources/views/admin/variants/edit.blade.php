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

        <form action="{{ route('admin.variants.update', $variant) }}" method="post" class="space-y-4">

            @csrf
            @method('put')

            <x-wire-input label="Nombre" name="name" placeholder="Nombre del producto"
                value="{{ old('name', $variant->name) }}" />
            <x-wire-textarea label="Descripción" name="description" placeholder="Descripción del producto">
                {{ old('description', $variant->description) }}
            </x-wire-textarea>

            <x-wire-input type="number" label="Precio" name="price" placeholder="Precio del producto"
                value="{{ old('price', $variant->price) }}" />

            <div class="flex justify-end">
                <x-button type="submit">
                    Actualizar
                </x-button>
            </div>

        </form>

    </x-wire-card>

</x-admin-layout>
