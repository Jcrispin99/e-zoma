<div x-data="{
    variants: @entangle('variants'),
    taxes: @entangle('taxes'),
    total: @entangle('total'),
    subtotal: 0,
    taxTotal: 0,
    scanBuffer: '',
    lastKeyTime: 0,
    removeVariant(index) { this.variants.splice(index, 1); },
    init() {
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
            $data.subtotal = subtotal;
            $data.taxTotal = taxTotal;
            $data.total = subtotal + taxTotal;
            $data.total = subtotal + taxTotal;
        };
        calc();
        this.$watch('variants', () => calc(), { deep: true });
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
}" x-on:keydown.window="handleScanner($event)">
    <x-wire-card class="mb-3">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-2">
                <x-wire-button label="Guardar" right-icon="check" positive wire:click="save" />
                <x-wire-badge :label="'COTIZACIÓN'" color="slate" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-wire-dropdown icon="bars-3" align="right">
                <x-wire-dropdown.header separator label="Acciones" />
                <x-wire-dropdown.item label="Enviar cotización por correo" wire:click="openModal({{ $quote }})" />
                <x-wire-dropdown.item label="Descargar PDF" :href="route('admin.quotes.pdf', $quote)" />
                <x-wire-dropdown.item label="Ver PDF (vista)" :href="route('admin.quotes.pdf.view', $quote)" />
                @if(!$quote->sales()->exists())
                <x-wire-dropdown.item label="Crear venta"
                    :href="route('admin.sales.create', ['quote_id' => $quote->id])" />
                @else
                <x-wire-dropdown.item label="Ver venta"
                    :href="route('admin.sales.edit', $quote->sales()->latest()->value('id'))" />
                @endif
                <x-wire-dropdown.item label="Volver" :href="route('admin.quotes.index')" />
                </x-wire-dropdown>
            </div>
        </div>
    </x-wire-card>

    <x-wire-card>
        <form wire:submit="save" class="space-y-4" x-on:keydown.enter.prevent>
            <div class="grid lg:grid-cols-4 gap-4">
                <x-wire-native-select label="Serie del Documento" wire:model="journal_id" disabled>
                    @foreach ($journals as $journal)
                    <option value="{{ $journal->id }}">{{ $journal->name }} ({{ $journal->code }})</option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-input label="Correlativo" wire:model="correlative" disabled />
                <x-wire-input label="Fecha" wire:model.live="date" type="date" />
            </div>

            <div class="col-span-2">
                <x-wire-select label="Cliente" wire:model="customer_id" placeholder="Seleccione un cliente"
                    :async-data="['api' => route('api.customers.index'), 'method' => 'POST']" option-label="name"
                    option-value="id" class="flex-1" option-description="description" />
            </div>

            <div class="lg:flex lg:space-x-4">
                <x-wire-select label="Producto" wire:model="variant_id" placeholder="Seleccione un producto"
                    :async-data="['api' => route('api.product.index'), 'method' => 'POST']" option-label="name"
                    option-value="id" class="flex-1" />
                <div class="flex-shrink-0">
                    <x-wire-button wire:click="addProduct" class="mt-4 w-full lg:mt-6.5" spinner>Agregar producto
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
                                    <x-wire-native-select x-model="variant.tax_id">
                                        <template x-for="tax in taxes" :key="tax.id">
                                            <option :value="tax.id"
                                                x-text="`${tax.invoice_label ?? tax.name}${tax.is_price_inclusive ? ' (TTC)' : ''}`">
                                            </option>
                                        </template>
                                    </x-wire-native-select>
                                </td>
                                <td class="px-4 py-1"
                                    x-text="(() => { const t = (taxes||[]).find(tt => String(tt.id)===String(variant.tax_id)); const r = t ? Number(t.rate_percent)||0 : 0; const inc = t ? Boolean(t.is_price_inclusive) : false; const line = (Number(variant.quantity)||0) * (Number(variant.price)||0); const base = (inc && r>0) ? (line/(1+(r/100))) : line; return Number(base).toFixed(2); })()">
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
                Subtotal: $<span x-text="Number(subtotal ?? 0).toFixed(2)"></span><br>
                Impuestos: $<span x-text="Number(taxTotal ?? 0).toFixed(2)"></span><br>
                Total: $<span x-text="Number(total ?? 0).toFixed(2)"></span>
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