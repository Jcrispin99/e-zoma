<x-wire-card>
    <div x-data="{
    variants: @entangle('variants'),
    taxes: @entangle('taxes'),
    total: @entangle('total'),
    subtotal: 0,
    taxTotal: 0,
    scanBuffer: '',
    lastKeyTime: 0,
    removeVariant(index) {
        this.variants.splice(index, 1);
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
        if (key === 'Backspace') { this.scanBuffer = this.scanBuffer.slice(0, -1); return; }
        if (/^[A-Za-z0-9\-_.]$/.test(key)) {
            if (this.lastKeyTime && now - this.lastKeyTime > 300) { this.scanBuffer = ''; }
            this.scanBuffer += key; this.lastKeyTime = now;
        }
    }
}" x-init="
    const calc = () => {
        let subtotal = 0;
        let taxTotal = 0;
        (this.variants || []).forEach(v => {
            const qty = Number(v.quantity) || 0;
            const price = Number(v.price) || 0;
            const line = qty * price;
            const tax = (this.taxes || []).find(t => String(t.id) === String(v.tax_id)) || null;
            const rate = tax ? Number(tax.rate_percent) || 0 : 0;
            const inclusive = tax ? Boolean(tax.is_price_inclusive) : false;
            const base = (inclusive && rate > 0) ? (line / (1 + (rate / 100))) : line;
            const taxAmt = base * (rate / 100);
            subtotal += base;
            taxTotal += taxAmt;
        });
        $data.total = subtotal + taxTotal;
        $data.subtotal = subtotal;
        $data.taxTotal = taxTotal;
    };
    calc();
    $watch('variants', () => calc(), { deep: true })
" x-on:keydown.window="handleScanner($event)">
        <form wire:submit.prevent="save" class="space-y-4" x-on:keydown.enter.prevent>

            <x-wire-card class="mb-3">
                <div class="flex items-center gap-2">
                    <x-wire-button color="primary" icon="check" spinner type="submit">
                        Guardar
                    </x-wire-button>
                    <x-wire-button color="secondary" icon="x-mark" :href="route('admin.sales.index')">
                        Cancelar
                    </x-wire-button>
                </div>
            </x-wire-card>
            <div class="grid lg:grid-cols-4 gap-4">
                <x-wire-native-select label="Serie" wire:model="journal_id">
                    <option value="">Seleccione serie</option>
                    @foreach($journals as $journal)
                    <option value="{{ $journal->id }}">{{ $journal->name }} ({{ $journal->code }})</option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-input label="Fecha" wire:model.live="date" type="date" />

                <div class="col-span-2">
                    <x-wire-select label="Cliente" wire:model="customer_id" placeholder="Seleccione un cliente"
                        :async-data="[
                                'api' => route('api.customers.index'),
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
                                <td class="px-4 py-1" x-text="variant.name"></td>
                                <td class="px-4 py-1">
                                    <x-wire-input type="number" x-model="variant.quantity" />
                                </td>
                                <td class="px-4 py-1">
                                    <x-wire-input type="number" x-model="variant.price" step="0.01" class="w-20" />
                                </td>
                                <td class="px-4 py-1">
                                    <x-wire-native-select x-model="variant.tax_id">
                                        <template x-for="tax in taxes" :key="tax.id">
                                            <option :value="tax.id"
                                                x-text="`${tax.invoice_label ?? tax.name}${tax.is_price_inclusive ? ' (TTC)' : ''}`">
                                            </option>
                                        </template>
                                    </x-wire-native-select>
                                </td>
                                <td class="px-4 py-1"
                                    x-text="(() => { const t = (taxes||[]).find(tt => String(tt.id)===String(variant.tax_id)); const r = t ? Number(t.rate_percent)||0 : 0; const inc = t ? Boolean(t.is_price_inclusive) : false; const line = (Number(variant.quantity)||0) * (Number(variant.price)||0); const base = (inc && r>0) ? (line/(1+(r/100))) : line; return base.toFixed(2); })()">
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

            <div class="flex items-center space-x-4">
                <x-label>Observaciones</x-label>
                <x-wire-input wire:model="observation" placeholder="Ingrese observaciones" class="flex-1" />
            </div>

            <div>
                Subtotal: $<span x-text="Number(subtotal).toFixed(2)"></span><br>
                Impuestos: $<span x-text="Number(taxTotal).toFixed(2)"></span><br>
                Total: $<span x-text="Number(total).toFixed(2)"></span>
            </div>

            
        </form>
    </div>
</x-wire-card>
