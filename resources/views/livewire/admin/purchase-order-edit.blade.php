<div x-data="{
    variants: @entangle('variants'),
    total: @entangle('total'),
    subtotal: 0,
    taxTotal: 0,

    removeVariant(index) {
        this.variants.splice(index, 1);
    },

    calculateTotals() {
        let subtotal = 0;
        let taxTotal = 0;

        this.variants.forEach(variant => {
            const lineSubtotal = (variant.quantity || 0) * (variant.price || 0);
            subtotal += lineSubtotal;
            taxTotal += lineSubtotal * ((variant.tax_rate || 0) / 100);
        });

        this.subtotal = subtotal;
        this.taxTotal = taxTotal;
        this.total = subtotal + taxTotal;
    },

    init() {
        this.calculateTotals();
        this.$watch('variants', () => this.calculateTotals());
    }
}">
    <x-wire-card class="mb-3">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-2">
                <x-wire-badge :label="str($purchaseOrder->status)->upper()" :color="$purchaseOrder->status === 'draft' ? 'slate' : ($purchaseOrder->status === 'confirmed' ? 'blue' : ($purchaseOrder->status === 'done' ? 'emerald' : 'rose'))" />
                @if($purchaseOrder->billing_status)
                    <x-wire-badge :label="str($purchaseOrder->billing_status)->upper()" :color="$purchaseOrder->billing_status === 'complete' ? 'emerald' : ($purchaseOrder->billing_status === 'partial' ? 'amber' : 'slate')" />
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($purchaseOrder->status == 'draft')
                    <x-wire-button light gray label="Confirmar Orden" wire:click="confirmOrder" />
                    <x-wire-button light red label="Cancelar" wire:click="cancelOrder" />
                @elseif ($purchaseOrder->status == 'confirmed')
                    @if ($hasPurchase)
                        <x-wire-button light gray label="Ver factura" wire:click="viewPurchase" />
                    @else
                        <x-wire-button light emerald label="Crear Compra" wire:click="createPurchase" />
                    @endif
                    <x-wire-button light red label="Cancelar" wire:click="cancelOrder" />
                @endif

                <x-wire-button light gray label="Enviar OC por correo" wire:click="openModal({{ $purchaseOrder }})">
                    <i class="fa-solid fa-envelope"></i>
                </x-wire-button>

                <x-wire-button light gray href="{{ route('admin.purchases-orders.pdf', $purchaseOrder) }}">
                    descargar
                </x-wire-button>

                <x-wire-button light gray :href="route('admin.purchases-orders.index')" label="Volver" />
            </div>
        </div>
    </x-wire-card>
    <x-wire-card class="border-2 border-gray-100">

        <form wire:submit="save" class="space-y-4">

            <div class="grid lg:grid-cols-4 gap-4">
                <x-wire-native-select label="Tipo de Documento" wire:model="voucher_type">
                    <option value="1">Factura</option>
                    <option value="2">Boleta</option>
                </x-wire-native-select>

                <x-wire-input label="Serie" wire:model="serie" placeholder="Serie del comprobante" disabled />
                <x-wire-input label="Correlativo" wire:model="correlative" placeholder="Correlativo del comprobante" disabled />

                <x-wire-input label="Fecha" wire:model="date" type="date" />
            </div>

            <x-wire-select label="Proveedor" wire:model="supplier_id" placeholder="Seleccione un proveedor" :async-data="[
                    'api' => route('api.suppliers.index'),
                    'method' => 'POST',
                ]" option-label="name" option-value="id" class="flex-1" />

            <div class="lg:flex lg:space-x-4">
                <x-wire-select label="Producto" wire:model="variant_id" placeholder="Seleccione un producto" :async-data="[
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
                            <th class="px-6 py-2">Impuesto</th>
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
                                <td class="px-4 py-1">
                                    <x-wire-native-select x-model="variant.tax_rate">
                                        <option value="0">Inafecto</option>
                                        <option value="10">IGV 10%</option>
                                        <option value="18">IGV 18%</option>
                                    </x-wire-native-select>
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
                Subtotal: $<span x-text="subtotal.toFixed(2)"></span><br>
                Impuestos: $<span x-text="taxTotal.toFixed(2)"></span><br>
                Total: $<span x-text="total.toFixed(2)"></span>
            </div>

            <x-wire-button type="submit" icon="check" spinner>
                Guardar
            </x-wire-button>
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
