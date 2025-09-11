<div>
    {{-- Botones de pestañas --}}
    <div class="flex gap-2 mb-4">
        <x-wire-button flat primary label="Producto" wire:click="setTab('product')" :class="$tab === 'product' ? 'bg-teal-300/60' : ''" />
        <x-wire-button flat primary label="Atributos" wire:click="setTab('attribute')" :class="$tab === 'attribute' ? 'bg-teal-300/60' : ''" />
    </div>

    {{-- Contenido dinámico --}}
    @if ($tab === 'product')
        <form wire:submit.prevent="savePersona" class="space-y-3">
            <x-wire-input label="Nombres" wire:model="nombres" placeholder="Ingrese los nombres" />
            <x-wire-input label="Apellidos" wire:model="apellidos" placeholder="Ingrese los apellidos" />
            <x-wire-input label="DNI" wire:model="dni" placeholder="Ingrese el DNI" />
            <div class="flex justify-end">
                <x-wire-button label="Guardar" primary type="submit" />
            </div>
        </form>
    @elseif ($tab === 'attribute')
        <form wire:submit.prevent="saveEmpresa" class="space-y-3">
            <x-wire-input label="Razón Social" wire:model="razon_social" placeholder="Ingrese la razón social" />
            <x-wire-input label="RUC" wire:model="ruc" placeholder="Ingrese el RUC" />
            <x-wire-input label="Contacto" wire:model="contacto" placeholder="Ingrese el contacto" />
            <div class="flex justify-end">
                <x-wire-button label="Guardar" primary type="submit" />
            </div>
        </form>
    @endif

    {{-- Mostrar mensajes de éxito --}}
    @if (session()->has('message'))
        <div class="mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('message') }}
        </div>
    @endif
</div>
