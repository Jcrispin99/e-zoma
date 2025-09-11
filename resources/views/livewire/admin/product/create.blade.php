<div>
    {{-- Botones de pestañas --}}
    <div class="flex gap-2 mb-4">
        <x-wire-button flat primary label="Producto" wire:click="setTab('product')" :class="$tab === 'product' ? 'bg-teal-300/60' : ''" />
        <x-wire-button flat primary label="Atributos" wire:click="setTab('attribute')" :class="$tab === 'attribute' ? 'bg-teal-300/60' : ''" />
    </div>

    {{-- Contenido dinámico --}}
    @if ($tab === 'product')
        <x-wire-card>
            <form wire:submit.prevent="saveProduct" class="space-y-4">
                <x-wire-input label="Nombre" wire:model="name" placeholder="Nombre del producto" />

                <x-wire-textarea label="Descripción" wire:model="description" placeholder="Descripción del producto" />

                <x-wire-input type="float" label="Precio" wire:model="price" placeholder="Precio del producto" />

                <x-wire-native-select label="Categoría" wire:model="category_id">
                    <option value="">Seleccione una categoría</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">
                            {{ $category->full_name }}
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
    @elseif ($tab === 'attribute')
        <x-wire-card>
            <form wire:submit.prevent="saveAttributes" class="space-y-4">
                <x-wire-input label="Nombre del Atributo" wire:model="attribute_name"
                    placeholder="Ingrese el nombre del atributo" />
                <x-wire-input label="Valor del Atributo" wire:model="attribute_value"
                    placeholder="Ingrese el valor del atributo" />
                <div class="flex justify-end">
                    <x-wire-button label="Guardar" primary type="submit" />
                </div>
            </form>
        </x-wire-card>
    @endif

    {{-- Mostrar mensajes de éxito --}}
    @if (session()->has('message'))
        <div class="mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('message') }}
        </div>
    @endif
</div>
