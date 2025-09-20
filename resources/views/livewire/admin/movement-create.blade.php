<div x-data="{
    variants: @entangle('variants'),

    total: @entangle('total'),

    removeVariant(index) {
        this.variants.splice(index, 1);
    },

    init() {
        this.$watch('variants', (newVariants) => {

            let total = 0;
            newVariants.forEach(variant => {
                total += variant.quantity * variant.price;
            });
            this.total = total;
        });
    }
}">

    <x-wire-card>

        <form wire:submit="save" class="space-y-4">

            <div class="grid lg:grid-cols-4 gap-4">
                <x-wire-native-select label="Tipo de Movimiento" wire:model.live="type">
                    <option value="1">Ingreso</option>
                    <option value="2">Salida</option>
                </x-wire-native-select>

                <x-wire-input label="Serie" wire:model="serie" placeholder="Serie del comprobante" disabled />
                <x-wire-input label="Correlativo" wire:model="correlative" placeholder="Correlativo del comprobante"
                    disabled />

                <x-wire-input label="Fecha" wire:model="date" type="date" />

                <x-wire-select class="lg:col-span-2" label="Almacenes" wire:model="warehouse_id"
                    placeholder="Seleccione un almacén" :async-data="[
                        'api' => route('api.warehouse.index'),
                        'method' => 'POST',
                    ]" option-label="name" option-value="id"
                    class="flex-1" option-description="description" />

                <x-wire-select class="lg:col-span-2" label="Motivo" wire:model="reason_id"
                    placeholder="Seleccione un motivo" :async-data="[
                        'api' => route('api.reasons.index'),
                        'method' => 'POST',
                        'params' => [
                            'type' => $type,
                        ],
                    ]" option-label="name" option-value="id"
                    class="flex-1" option-description="description" />
            </div>



            <div class="lg:flex lg:space-x-4">
                <x-wire-select label="Producto" wire:model="variant_id" placeholder="Seleccione un producto"
                    :async-data="[
                        'api' => route('api.product.index'),
                        'method' => 'POST',
                    ]" option-label="name" option-value="id" class="flex-1" />

                <div class="flex-shrink-0">

                    <x-wire-button wire:click="addProduct" class="mt-4 w-full lg:mt-6.5" spinner>
                        Agregar producto
                    </x-wire-button>

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
                                    <x-wire-input type="number" x-model="variant.price" step="0.01"
                                        class="w-20" />
                                </td>
                                <td class="px-4 py-1" x-text="(variant.quantity * variant.price).toFixed(2)"></td>
                                <td class="px-4 py-1">
                                    <x-wire-mini-button rounded x-on:click="removeVariant(index)" icon="trash" red />
                                </td>
                            </tr>
                        </template>
                        <template x-if="variants.length === 0">
                            <tr>
                                <td colspan="5" class="text-center text-gray-500 py-4">No hay productos agregados
                                </td>
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
                Total: $<span x-text="total.toFixed(2)"></span>
            </div>

            <x-wire-button type="submit" icon="check" spinner>
                Guardar
            </x-wire-button>
        </form>
    </x-wire-card>
</div>
