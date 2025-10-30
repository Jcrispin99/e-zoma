<div x-data="{
    variants: @entangle('variants'),
    total: @entangle('total'),
    subtotal: 0,
    taxTotal: 0,

    removeVariant(index) {
        this.variants.splice(index, 1);
    },

    recalcTotals() {
        let subtotal = 0;
        let taxTotal = 0;
        (this.variants || []).forEach(v => {
            const sub = (Number(v.quantity) || 0) * (Number(v.price) || 0);
            const tax = sub * ((Number(v.tax_rate) || 0) / 100);
            subtotal += sub;
            taxTotal += tax;
        });
        this.subtotal = subtotal;
        this.taxTotal = taxTotal;
        this.total = subtotal + taxTotal;
    },

    init() {
        this.$watch('variants', () => this.recalcTotals(), { deep: true });
        // Calcular al iniciar para mostrar subtotales correctamente
        this.recalcTotals();
    }
}">
    <x-wire-card class="mb-3">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-2">
                <x-wire-badge :label="str($purchase->status)->upper()" :color="$purchase->status === 'draft' ? 'slate' : ($purchase->status === 'posted' ? 'emerald' : 'rose')" />
                @if($purchase->payment_status)
                <x-wire-badge :label="str($purchase->payment_status)->upper()" :color="$purchase->payment_status === 'paid' ? 'emerald' : ($purchase->payment_status === 'partial' ? 'amber' : 'slate')" />
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if($purchase->status === 'draft')
                <x-wire-button light emerald label="Contabilizar" wire:click="post" wire:loading.attr="disabled" />
                <x-wire-button light red label="Cancelar" wire:click="cancel" wire:loading.attr="disabled" />
                @elseif($purchase->status === 'posted')
                <x-wire-button light emerald label="Registrar pago" wire:click="markPaid" wire:loading.attr="disabled" />
                <x-wire-button light red label="Anular" wire:click="cancel" wire:loading.attr="disabled" />
                @elseif($purchase->status === 'cancelled')
                <x-wire-button light gray label="Reabrir" wire:click="reopen" wire:loading.attr="disabled" />
                @endif

                @if($purchase->purchase_order_id)
                <x-wire-button light gray label="Ver OC" :href="route('admin.purchases-orders.edit', $purchase->purchase_order_id)" />
                @endif

                <x-wire-button light gray label="Enviar factura por correo" wire:click="openModal({{ $purchase }})">
                    <i class="fa-solid fa-envelope"></i>
                </x-wire-button>

                <x-wire-button light gray href="{{ route('admin.purchases.pdf', $purchase) }}">

                    descargar
                </x-wire-button>

                <x-wire-button light gray href="{{ route('admin.qr.labels', ['type' => 'purchase', 'id' => $purchase->id]) }}" label="Generar QR" />

                <x-wire-button light gray :href="route('admin.purchases.index')" label="Volver" />
            </div>
        </div>
    </x-wire-card>

    <x-wire-card>

        <form wire:submit="save" class="space-y-4">

            <div class="grid lg:grid-cols-4 gap-4">
                <x-wire-native-select label="Serie del Documento" wire:model="journal_id" disabled>
                    @foreach ($journals as $journal)
                        <option value="{{ $journal->id }}">{{ $journal->name }} ({{ $journal->code }})</option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-input label="Correlativo" wire:model="correlative" readonly disabled />

                <x-wire-input label="Fecha" wire:model="date" type="date" />

                <div class="col-span-2">
                    <x-wire-select label="Proveedor" wire:model="supplier_id" placeholder="Seleccione un proveedor" :async-data="[
                            'api' => route('api.suppliers.index'),
                            'method' => 'POST',
                        ]" option-label="name" option-value="id" class="flex-1" option-description="description" />
                </div>
                <div class="col-span-2">
                    <x-wire-select label="Almacenes" wire:model="warehouse_id" placeholder="Seleccione un almacén" :async-data="[
                            'api' => route('api.warehouse.index'),
                            'method' => 'POST',
                            'params' => [
                                'company_ids' => session()->get('selected_company_ids', [])
                            ]
                        ]" option-label="name" option-value="id" class="flex-1" option-description="description" />
                </div>
            </div>

            <div class="flex items-end space-x-4">
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
                                    <x-wire-input type="number" x-model.number="variant.quantity" />
                                </td>
                                <td class="px-4 py-1">
                                    <x-wire-input type="number" step="0.01" class="w-20" x-model.number="variant.price" />
                                </td>
                                <td class="px-4 py-1">
                                    <x-wire-native-select x-model="variant.tax_rate">
                                        <option value="0">Inafecto</option>
                                        <option value="10">IGV 10%</option>
                                        <option value="18">IGV 18%</option>
                                    </x-wire-native-select>
                                </td>
                                <td class="px-4 py-1">
                                    <span x-text="((Number(variant.quantity)||0) * (Number(variant.price)||0)).toFixed(2)"></span>
                                </td>
                                <td class="px-6 py-2 w-16 text-right">
                                    <button type="button" class="text-red-600" x-on:click="removeVariant(index)">Eliminar</button>
                                </td>
                            </tr>
                        </template>
                        <template x-if="variants.length === 0">
                            <tr>
                                <td colspan="6" class="text-center text-gray-500 py-4">No hay productos agregados</td>
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
                Subtotal: $<span x-text="Number(subtotal || 0).toFixed(2)"></span><br>
                Impuestos: $<span x-text="Number(taxTotal || 0).toFixed(2)"></span><br>
                Total: $<span x-text="Number(total || 0).toFixed(2)"></span>
            </div>

            <x-wire-button type="submit" icon="check" spinner>
                Guardar cambios
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
