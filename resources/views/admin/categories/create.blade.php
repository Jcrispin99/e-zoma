<x-admin-layout title="CAtegorias" :breadcrumbs="[
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
    ],
]">

    <x-wire-card>

        <form action="{{ route('admin.categories.store') }}" method="post" class="space-y-4">

            @csrf
            <x-wire-input label="Nombre" name="name" placeholder="Nombre de la categoría" />
            <x-wire-textarea label="Descripción" name="description" placeholder="Descripción de la categoría">
                {{ old('description') }}
            </x-wire-textarea>

            <x-wire-native-select label="Categoría padre" name="parent_id">
                <option value="">Ninguna</option>
                @foreach ($categories as $parent_category)
                    <option value="{{ $parent_category->id }}" @selected(old('parent_id') == $parent_category->id)>
                        {{ $parent_category->full_name }}
                    </option>
                @endforeach
            </x-wire-native-select>
            <div class="flex justify-end">
                <x-button type="submit">
                    Guardar
                </x-button>
            </div>

        </form>

    </x-wire-card>

</x-admin-layout>
