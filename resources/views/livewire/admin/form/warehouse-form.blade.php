<div class="space-y-4">
    <div class="flex items-center justify-between">

        <div class="flex items-center gap-2">
            <x-wire-button color="primary" icon="check" spinner wire:click="save">
                {{ $isEditing ? 'Actualizar' : 'Guardar' }}
            </x-wire-button>
            @if($redirectAfterSave)
            <x-wire-button color="secondary" icon="x-mark" href="{{ route('admin.warehouses.index') }}">
                Cancelar
            </x-wire-button>
            @endif
        </div>

        <h2 class="text-lg font-semibold">
            {{ $isEditing ? 'Editar almacén' : 'Nuevo almacén' }}
        </h2>
    </div>

    <form wire:submit.prevent="save" class="space-y-4">
        <x-wire-input label="Nombre" wire:model.defer="name" placeholder="Nombre del almacén" />
        <x-wire-input label="Ubicación" wire:model.defer="location" placeholder="Ubicación del almacén" />
    </form>
</div>