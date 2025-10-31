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
        'name' => 'Editar',
    ],
]">

    <div x-data="attributeValuesManager({{ $attribute->attributeValues->map(fn($v) => ['id' => $v->id, 'value' => $v->value]) }})">
        <x-wire-card>

            <form action="{{ route('admin.attributes.update', $attribute) }}" method="post" class="space-y-4">

                @csrf
                @method('put')
                <x-wire-input label="Nombre" name="name" placeholder="Nombre del atributo"
                    value="{{ old('name', $attribute->name) }}" />


                {{-- Attribute Values --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Valores del Atributo</label>
                    <div class="space-y-2">
                        <template x-for="(item, index) in values" :key="index">
                            <div class="flex items-center space-x-2">
                                <input type="hidden" :name="'values[' + index + '][id]'" x-model="item.id">
                                <input
                                    class="flex-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300"
                                    type="text" :name="'values[' + index + '][value]'" x-model="item.value" placeholder="Valor">
                                <button type="button" @click="removeValue(index)" class="text-red-500 hover:text-red-700">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                    <x-button type="button" @click="addValue()" class="mt-2">
                        <i class="fa-solid fa-plus mr-2"></i>
                        Añadir Valor
                    </x-button>
                </div>


                <div class="flex justify-end">
                    <x-button type="submit">
                        Actualizar
                    </x-button>
                </div>

            </form>

        </x-wire-card>
    </div>

    @push('js')
    <script>
        function attributeValuesManager(initialValues) {
            return {
                values: initialValues || [],
                addValue() {
                    this.values.push({ id: null, value: '' });
                },
                removeValue(index) {
                    this.values.splice(index, 1);
                }
            }
        }
    </script>
    @endpush

</x-admin-layout>
