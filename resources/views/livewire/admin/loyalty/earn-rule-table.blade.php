<div>
    <x-wire-card title="Reglas de acumulación">
        <div class="flex items-center justify-between mb-4">
            <div class="text-sm text-gray-600">Total: {{ $rules->total() }}</div>
            <x-wire-button color="primary" wire:click="openModal">Crear regla</x-wire-button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Base</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pts/Sol</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pts/Unidad</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pts/Orden</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Activo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Prioridad</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($rules as $rule)
                    <tr>
                        <td class="px-4 py-2">{{ $rule->id }}</td>
                        <td class="px-4 py-2">{{ $rule->name }}</td>
                        <td class="px-4 py-2">{{ $rule->basis }}</td>
                        <td class="px-4 py-2">{{ $rule->points_per_sol }}</td>
                        <td class="px-4 py-2">{{ $rule->points_per_unit }}</td>
                        <td class="px-4 py-2">{{ $rule->points_per_order }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 text-xs rounded {{ $rule->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $rule->is_active ? 'Sí' : 'No' }}
                            </span>
                        </td>
                        <td class="px-4 py-2">{{ $rule->priority }}</td>
                        <td class="px-4 py-2">
                            <x-wire-button color="secondary" class="text-xs" wire:click="edit({{ $rule->id }})">Editar</x-wire-button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-6 text-center text-gray-500">Aún no hay reglas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $rules->links() }}
        </div>

        <x-dialog-modal wire:model="modalOpen">
            <x-slot name="title">Abrir: Reglas condicionales</x-slot>

            <x-slot name="content">
                <div class="grid grid-cols-1 gap-6">
                    <!-- Sección: Condiciones -->
                    <div class="space-y-4">
                        <div class="text-sm font-semibold text-gray-700">CONDICIONES</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-wire-input wire:model.lazy="min_qty" type="number" min="0" step="1" label="Cantidad mínima" />
                            <x-wire-input wire:model.lazy="min_amount" type="number" step="0.01" label="Compra mínima (S/)" />
                        </div>
                    </div>

                    <!-- Sección: Puntos -->
                    <div class="space-y-4">
                        <div class="text-sm font-semibold text-gray-700">PUNTO(S)</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                            <x-wire-input wire:model.lazy="points_value" type="number" min="0" step="0.01" label="Otorgar" placeholder="Cantidad de puntos" />
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Base</label>
                                <div class="space-y-2">
                                    <label class="inline-flex items-center space-x-2">
                                        <input type="radio" name="basis" value="per_order" class="rounded" wire:model.live="basis">
                                        <span>por orden</span>
                                    </label>
                                    <label class="inline-flex items-center space-x-2">
                                        <input type="radio" name="basis" value="per_amount" class="rounded" wire:model.live="basis">
                                        <span>por S/ gastados</span>
                                    </label>
                                    <label class="inline-flex items-center space-x-2">
                                        <input type="radio" name="basis" value="per_unit" class="rounded" wire:model.live="basis">
                                        <span>por unidad pagada</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección: Dominio -->
                    <div class="space-y-4">
                        <div class="text-sm font-semibold text-gray-700">DOMINIO</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                            <div class="space-y-2">
                                <label class="inline-flex items-center space-x-2">
                            </div>
                            <!-- DOMINIO: solo categorías y variantes (sin checkboxes) -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <x-wire-select label="Categoría" wire:model="category_id" placeholder="Seleccione categoría" :async-data="['api' => route('categories.index'), 'method' => 'GET']" option-label="name" option-value="id" />

                                <x-wire-select label="Producto" wire:model="variant_ids" placeholder="Seleccione variantes" :async-data="['api' => route('api.product.index'), 'method' => 'POST']" option-label="name" option-value="id" multiselect />
                            </div>
                        </div>
                    </div>

                    <!-- Otros -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-wire-toggle wire:model="is_active" label="Activo" />
                        <x-wire-input wire:model.lazy="priority" type="number" label="Prioridad" />
                        <x-wire-input wire:model.lazy="description" label="Descripción" placeholder="Opcional" />
                        <x-wire-input wire:model.lazy="name" label="Nombre" placeholder="Se generará automáticamente si se deja vacío" />
                    </div>
                </div>
            </x-slot>

            <x-slot name="footer">
                <x-wire-button color="primary" wire:click="submit">{{ $editingId ? 'Actualizar' : 'Guardar' }}</x-wire-button>
                <x-wire-button color="secondary" wire:click="closeModal">Cancelar</x-wire-button>
            </x-slot>
        </x-dialog-modal>
    </x-wire-card>
</div>
