<div x-data="{
    variants: @entangle('variants'),
    total: @entangle('total'),
    subtotal: 0,
    taxTotal: 0,
    taxes: @js($taxes),
    scanBuffer: '',
    lastKeyTime: 0,

    removeVariant(index) {
        this.variants.splice(index, 1);
        this.calculateTotals();
    },

    calculateTotals() {
        let subtotal = 0;
        let taxTotal = 0;

        this.variants.forEach(variant => {
            const q = Number(variant.quantity || 0);
            const p = Number(variant.price || 0);
            const r = Number(variant.tax_rate || 0) / 100;
            const inclusive = Boolean(variant.tax_inclusive || false);

            const gross = q * p;
            if (inclusive && r > 0) {
                const base = gross / (1 + r);
                const tax = gross - base;
                subtotal += base;
                taxTotal += tax;
            } else {
                const base = gross;
                const tax = base * r;
                subtotal += base;
                taxTotal += tax;
            }
        });

        this.subtotal = subtotal;
        this.taxTotal = taxTotal;
        this.total = subtotal + taxTotal;
    },

    init() {
        this.calculateTotals();
        this.$watch('variants', () => this.calculateTotals(), { deep: true });
    },

    handleScanner(e) {
        const key = e.key;
        const now = Date.now();

        if (key === 'Enter') {
            if (this.scanBuffer.length > 0) {
                this.$wire.scanBarcode(this.scanBuffer);
                this.scanBuffer = '';
                e.preventDefault();
            }
            return;
        }

        if (key === 'Backspace') {
            this.scanBuffer = this.scanBuffer.slice(0, -1);
            return;
        }

        if (/^[A-Za-z0-9\-_.]$/.test(key)) {
            if (this.lastKeyTime && now - this.lastKeyTime > 300) {
                this.scanBuffer = '';
            }
            this.scanBuffer += key;
            this.lastKeyTime = now;
        }
    }
}" x-on:keydown.window="handleScanner($event)">

    @if($mode === 'edit')
    <x-wire-card class="mb-3">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-2">
                <x-wire-button label="Guardar" right-icon="check" positive wire:click="save" />
            </div>

            <div>
                <x-wire-badge :label="str($purchaseOrder->status)->upper()"
                    :color="$purchaseOrder->status === 'draft' ? 'slate' : ($purchaseOrder->status === 'confirmed' ? 'blue' : ($purchaseOrder->status === 'done' ? 'emerald' : 'rose'))" />
                @if($purchaseOrder->billing_status)
                <x-wire-badge :label="str($purchaseOrder->billing_status)->upper()"
                    :color="$purchaseOrder->billing_status === 'complete' ? 'emerald' : ($purchaseOrder->billing_status === 'partial' ? 'amber' : 'slate')" />
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-wire-dropdown icon="bars-3" align="right">
                    @if ($purchaseOrder->status == 'draft')
                    <x-wire-dropdown.item label="Confirmar Orden" wire:click="confirmOrder" />
                    <x-wire-dropdown.item label="Cancelar" wire:click="cancelOrder" />
                    @elseif ($purchaseOrder->status == 'confirmed')
                    @if ($hasPurchase)
                    <x-wire-dropdown.item label="Ver factura" wire:click="viewPurchase" />
                    @else
                    <x-wire-dropdown.item label="Crear Compra" wire:click="createPurchase" />
                    @endif
                    <x-wire-dropdown.item label="Cancelar" wire:click="cancelOrder" />
                    @endif

                    <x-wire-dropdown.header separator label="Acciones" />
                    <x-wire-dropdown.item label="Enviar OC por correo"
                        wire:click="openModal({{ $purchaseOrder->id }})" />
                    <x-wire-dropdown.item label="Imprimir QR (productos)"
                        :href="route('admin.qr.labels', ['type' => 'purchase-order', 'id' => $purchaseOrder->id])" />
                    <x-wire-dropdown.item label="Ver PDF"
                        :href="route('admin.purchases-orders.pdf.view', $purchaseOrder)" />
                    <x-wire-dropdown.item label="Volver" :href="route('admin.purchases-orders.index')" />
                </x-wire-dropdown>
            </div>
        </div>
    </x-wire-card>
    @else
    <x-wire-card class="mb-3">
        <form wire:submit="save" class="space-y-4" x-on:keydown.enter.prevent>
            <div class="flex items-center gap-2">
                <x-wire-button color="primary" icon="check" spinner wire:click="save">
                    Guardar
                </x-wire-button>
                <x-wire-button color="secondary" icon="x-mark" href="{{ route('admin.purchases-orders.index') }}">
                    Cancelar
                </x-wire-button>
            </div>
        </form>
    </x-wire-card>
    @endif

    <x-wire-card class="border-2 border-gray-100">
        <form wire:submit="save" class="space-y-4" x-on:keydown.enter.prevent>
            <div class="grid lg:grid-cols-4 gap-4">
                <x-wire-select label="Serie del Documento" :disabled="$mode === 'edit'" wire:model="journal_id"
                    :options="$journalOptions" option-label="label" option-value="id"
                    placeholder="Seleccione una serie" />

                <x-wire-input label="Correlativo" wire:model="correlative" readonly disabled />

                <x-wire-input label="Fecha" wire:model="date" type="date" />
            </div>

            <x-wire-select label="Proveedor" wire:model="supplier_id" placeholder="Seleccione un proveedor" :async-data="[
                'api' => route('api.suppliers.index'),
                'method' => 'POST',
            ]" option-label="name" option-value="id" class="flex-1" />

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
                            <th class="px-6 py-2">Impuesto</th>
                            <th class="px-6 py-2">Sin Imp.</th>
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
                                    <select x-model="variant.tax_id"
                                        x-on:change="(() => { const t = taxes.find(t => t.id == Number(variant.tax_id)); variant.tax_rate = t ? Number(t.rate_percent) : 0; variant.tax_inclusive = t ? Boolean(t.is_price_inclusive) : false; calculateTotals(); })()"
                                        class="form-select block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                        @foreach($taxes as $tax)
                                        <option value="{{ $tax['id'] }}">
                                            {{ $tax['invoice_label'] ?? $tax['name'] }}
                                            @if(!empty($tax['is_price_inclusive'])) (TTC) @endif
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-1"
                                    x-text="( (variant.tax_inclusive && Number(variant.tax_rate) > 0) ? ((variant.quantity * variant.price) / (1 + (Number(variant.tax_rate) / 100))) : (variant.quantity * variant.price) ).toFixed(2)">
                                </td>
                                <td class="px-4 py-1">
                                    <x-wire-mini-button rounded x-on:click="removeVariant(index)" icon="trash" red />
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
                Subtotal: $<span x-text="subtotal.toFixed(2)"></span><br>
                Impuestos: $<span x-text="taxTotal.toFixed(2)"></span><br>
                Total: $<span x-text="total.toFixed(2)"></span>
            </div>

        </form>
    </x-wire-card>

    @if($mode === 'edit')
    <x-wire-modal-card wire:model="form.open" width="lg">
        <x-slot name="title">
            <p class="text-xl text-center mb-2">Enviar email</p>
            <p class="text-lg text-center uppercase font-bold mb-2">{{ $form['document'] }}</p>
            <p class="text-lg text-center mb-2">{{ $form['client'] }}</p>
        </x-slot>

        <form wire:submit="sendEmail">
            <x-wire-input label="Correo" wire:model="form.email" class="mb-4" value="{{ $form['email'] }}" />
            <x-wire-button type="submit" class="w-full">Enviar</x-wire-button>
        </form>
    </x-wire-modal-card>
    @endif
</div>
