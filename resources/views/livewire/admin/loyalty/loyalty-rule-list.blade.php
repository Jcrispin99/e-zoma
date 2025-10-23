<div>
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-semibold">Reglas</h2>
        <x-wire-button primary wire:click="openCreate">Nueva</x-wire-button>
    </div>

    <div class="overflow-x-auto rounded-md border">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left">Modo</th>
                    <th class="px-3 py-2 text-left">Código</th>
                    <th class="px-3 py-2 text-left">Cantidad mínima</th>
                    <th class="px-3 py-2 text-left">Monto mínimo</th>
                    <th class="px-3 py-2 text-left">Categoría</th>
                    <th class="px-3 py-2 text-left">Puntos</th>
                    <th class="px-3 py-2 text-left">Activo</th>
                    <th class="px-3 py-2 text-left">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rules as $rule)
                <tr class="border-t">
                    <td class="px-3 py-2">{{ $rule->mode }}</td>
                    <td class="px-3 py-2">{{ $rule->code ?? '—' }}</td>
                    <td class="px-3 py-2">{{ $rule->minimum_qty ?? 0 }}</td>
                    <td class="px-3 py-2">
                        @if ($rule->minimum_amount)
                        {{ number_format($rule->minimum_amount, 2) }} ({{ $rule->minimum_amount_tax_mode === 'with_tax' ? 'c/IGV' : 's/IGV' }})
                        @else
                        —
                        @endif
                    </td>
                    <td class="px-3 py-2">{{ optional($rule->category)->name ?? '—' }}</td>
                    <td class="px-3 py-2">
                        @if ($rule->reward_point_mode === 'money' && $rule->amount_per_point)
                        S/ {{ number_format($rule->amount_per_point, 2) }} por punto
                        @elseif ($rule->reward_point_mode === 'order' && $rule->points_per_order)
                        {{ $rule->points_per_order }} puntos por pedido
                        @else
                        —
                        @endif
                        @if ($rule->reward_point_split)
                        <span class="ml-2 inline-block px-2 py-0.5 rounded bg-yellow-100 text-yellow-800">Dividir por ítem</span>
                        @endif
                    </td>
                    <td class="px-3 py-2">{{ $rule->active ? 'Sí' : 'No' }}</td>
                    <td class="px-3 py-2 space-x-2">
                        <x-wire-button xs secondary wire:click="openEdit({{ $rule->id }})">Editar</x-wire-button>
                        <x-wire-button xs info wire:click="toggleActive({{ $rule->id }})">{{ $rule->active ? 'Desactivar' : 'Activar' }}</x-wire-button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-3 py-6 text-center text-gray-500">Sin reglas aún</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-dialog-modal wire:model.live="open">
        <x-slot name="title">
            {{ $editingId ? 'Editar regla' : 'Nueva regla' }}
        </x-slot>
        <x-slot name="content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-wire-native-select label="Categoría" wire:model="product_category_id">
                        <option value="">—</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </x-wire-native-select>
                </div>
                <x-wire-select label="Productos" wire:model.live="selected_variant_ids" placeholder="Seleccione productos" :async-data="[
                        'api' => route('api.product.index'),
                        'method' => 'POST',
                    ]" option-label="name" option-value="id" multiselect />
                <x-wire-input label="Cantidad mínima" wire:model="minimum_qty" type="number" min="0" />
                <x-wire-input label="Monto mínimo" wire:model="minimum_amount" type="number" step="0.01" min="0" />
                <div>
                    <x-wire-native-select label="Modo impuestos" wire:model="minimum_amount_tax_mode">
                        <option value="">—</option>
                        <option value="with_tax">Con impuestos</option>
                        <option value="without_tax">Sin impuestos</option>
                    </x-wire-native-select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-wire-native-select label="Modo" wire:model="mode">
                            <option value="auto">Automático</option>
                            <option value="with_code">Con código</option>
                        </x-wire-native-select>
                    </div>
                    <x-wire-input label="Código" wire:model="code" />
                    <x-wire-input label="Código de barras" wire:model="promo_barcode" />
                    <x-wire-native-select label="Modo puntos" wire:model="reward_point_mode">
                        <option value="">—</option>
                        <option value="money">Por dinero</option>
                        <option value="order">Por pedido</option>
                    </x-wire-native-select>

                    @if($reward_point_mode === 'money')
                    <x-wire-input label="Monto por punto" wire:model="amount_per_point" type="number" step="0.01" min="0.01" />
                    @endif

                    @if($reward_point_mode === 'order')
                    <x-wire-input label="Puntos por pedido" wire:model="points_per_order" type="number" min="1" />
                    @endif

                    <x-wire-checkbox label="Dividir puntos por líneas" wire:model="reward_point_split" />
                </div>
                <div>
                    <x-wire-checkbox label="Activo" wire:model="active" />
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-wire-button blue wire:click="save">Guardar</x-wire-button>
            <x-wire-button wire:click="$set('open', false)">Cancelar</x-wire-button>
        </x-slot>
    </x-dialog-modal>
</div>
