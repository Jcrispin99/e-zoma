<div x-data="{
    showTaxDetails: @entangle('apply_tax').live
}">
    <!-- Header con acciones -->
    <x-wire-card class="mb-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <x-wire-button color="primary" icon="check" spinner wire:click="save">
                    {{ $isEditing ? 'Actualizar' : 'Guardar' }}
                </x-wire-button>
                <x-wire-button color="secondary" icon="x-mark" href="{{ route('admin.posconfig.index') }}"
                    wire:navigate>
                    Cancelar
                </x-wire-button>
            </div>

            <div class="flex items-center gap-3">
                <h2 class="text-lg font-semibold text-gray-800">
                    {{ $isEditing ? 'Editar Configuración POS' : 'Nueva Configuración POS' }}
                </h2>
                @if($isEditing)
                <x-wire-badge :label="$is_active ? 'ACTIVO' : 'INACTIVO'" :color="$is_active ? 'emerald' : 'slate'" />
                @endif
            </div>
        </div>
    </x-wire-card>

    <!-- Formulario Principal -->
    <x-wire-card class="border-2 border-gray-100">
        <form wire:submit.prevent="save" class="space-y-6" x-on:keydown.enter.prevent>

            <!-- Sección: Información General -->
            <div class="space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-gray-200">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-base font-semibold text-gray-700">Información General</h3>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <x-wire-input label="Nombre de la Configuración" wire:model.defer="name"
                        placeholder="Ej: POS Principal - Tienda 1"
                        hint="Identifica esta configuración de punto de venta" />

                    <x-wire-select label="Almacén" wire:model.defer="warehouse_id" placeholder="Seleccione un almacén"
                        :options="$warehouseOptions" option-label="name" option-value="id"
                        hint="Almacén desde donde se venderán los productos" />

                    <x-wire-select label="Cliente por Defecto" wire:model.defer="default_customer_id"
                        placeholder="Seleccione un cliente" :options="$customerOptions" option-label="name"
                        option-value="id" hint="Cliente que se usará cuando no se especifique uno" />

                    <div class="flex items-center pt-6">
                        <x-wire-toggle wire:model.defer="is_active" label="Configuración Activa"
                            hint="Solo configuraciones activas pueden usarse en el POS" />
                    </div>
                </div>
            </div>

            <!-- Sección: Configuración de Diarios -->
            <div class="space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-gray-200">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="text-base font-semibold text-gray-700">Diarios de Comprobantes</h3>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <x-wire-select label="Diario de Boletas" wire:model.defer="receipt_journal_id"
                        :options="$journalOptions" option-label="label" option-value="id"
                        placeholder="Seleccione un diario" hint="Diario para emitir boletas de venta (03)" />

                    <x-wire-select label="Diario de Facturas" wire:model.defer="invoice_journal_id"
                        :options="$journalOptions" option-label="label" option-value="id"
                        placeholder="Seleccione un diario" hint="Diario para emitir facturas (01)" />
                </div>
            </div>

            <!-- Sección: Configuración de Impuestos -->
            <div class="space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-gray-200">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <h3 class="text-base font-semibold text-gray-700">Configuración de Impuestos (IGV)</h3>
                </div>

                <div class="space-y-4">
                    <!-- Toggle principal de impuestos -->
                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <x-wire-toggle wire:model.live="apply_tax" label="Aplicar IGV en las ventas"
                            hint="Activar para incluir el Impuesto General a las Ventas" />
                    </div>

                    <!-- Detalles de impuestos (se muestra solo si apply_tax está activo) -->
                    <div x-show="showTaxDetails" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-4">

                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div class="lg:col-span-2">
                                <x-wire-select label="Impuesto por Defecto" wire:model.live="default_tax_id"
                                    placeholder="Sin impuesto por defecto" :options="$taxOptions" option-label="label"
                                    option-value="id" :clearable="true"
                                    hint="Seleccione el impuesto que se aplicará automáticamente" />

                                <div class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-md">
                                    <p class="text-xs text-amber-800 flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <span>Al seleccionar un impuesto del catálogo, se sincronizarán automáticamente
                                            la tasa y la configuración de precios con IGV incluido.</span>
                                    </p>
                                </div>
                            </div>

                            <x-wire-input type="number" step="0.01" min="0" max="1" label="Tasa de IGV"
                                wire:model="tax_rate" readonly hint="0.18 = 18% (sincronizado automáticamente)"
                                prefix="%" />

                            <div class="flex items-center pt-6">
                                <x-wire-toggle wire:model.defer="prices_include_tax" label="Precios incluyen IGV (TTC)"
                                    hint="Los precios mostrados ya tienen el impuesto incluido" />
                            </div>
                        </div>

                        <!-- Ejemplo visual del cálculo -->
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Ejemplo de cálculo:</h4>
                            <div class="text-sm text-gray-600 space-y-1">
                                <template x-if="!@js($prices_include_tax)">
                                    <div>
                                        <p>• Precio sin IGV: S/ 100.00</p>
                                        <p>• IGV ({{ number_format($tax_rate * 100, 0) }}%): S/ {{ number_format(100 *
                                            $tax_rate, 2) }}</p>
                                        <p class="font-semibold text-gray-800">• Total a pagar: S/ {{ number_format(100
                                            * (1 + $tax_rate), 2) }}</p>
                                    </div>
                                </template>
                                <template x-if="@js($prices_include_tax)">
                                    <div>
                                        <p class="font-semibold text-gray-800">• Precio con IGV incluido: S/ 100.00</p>
                                        <p>• Base imponible: S/ {{ number_format(100 / (1 + $tax_rate), 2) }}</p>
                                        <p>• IGV ({{ number_format($tax_rate * 100, 0) }}%): S/ {{ number_format(100 -
                                            (100 / (1 + $tax_rate)), 2) }}</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </x-wire-card>
</div>