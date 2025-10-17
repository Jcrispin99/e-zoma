<div x-data="{
    variants: @entangle('variants'),
    total: @entangle('total'),
    removeVariant(index) { this.variants.splice(index, 1); },
    init() { this.$watch('variants', (vs) => { let t = 0; vs.forEach(v => t += Number(v.quantity||0)*Number(v.price||0)); this.total = t; }); }
}">
    <x-wire-card class="mb-3">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-2">
                <x-wire-badge :label="'COTIZACIÓN'" color="slate" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-wire-button light gray label="Enviar cotización por correo" wire:click="openModal({{ $quote }})">
                    <i class="fa-solid fa-envelope"></i>
                </x-wire-button>

                <x-wire-button light gray href="{{ route('admin.quotes.pdf', $quote) }}">
                    descargar
                </x-wire-button>

                <div class="flex items-center gap-2">
                    @if(!$quote->sales()->exists())
                    <x-wire-button light gray href="{{ route('admin.sales.create', ['quote_id' => $quote->id]) }}">
                        Crear venta
                    </x-wire-button>
                    @else
                    <x-wire-button light gray href="{{ route('admin.sales.edit', $quote->sales()->latest()->value('id')) }}" label="Ver venta" />

                    @endif
                </div>

                <x-wire-button light gray :href="route('admin.quotes.index')" label="Volver" />
            </div>
        </div>
    </x-wire-card>

    <x-wire-card>
        <form wire:submit="save" class="space-y-4">
            <div class="grid lg:grid-cols-4 gap-4">
                <x-wire-native-select label="Tipo de Documento" wire:model="voucher_type">
                    <option value="1">Factura</option>
                    <option value="2">Boleta</option>
                </x-wire-native-select>


                <x-wire-input label="Correlativo" wire:model="correlative" disabled />
                <x-wire-input label="Fecha" wire:model.live="date" type="date" />
            </div>

            <div class="col-span-2">
                <x-wire-select label="Cliente" wire:model="customer_id" placeholder="Seleccione un cliente" :async-data="['api' => route('api.customers.index'), 'method' => 'POST']" option-label="name" option-value="id" class="flex-1" option-description="description" />
            </div>

            <div class="lg:flex lg:space-x-4">
                <x-wire-select label="Producto" wire:model="variant_id" placeholder="Seleccione un producto" :async-data="['api' => route('api.product.index'), 'method' => 'POST']" option-label="name" option-value="id" class="flex-1" />
                <div class="flex-shrink-0">
                    <x-wire-button wire:click="addProduct" class="mt-4 w-full lg:mt-6.5" spinner>Agregar producto</x-wire-button>
                </div>
            </div>

            <div class="overflow-x-auto w-full">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="text-gray-700 border-y bg-blue-50">
                            <th class="px-6 py-2">Producto</th>
                            <th class="px-6 py-2">Cantidad</th>
                            <th class="px-6 py-2">Precio</th>
                            <th class="px-6 py-2">Subtotal</th>
                            <th class="px-6 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(variant, index) in variants" :key="variant.id">
                            <tr class="border-b">
                                <td class="px-4 py-1" x-text="variant.name" />
                                <td class="px-4 py-1">
                                    <x-wire-input type="number" x-model="variant.quantity" />
                                </td>
                                <td class="px-4 py-1">
                                    <x-wire-input type="number" x-model="variant.price" step="0.01" class="w-20" />
                                </td>
                                <td class="px-4 py-1" x-text="(Number(variant.quantity||0) * Number(variant.price||0)).toFixed(2)"></td>
                                <td class="px-4 py-1">
                                    <x-wire-mini-button rounded x-on:click="removeVariant(index)" icon="trash" red />
                                </td>
                            </tr>
                        </template>
                        <template x-if="variants.length === 0">
                            <tr>
                                <td colspan="5" class="text-center text-gray-500 py-4">No hay productos agregados</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="fex items-center space-x-4">
                <x-label>Observaciones</x-label>
                <x-wire-input wire:model="observation" placeholder="Ingrese observaciones" class="flex-1" />
            </div>
            <div>
                Total: $<span x-text="Number(total ?? 0).toFixed(2)"></span>
            </div>

            <div>
                <x-wire-button type="submit" icon="check" spinner>Guardar</x-wire-button>
            </div>
        </form>
    </x-wire-card>

    <x-wire-modal-card wire:model="form.open" width="lg">
        <x-slot name="title">
            <p class="text-xl text-center mb-2">Enviar email</p>
            <p class="text-lg text-center uppercase font-bold mb-2">{{ $form['document'] }}</p>
            <p class="text-lg text-center mb-2">{{ $form['client'] }}</p>
        </x-slot>

        <form wire:submit="sendEmail">
            <x-wire-input label="Correo" wire:model="form.email" class="mb-4" value="{{ $form['email'] }}" />
            <x-wire-button type="submit" class="w-full">
                Enviar
            </x-wire-button>
        </form>
    </x-wire-modal-card>
</div>
