<div>
    <x-wire-card>
        <h1 class="text-2xl font-semibold mb-4">
            Editar Usuario
        </h1>
    </x-wire-card>

    <form wire:submit.prevent="save">
        <div class="grid grid-cols-2 gap-4">
            <x-wire-input label="Nombre" wire:model="name" required placeholder="Ingrese el nombre del usuario" />
            <x-wire-input label="Correo electrónico" wire:model="email" type="email" required placeholder="Ingrese el correo electrónico del usuario" />
            <x-wire-input label="Contraseña" wire:model="password" type="password" placeholder="Ingrese la contraseña del usuario" />
            <x-wire-input label="Confirmar contraseña" wire:model="password_confirmation" type="password" placeholder="Confirme la contraseña del usuario" />
        </div>

        <!-- Selector de Compañías -->
        <div class="mt-4">
            <div class="mb-4">
                <x-wire-select
                    label="Compañías"
                    placeholder="Selecciona una o más compañías"
                    multiselect
                    :options="$allCompanies"
                    option-label="name"
                    option-value="id"
                    wire:model="selectedCompanies"
                />
            </div>
        </div>

        <div class="flex justify-end mt-4">
            <x-wire-button type="submit" blue>
                Actualizar
            </x-wire-button>
        </div>
    </form>
</div>
