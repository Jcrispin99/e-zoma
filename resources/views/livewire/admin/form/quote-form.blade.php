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
        this.recalcTotals();
    },

    recalcTotals() {
        let subtotal = 0;
        let taxTotal = 0;
        (this.variants || []).forEach(v => {
            const rate = Number(v.tax_rate) || 0;
            const inclusive = Boolean(v.tax_inclusive);
            const lineTotal = (Number(v.quantity) || 0) * (Number(v.price) || 0);
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
        this.$watch('variants', () => this.recalcTotals(), { deep: true });
        this.recalcTotals();
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
                <x-wire-badge :label="str($status)->upper()"
                    :color="$status === 'draft' ? 'slate' : ($status === 'posted' ? 'emerald' : 'rose')" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-wire-dropdown icon="bars-3" align="right">
                    @if($status === 'draft')
                    <x-wire-dropdown.item label="Publicar" wire:click="post" />
                    <x-wire-dropdown.item label="Cancelar" wire:click="cancel" />
                    @elseif($status === 'posted')
                    <x-wire-dropdown.item label="Anular" wire:click="cancel" />
                    @if(!$quote->sales()->exists())
                    <x-wire-dropdown.item label="Crear venta"
                        :href="route('admin.sales.create', ['quote_id' => $quote->id])" />
                    @else
                    <x-wire-dropdown.item label="Ver venta"
                        :href="route('admin.sales.edit', $quote->sales()->latest()->value('id'))" />
                    @endif
                    @elseif($status === 'cancelled')
                    <x-wire-dropdown.item label="Reabrir" wire:click="reopen" />
                    @endif

                    <x-wire-dropdown.header separator label="Acciones" />
                    <x-wire-dropdown.item label="Enviar cotización por correo" wire:click="openModal({{ $quote }})" />
                    <x-wire-dropdown.item label="Ver PDF" :href="route('admin.quotes.pdf.view', $quote)" />
                    <x-wire-dropdown.item label="Ver público"
                        :href="URL::signedRoute('public.quotes.pdf.view', ['quote' => $quote])" />
                    <x-wire-dropdown.item label="Descargar PDF" :href="route('admin.quotes.pdf', $quote)" />
                    <x-wire-dropdown.item label="Volver" :href="route('admin.quotes.index')" />
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
                <x-wire-button color="secondary" icon="x-mark" href="{{ route('admin.quotes.index') }}">
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

            <div class="col-span-2">
                <x-wire-select label="Cliente" wire:model="customer_id" placeholder="Seleccione un cliente" :async-data="[
                        'api' => route('api.customers.index'),
                        'method' => 'POST',
                    ]" option-label="name" option-value="id" class="flex-1" />
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
                                    <x-wire-input type="number" step="0.01" class="w-20"
                                        x-model.number="variant.price" />
                                </td>
                                <td class="px-4 py-1">
                                    <select x-model="variant.tax_id"
                                        x-on:change="(() => { const t = taxes.find(t => t.id == Number(variant.tax_id)); variant.tax_rate = t ? Number(t.rate_percent) : 0; variant.tax_inclusive = t ? Boolean(t.is_price_inclusive) : false; recalcTotals(); })()"
                                        class="form-select block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                        @foreach($taxes as $tax)
                                        <option value="{{ $tax['id'] }}">
                                            {{ $tax['invoice_label'] ?? $tax['name'] }}
                                            @if(!empty($tax['is_price_inclusive'])) (TTC) @endif
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-1">
                                    <span
                                        x-text="(((variant.tax_inclusive && Number(variant.tax_rate) > 0) ? ((Number(variant.quantity) * Number(variant.price)) / (1 + (Number(variant.tax_rate) / 100))) : (Number(variant.quantity) * Number(variant.price)))).toFixed(2)"></span>
                                </td>
                                <td class="px-6 py-2 w-16 text-right">
                                    <button type="button" class="text-red-600"
                                        x-on:click="removeVariant(index)">Eliminar</button>
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
            <x-wire-button type="submit" class="w-full">
                Enviar
            </x-wire-button>
        </form>
    </x-wire-modal-card>
    @endif

</div>