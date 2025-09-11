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
        'name' => 'Nuevo',
    ],
]">

    <x-wire-card>

        <form action="{{ route('admin.variants.store') }}" method="post" class="space-y-4">

            @csrf
            <x-wire-input label="Nombre" name="name" placeholder="Nombre de la variante" value="{{ old('name') }}" />
            <x-wire-textarea label="Descripción" name="description" placeholder="Descripción de la variante">
                {{ old('description') }}
            </x-wire-textarea>

            <x-wire-input type="number" label="Precio" name="price" placeholder="Precio de la variante"
                value="{{ old('price') }}" />

            <div class="flex justify-end">
                <x-button type="submit">
                    Guardar
                </x-button>
            </div>

        </form>

    </x-wire-card>

</x-admin-layout>
