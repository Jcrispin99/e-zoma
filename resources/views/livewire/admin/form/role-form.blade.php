<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <x-wire-button color="primary" icon="check" spinner wire:click="save">
                {{ $isEditing ? 'Actualizar' : 'Guardar' }}
            </x-wire-button>
            <x-wire-button color="secondary" icon="x-mark" href="{{ route('admin.roles.index') }}" wire:navigate>
                Cancelar
            </x-wire-button>
        </div>
        <h2 class="text-lg font-semibold">
            {{ $isEditing ? 'Editar rol' : 'Nuevo rol' }}
        </h2>
    </div>

    <form wire:submit.prevent="save" class="space-y-4">
        <x-wire-input label="Nombre" wire:model.defer="name" />

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Permisos</label>
            <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 overflow-auto border rounded-md p-3">
                @foreach ($permissionOptions as $perm)
                <li class="flex items-center gap-2">
                    <input type="checkbox"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        wire:model.defer="selectedPermissions" value="{{ $perm['id'] }}">
                    <span class="text-sm">{{ $perm['name'] }}</span>
                </li>
                @endforeach
            </ul>
        </div>
    </form>
</div>