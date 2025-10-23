<div>
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-semibold">Recompensas</h2>
        <x-wire-button primary wire:click="openCreate">Nueva</x-wire-button>
    </div>

    <div class="overflow-x-auto rounded-md border">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left">Tipo</th>
                    <th class="px-3 py-2 text-left">Modo/Aplicación</th>
                    <th class="px-3 py-2 text-left">Descuento</th>
                    <th class="px-3 py-2 text-left">Producto regalo</th>
                    <th class="px-3 py-2 text-left">Puntos requeridos</th>
                    <th class="px-3 py-2 text-left">Activo</th>
                    <th class="px-3 py-2 text-left">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rewards as $reward)
                <tr class="border-t">
                    <td class="px-3 py-2">{{ $reward->reward_type }}</td>
                    <td class="px-3 py-2">
                        @if($reward->reward_type === 'discount')
                        {{ $reward->discount_mode }} / {{ $reward->discount_applicability ?? '—' }}
                        @else
                        —
                        @endif
                    </td>
                    <td class="px-3 py-2">
                        @if($reward->reward_type === 'discount')
                        {{ $reward->discount ?? '—' }} (max {{ $reward->discount_max_amount ?? '—' }})
                        @else
                        —
                        @endif
                    </td>
                    <td class="px-3 py-2">
                        @if($reward->reward_type === 'product')
                        {{ optional($reward->rewardProduct)->name ?? ('ID ' . $reward->reward_product_id) }} × {{ $reward->reward_product_qty ?? 1 }}
                        @else
                        —
                        @endif
                    </td>
                    <td class="px-3 py-2">{{ $reward->required_points ?? '—' }}</td>
                    <td class="px-3 py-2">{{ $reward->active ? 'Sí' : 'No' }}</td>
                    <td class="px-3 py-2 space-x-2">
                        <x-wire-button xs secondary wire:click="openEdit({{ $reward->id }})">Editar</x-wire-button>
                        <x-wire-button xs info wire:click="toggleActive({{ $reward->id }})">{{ $reward->active ? 'Desactivar' : 'Activar' }}</x-wire-button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-3 py-6 text-center text-gray-500">Sin recompensas aún</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-dialog-modal wire:model.live="open">
        <x-slot name="title">
            {{ $editingId ? 'Editar recompensa' : 'Nueva recompensa' }}
        </x-slot>
        <x-slot name="content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-wire-input label="Nombre" wire:model="name" />
                </div>
                <div>
                    <x-wire-native-select label="Tipo" wire:model="reward_type">
                        <option value="discount">Descuento</option>
                        <option value="product">Producto regalo</option>
                    </x-wire-native-select>
                </div>
            </div>

            @if($reward_type === 'discount')
            <div>
                <x-wire-native-select label="Modo" wire:model="discount_mode">
                    <option value="percent">Porcentaje</option>
                    <option value="per_order">Monto por pedido</option>
                    <option value="per_point">Por punto</option>
                </x-wire-native-select>
            </div>
            <div>
                <x-wire-native-select label="Aplicabilidad" wire:model="discount_applicability">
                    <option value="order">Pedido</option>
                    <option value="cheapest">Producto más barato</option>
                    <option value="specific">Productos específicos</option>
                </x-wire-native-select>
            </div>
            @if($discount_applicability === 'specific')
            <x-wire-select label="Productos con descuento" wire:model.live="selected_variant_ids" placeholder="Seleccione productos" :async-data="['api' => route('api.product.index'), 'method' => 'POST']" option-label="name" option-value="id" multiselect />
            @endif
            @if($discount_mode !== 'per_point')
            <div class="grid grid-cols-2 gap-2 items-end">
                <x-wire-input label="Descuento" wire:model="discount" type="number" step="0.01" min="0" />
                <x-wire-native-select label="Unidad" wire:model="discount_mode">
                    <option value="percent">Porcentaje</option>
                    <option value="per_order">Soles</option>
                </x-wire-native-select>
            </div>
            @else
            <x-wire-input label="Descuento por punto (S/)" wire:model="discount" type="number" step="0.01" min="0" />
            @endif
            <x-wire-input label="Tope de descuento" wire:model="discount_max_amount" type="number" step="0.01" min="0" />
            @endif

            @if($reward_type === 'product')
            <x-wire-input label="ID de producto (variant)" wire:model="reward_product_id" type="number" min="1" />
            <x-wire-input label="Cantidad" wire:model="reward_product_qty" type="number" min="1" />
            @endif

            <x-wire-input label="Puntos requeridos" wire:model="required_points" type="number" step="0.01" min="0" />
            <x-wire-checkbox label="Limpiar billetera" wire:model="clear_wallet" />
            <div>
                <label class="block text-sm font-medium text-gray-700">Descripción</label>
                <textarea wire:model="description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" rows="3"></textarea>
            </div>
            <x-wire-checkbox label="Activo" wire:model="active" />
        </x-slot>
        <x-slot name="footer">
            <x-wire-button blue wire:click="save">Guardar</x-wire-button>
            <x-wire-button wire:click="$set('open', false)">Cancelar</x-wire-button>
        </x-slot>
    </x-dialog-modal>
</div>
