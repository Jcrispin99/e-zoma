<div class="space-y-4">
    <div class="flex items-center justify-between">

        <div class="flex items-center gap-2">
            <x-wire-button color="primary" icon="check" spinner wire:click="save">
                {{ $isEditing ? 'Actualizar' : 'Guardar' }}
            </x-wire-button>
            <x-wire-button color="secondary" icon="x-mark" href="{{ route('admin.categories.index') }}">
                Cancelar
            </x-wire-button>
        </div>
        <h2 class="text-lg font-semibold">
            {{ $isEditing ? 'Editar categoría' : 'Nueva categoría' }}
        </h2>
    </div>

    <form id="categoryForm" wire:submit.prevent="save" class="space-y-4">
        <x-wire-input label="Nombre" wire:model.defer="name" />
        <x-wire-textarea label="Descripción" wire:model.defer="description" />

        <x-wire-select label="Categoría padre" placeholder="Seleccione una opción" :options="$parentOptions"
            option-label="name" option-value="id" wire:model.defer="parent_id" :clearable="true" />
    </form>
</div>