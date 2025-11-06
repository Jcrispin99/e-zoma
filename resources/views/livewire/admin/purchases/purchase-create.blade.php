<div x-data="{
    variants: @entangle('variants'),
    total: @entangle('total'),
    subtotal: 0,
    taxTotal: 0,
    taxes: @js($taxes),
    scanBuffer: '',
    lastKeyTime: 0,

    removeVariant(index) { this.variants.splice(index, 1); },

    calculateTotals() {
        let subtotal = 0;
        let taxTotal = 0;

        this.variants.forEach(variant => {
            const rate = Number(variant.tax_rate) || 0;
            const inclusive = Boolean(variant.tax_inclusive);
            const lineTotal = (Number(variant.quantity) || 0) * (Number(variant.price) || 0);
            const base = (inclusive && rate > 0) ? (lineTotal / (1 + (rate / 100))) : lineTotal;
            const tax = base * (rate / 100);
            subtotal += base;
            taxTotal += tax;
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

    <x-wire-card>

        <form wire:submit="save" class="space-y-4" x-on:keydown.enter.prevent>

            <x-wire-card class="mb-3">
                <div class="flex items-center gap-2">
                    <x-wire-button color="primary" icon="check" spinner type="submit">
                        Guardar
                    </x-wire-button>
                    <x-wire-button color="secondary" icon="x-mark" :href="route('admin.purchases.index')">
                        Cancelar
                    </x-wire-button>
                </div>
            </x-wire-card>

            <div class="grid lg:grid-cols-4 gap-4">
                <x-wire-native-select label="Serie del Documento" wire:model.live="journal_id">
                    @if($journals->isEmpty())
                    <option value="">No hay series para compras</option>
                    @endif
                    @foreach ($journals as $journal)
                    <option value="{{ $journal->id }}">{{ $journal->name }} ({{ $journal->code }})</option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-input label="Correlativo" wire:model="correlative" readonly disabled />

                <x-wire-input label="Fecha" wire:model="date" type="date" />

                <x-wire-select label="Orden de Compra" wire:model.live="purchase_order_id"
                    placeholder="Seleccione una orden de compra" :async-data="[
                        'api' => route('api.purchase-orders.index'),
                        'method' => 'POST',
                    ]" option-label="name" option-value="id" option-description="description" class="flex-1" />

                <div class="col-span-2">
                    <x-wire-select label="Proveedor" wire:model="supplier_id" placeholder="Seleccione un proveedor"
                        :async-data="[
                            'api' => route('api.suppliers.index'),
                            'method' => 'POST',
                        ]" option-label="name" option-value="id" class="flex-1" option-description="description" />
                </div>
                <div class="col-span-2">
                    <x-wire-select label="Almacenes" wire:model="warehouse_id" placeholder="Seleccione un almacén"
                        :async-data="[
                            'api' => route('api.warehouse.index'),
                            'method' => 'POST',
                            'params' => [
                                'company_ids' => session()->get('selected_company_ids', [])
                            ]
                        ]" option-label="name" option-value="id" class="flex-1" option-description="description" />
                </div>
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
                                    <x-wire-input type="number" x-model.number="variant.quantity" />
                                </td>
                                <td class="px-4 py-1">
                                    <x-wire-input type="number" x-model.number="variant.price" step="0.01"
                                        class="w-20" />
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
                                    x-text="(((variant.tax_inclusive && Number(variant.tax_rate) > 0) ? ((Number(variant.quantity) * Number(variant.price)) / (1 + (Number(variant.tax_rate) / 100))) : (Number(variant.quantity) * Number(variant.price)))).toFixed(2)"></td>
                                <td class="px-4 py-1">
                                    <x-wire-mini-button rounded x-on:click="removeVariant(index)" icon="trash" red />
                                </td>
                            </tr>
                        </template>
                        <template x-if="variants.length === 0">
                            <tr>
                                <td colspan="6" class="text-center text-gray-500 py-4">No hay productos agregados
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
        </form>
    </x-wire-card>
</div>