<div class="space-y-4">
    <div class="flex items-center justify-between">

        <div class="flex items-center gap-2">
            <x-wire-button color="primary" icon="check" spinner wire:click="save">
                {{ $isEditing ? 'Actualizar' : 'Guardar' }}
            </x-wire-button>
            <x-wire-button color="secondary" icon="x-mark" href="{{ route('admin.attributes.index') }}" wire:navigate>
                Cancelar
            </x-wire-button>
        </div>

        <h2 class="text-lg font-semibold">
            {{ $isEditing ? 'Editar atributo' : 'Nuevo atributo' }}
        </h2>
    </div>

    <form wire:submit.prevent="save" class="space-y-4">
        <x-wire-input label="Nombre" wire:model.defer="name" placeholder="Nombre del atributo" />

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Valores del Atributo</label>
            <div class="space-y-2">
                @foreach ($values as $index => $item)
                    <div class="flex items-center gap-2">
                        <x-wire-input class="flex-1" wire:model.defer="values.{{ $index }}.value" placeholder="Valor" />
                        <x-wire-button color="negative" icon="trash" compact type="button" wire:click="removeValue({{ $index }})" />
                    </div>
                @endforeach
            </div>
            <x-wire-button type="button" color="primary" icon="plus" class="mt-2" wire:click="addValue">
                Añadir Valor
            </x-wire-button>
        </div>
    </form>
</div>