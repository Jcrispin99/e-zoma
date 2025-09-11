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
        'name' => 'Editar',
    ],
]">

    <x-wire-card>

        <form action="{{ route('admin.categories.update', $category) }}" method="post" class="space-y-4">

            @csrf
            @method('put')
            <x-wire-input label="Nombre" name="name" placeholder="Nombre de la categoría"
                value="{{ old('name', $category->name) }}" />
            <x-wire-textarea label="Descripción" name="description"
                placeholder="Descripción de la categoría">{{ old('description', $category->description) }}</x-wire-textarea>


            <x-wire-native-select label="Categoría padre" name="parent_id">
                <option value="">Ninguna</option>
                @foreach ($categories as $parent_category)
                    <option value="{{ $parent_category->id }}" @selected(old('parent_id', $category->parent_id) == $parent_category->id)>
                        {{ $parent_category->full_name }}
                    </option>
                @endforeach
            </x-wire-native-select>
            <div class="flex justify-end">
                <x-button type="submit">
                    Actualizar
                </x-button>
            </div>

        </form>

    </x-wire-card>

</x-admin-layout>
