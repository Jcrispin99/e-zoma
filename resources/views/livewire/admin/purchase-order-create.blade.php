<div x-data="{
    products: @entangle('products'),

    total: @entangle('total'),

    removeProduct(index) {
        this.products.splice(index, 1);
    },

    init() {
        this.$watch('products', (newProducts) => {

            let total = 0;
            newProducts.forEach(product => {
                total += product.quantity * product.price;
            });
            this.total = total;
        });
    }
}">

    <x-wire-card>

        <form wire:submit="save" class="space-y-4">

            <div class="grid grid-cols-4 gap-4">
                <x-wire-native-select label="Tipo de Documento" wire:model="voucher_type">
                    <option value="1">Factura</option>
                    <option value="2">Boleta</option>
                </x-wire-native-select>

                <x-wire-input label="Serie" wire:model="serie" placeholder="Serie del comprobante" />
                <x-wire-input label="Correlativo" wire:model="correlative" placeholder="Correlativo del comprobante" />

                <x-wire-input label="Fecha" wire:model="date" type="date" />
            </div>

            <x-wire-select label="Proveedor" wire:model="supplier_id" placeholder="Seleccione un proveedor"
                :async-data="[
                    'api' => route('supplier.index'),
                    'method' => 'POST',
                ]" option-label="name" option-value="id" class="flex-1" />

            <div class="flex space-x-4">
                <x-wire-select label="Producto" wire:model="product_id" placeholder="Seleccione un producto"
                    :async-data="[
                        'api' => route('product.index'),
                        'method' => 'POST',
                    ]" option-label="name" option-value="id" class="flex-1" />

                <div class="flex-shrink-0">

                    <x-wire-button wire:click="addProduct" class="mt-6.5">
                        Agregar producto
                    </x-wire-button>

                </div>
            </div>

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
                    <template x-for="(pooduct, index) in pooducts" :key="pooduct.id">
                        <tr class="border-b">
                            <td class="px-4 py-1" x-text="pooduct.name" />
                            <td class="px-4 py-1">
                                <x-wire-input type="number" x-model="pooduct.quantity" />
                            </td>
                            <td class="px-4 py-1">
                                <x-wire-input type="number" x-model="pooduct.price" step="0.01" class="w-20" />
                            </td>
                            <td class="px-4 py-1" x-text="(pooduct.quantity * pooduct.price).toFixed(2)"></td>
                            <td class="px-4 py-1">
                                <x-wire-mini-button rounded x-on:click="removeProduct(index)" icon="trash" red />
                            </td>
                        </tr>
                    </template>
                    <template x-if="pooducts.length === 0">
                        <tr>
                            <td colspan="5" class="text-center text-gray-500 py-4">No hay productos agregados</td>
                        </tr>
                    </template>

                </tbody>

            </table>
            <div class="fex items-center space-x-4">
                <x-label>Observaciones</x-label>
                <x-wire-input wire:model="observations" placeholder="Ingrese observaciones" class="flex-1" />
            </div>
            <div>
                Total: $<span x-text="total.toFixed(2)"></span>
            </div>

            <x-wire-button type="submit" icon="check">
                Guardar
            </x-wire-button>
        </form>
    </x-wire-card>
</div>
