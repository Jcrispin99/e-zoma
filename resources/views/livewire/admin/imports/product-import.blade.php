<div>
    {{-- The whole world belongs to you. --}}
    <x-wire-card>
        <h1 class="text-2xl font-semibold text-gray-800 mb-6">
            Importar Productos
        </h1>

        <x-wire-button type="submit" blue wire:click="downloadTemplate">
            <i class="fas fa-file-import"></i>
            Descargar plantilla
        </x-wire-button>

        <p class="text-sm text-gray-600 mt-1">
            <span class="font-bold">Favor de descargar la plantilla para tener el formato correcto</span>
        </p>

        <div class="mt-4">

            <x-wire-input type="file" wire:model="file" accept=".xlsx,.xls" />

            <x-wire-button type="submit" blue wire:click="importProducts">
                <i class="fas fa-file-import"></i>
                Importar productos
            </x-wire-button>
        </div>

    </x-wire-card>
</div>
