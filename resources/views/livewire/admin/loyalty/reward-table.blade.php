<div>
    <x-wire-card title="Recompensas">
        <div class="flex items-center justify-between mb-4">
            <div class="text-sm text-gray-600">Total: {{ $rewards->total() }}</div>
            <x-wire-button color="primary" wire:click="openModal">Crear recompensa</x-wire-button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Puntos</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Activo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Prioridad</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Creado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($rewards as $reward)
                    <tr>
                        <td class="px-4 py-2">{{ $reward->id }}</td>
                        <td class="px-4 py-2">{{ $reward->name }}</td>
                        <td class="px-4 py-2">{{ $reward->reward_type }}</td>
                        <td class="px-4 py-2">{{ $reward->points_cost }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 text-xs rounded {{ $reward->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $reward->is_active ? 'Sí' : 'No' }}
                            </span>
                        </td>
                        <td class="px-4 py-2">{{ $reward->priority }}</td>
                        <td class="px-4 py-2">{{ optional($reward->created_at)->format('Y-m-d') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500">Aún no hay recompensas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $rewards->links() }}
        </div>

        <x-dialog-modal wire:model="modalOpen">
            <x-slot name="title">Nueva recompensa</x-slot>

            <x-slot name="content">
                <div class="space-y-6">
                    <!-- Sección: Recompensa -->
                    <div class="border rounded p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Recompensa</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-wire-native-select wire:model.live="reward_type" label="Tipo de recompensa">
                                <option value="">Seleccione tipo</option>
                                <option value="discount">Descuento</option>
                                <option value="free_product">Producto gratis</option>
                                <option value="free_shipping">Envío gratis</option>
                            </x-wire-native-select>
                            <x-wire-toggle wire:model="is_active" label="Activo" />
                            <x-wire-input wire:model.lazy="priority" type="number" label="Prioridad" />
                        </div>
                    </div>

                    <!-- Sección: Puntos -->
                    <div class="border rounded p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Puntos</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-wire-input wire:model.lazy="name" label="Nombre" placeholder="Nombre de la recompensa" />
                            <x-wire-input wire:model.lazy="points_cost" type="number" label="A cambio de (puntos)" placeholder="Cantidad de puntos" />
                            <x-wire-toggle wire:model="consume_all_points" label="Eliminar todos los puntos al canjear" />
                            <x-wire-input wire:model.lazy="description" label="Descripción" placeholder="Opcional" />
                        </div>
                    </div>

                    <!-- Sección: Descuento -->
                    @if($reward_type === 'discount')
                    <div class="border rounded p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Descuento</h3>

                        <!-- Alcance como checkboxes (mutuamente excluyentes) -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-4">
                            <label class="inline-flex items-center space-x-2">
                                <input type="checkbox" class="rounded border-gray-300" wire:click="$set('discount_scope', 'order')" @checked($discount_scope==='order' )>
                                <span>Orden</span>
                            </label>
                            <label class="inline-flex items-center space-x-2">
                                <input type="checkbox" class="rounded border-gray-300" wire:click="$set('discount_scope', 'cheapest_product')" @checked($discount_scope==='cheapest_product' )>
                                <span>Producto más barato</span>
                            </label>
                            <label class="inline-flex items-center space-x-2">
                                <input type="checkbox" class="rounded border-gray-300" wire:click="$set('discount_scope', 'specific_product')" @checked($discount_scope==='specific_product' )>
                                <span>Producto específico</span>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-wire-input wire:model.lazy="discount_value" type="number" step="0.01" label="Cantidad de descuento" placeholder="Ej: 10" />
                            <x-wire-native-select wire:model.live="discount_method" label="Tipo de descuento">
                                <option value="">Seleccione tipo</option>
                                <option value="percent">% Porcentaje</option>
                                <option value="soles_per_point">S/. por punto</option>
                                <option value="soles_fixed">S/. por orden</option>
                            </x-wire-native-select>

                            @if(in_array($discount_scope, ['order','cheapest_product']))
                            <x-wire-input wire:model.lazy="max_discount_amount" type="number" step="0.01" label="Descuento máximo (S/.)" placeholder="Ej: 50.00" />
                            @endif

                            @if($discount_scope === 'specific_product')
                            <x-wire-select label="Categoría" wire:model="discount_category_id" placeholder="Seleccione categoría" :async-data="['api' => route('categories.index'), 'method' => 'GET']" option-label="name" option-value="id" />

                            <x-wire-select label="Producto" wire:model="discount_variant_ids" multiselect placeholder="Seleccione productos" :async-data="['api' => route('api.product.index'), 'method' => 'POST']" option-label="name" option-value="id" />
                            @endif

                            <x-wire-select label="Producto con descuento" wire:model="reward_product_id" placeholder="Seleccione producto" :options="$products" option-label="name" option-value="id" />
                        </div>
                    </div>
                    @endif

                    <!-- Sección: Producto gratis -->
                    @if($reward_type === 'free_product')
                    <div class="border rounded p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Producto gratis</h3>
                        <x-wire-select label="Producto de recompensa" wire:model="reward_product_id" placeholder="Seleccione producto" :options="$products" option-label="name" option-value="id" />
                    </div>
                    @endif
                </div>
            </x-slot>

            <x-slot name="footer">
                <x-wire-button color="primary" wire:click="save">Guardar</x-wire-button>
                <x-wire-button color="secondary" wire:click="closeModal">Cancelar</x-wire-button>
            </x-slot>
        </x-dialog-modal>
    </x-wire-card>
</div>
