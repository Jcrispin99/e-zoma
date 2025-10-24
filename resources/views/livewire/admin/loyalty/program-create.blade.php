<div>
    <x-wire-card title="Nuevo programa de lealtad">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-wire-input
                wire:model.lazy="name"
                type="text"
                label="Nombre"
                placeholder="Ingresa el nombre"
                required
            />

            <x-wire-input
                wire:model.lazy="code"
                type="text"
                label="Código"
                placeholder="Código único"
                required
            />

            <!-- Reemplazar select Tipo por nativo -->
            <x-wire-native-select wire:model="type" label="Tipo">
                <option value="">Seleccione un tipo</option>
                <option value="promotion">Promoción</option>
                <option value="points">Puntos</option>
            </x-wire-native-select>

            <!-- Reemplazar select Ámbito por nativo -->
            <x-wire-native-select wire:model="scope" label="Ámbito">
                <option value="">Seleccione un ámbito</option>
                <option value="pos">POS</option>
                <option value="sales">Ventas</option>
                <option value="both">Ambos</option>
            </x-wire-native-select>

            <x-wire-toggle wire:model="is_active" label="Activo" />

            <x-wire-input
                wire:model="valid_from"
                type="date"
                label="Válido desde"
            />

            <x-wire-input
                wire:model="valid_to"
                type="date"
                label="Válido hasta"
            />
        </div>

        <div class="mt-6 flex items-center gap-3">
            <x-wire-button color="primary" wire:click="save">
                Guardar
            </x-wire-button>
            <x-wire-button color="secondary" href="{{ route('admin.loyalty-programs.index') }}">
                Cancelar
            </x-wire-button>
        </div>
    </x-wire-card>
</div>