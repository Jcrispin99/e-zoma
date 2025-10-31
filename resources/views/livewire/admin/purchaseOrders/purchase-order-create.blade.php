<div x-data="{
    variants: @entangle('variants'),
    total: @entangle('total'),
    scanBuffer: '',
    lastKeyTime: 0,

    removeVariant(index) {
        this.variants.splice(index, 1);
    },

    init() {
        this.$watch('variants', (newVariants) => {
            let total = 0;
            newVariants.forEach(variant => {
                const subtotal = variant.quantity * variant.price;
                const tax = subtotal * (variant.tax_rate / 100);
                total += subtotal + tax;
            });
            this.total = total;
        }, { deep: true });
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

            <div class="grid lg:grid-cols-4 gap-4">
                <x-wire-native-select label="Serie del Documento" wire:model.live="journal_id">
                    @if($journals->isEmpty())
                    <option value="">No hay series para O/C</option>
                    @endif
                    @foreach ($journals as $journal)
                    <option value="{{ $journal->id }}">{{ $journal->name }} ({{ $journal->code }})</option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-input label="Correlativo" wire:model="correlative" readonly disabled />

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
                                    <select x-model="variant.tax_rate" class="form-select block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                        <option value="0">Inafecto</option>
                                        <option value="10">IGV 10%</option>
                                        <option value="18">IGV 18%</option>
                                    </select>
                                </td>
                                <td class="px-4 py-1" x-text="(variant.quantity * variant.price).toFixed(2)"></td>
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
                Total: $<span x-text="total.toFixed(2)"></span>
            </div>

            <x-wire-button type="submit" icon="check" spinner>
                Guardar
            </x-wire-button>
        </form>
    </x-wire-card>
</div>
