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
        'name' => 'Nuevo',
    ]
]">

    <x-wire-card>

        <form action="{{ route('admin.categories.store') }}" method="post" class="space-y-4">

            @csrf
            <x-wire-input label="Nombre" name="name" placeholder="Nombre de la categoría" />
            <x-wire-textarea label="Descripción" name="description" placeholder="Descripción de la categoría">
                {{ old('description') }}
            </x-wire-textarea >
            
            <x-wire-select
                label="Categoría padre"
                placeholder="Seleccione una opción"
                :options="$categories"
                option-label="name"
                option-value="id"
                name="parent_id"
            />
            <div class="flex justify-end">
                <x-button type="submit">
                    Guardar
                </x-button>
            </div>

        </form>

    </x-wire-card>

</x-admin-layout>
