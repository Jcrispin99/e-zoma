<div>
    {{-- Botones de pestañas --}}
    <div class="flex gap-2 mb-4">
        <x-wire-button flat primary label="Producto" wire:click="setTab('product')" :class="$tab === 'product' ? 'bg-teal-300/60' : ''" />
        <x-wire-button flat primary label="Atributos" wire:click="setTab('attribute')" :class="$tab === 'attribute' ? 'bg-teal-300/60' : ''" />
    </div>

    {{-- Contenido dinámico --}}
    @if ($tab === 'product')
        <x-wire-card>
            <form wire:submit.prevent="saveProduct" class="space-y-4">
                <x-wire-input label="Nombre" wire:model="name" placeholder="Nombre del producto" />

                <x-wire-textarea label="Descripción" wire:model="description" placeholder="Descripción del producto" />

                <x-wire-input type="float" label="Precio" wire:model="price" placeholder="Precio del producto" />

                <x-wire-native-select label="Categoría" wire:model="category_id">
                    <option value="">Seleccione una categoría</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">
                            {{ $category->full_name }}
                        </option>
                    @endforeach
                </x-wire-native-select>

                <div class="flex justify-end">
                    <x-button type="submit">
                        Guardar
                    </x-button>
                </div>
            </form>
        </x-wire-card>
    @elseif ($tab === 'attribute')
        <x-wire-card>
            <div class="space-y-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Gestión de Atributos y Variantes</h3>
                    <p class="text-sm text-gray-600 mb-4">Defina los atributos del producto y sus valores. Los valores
                        deben separarse por comas (ej: Rojo, Azul, Verde).</p>
                </div>

                {{-- Tabla de Atributos --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nombre del Atributo
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Valores (separados por comas)
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($productAttributes as $index => $attribute)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <x-wire-select
                                            wire:model="productAttributes.{{ $index }}.name"
                                            placeholder="Buscar o crear atributo..."
                                            :async-data="[
                                                'api' => route('admin.search-attributes'),
                                                'method' => 'GET',
                                                'params' => ['query' => 'search']
                                            ]"
                                            option-label="label"
                                            option-value="value"
                                            :clearable="false"
                                            class="w-full"
                                        />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <x-wire-select
                                            wire:model="productAttributes.{{ $index }}.selectedValues"
                                            placeholder="Buscar o crear valores..."
                                            :async-data="[
                                                'api' => route('admin.search-attribute-values', ['index' => $index]),
                                                'method' => 'GET',
                                                'params' => ['query' => 'search']
                                            ]"
                                            option-label="label"
                                            option-value="value"
                                            multiselect
                                            :clearable="false"
                                            class="w-full"
                                        />
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        @if (count($productAttributes) > 1)
                                            <x-wire-button wire:click="removeAttributeRow({{ $index }})" negative
                                                xs icon="trash" label="Eliminar" />
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Botones de Acción --}}
                <div class="flex flex-wrap gap-3">
                    <x-wire-button wire:click="addAttributeRow" positive icon="plus" label="Agregar Línea" />

                    <x-wire-button wire:click="generateVariants" secondary icon="cog" label="Generar Variantes" />

                    <x-wire-button wire:click="saveAttributes" primary icon="check" label="Validar Atributos" />
                </div>

                {{-- Gestión de Precios y SKUs --}}
                @if ($showVariants && count($generatedVariants) > 0)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Gestión de Precios y SKUs</h3>
                        <div class="space-y-4">
                            @foreach ($generatedVariants as $index => $variant)
                                <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg border border-green-200">
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-2">
                                            <h4 class="font-medium text-gray-900">{{ $variant['name'] }}</h4>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Nueva
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Precio</label>
                                                <input type="number" 
                                                       wire:model="variantPrices.{{ $index }}" 
                                                       step="0.01" 
                                                       min="0"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                       placeholder="0.00">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                                                <input type="text" 
                                                       wire:model="variantSkus.{{ $index }}" 
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                       placeholder="Código SKU">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Preview de Variantes Generadas --}}
                @if ($showVariants && count($generatedVariants) > 0)
                    <div class="mt-6">
                        <h4 class="text-md font-medium text-gray-900 mb-3">Variantes Generadas
                            ({{ count($generatedVariants) }})</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach ($generatedVariants as $variant)
                                    <div class="bg-white p-3 rounded border">
                                        <div class="font-medium text-sm text-gray-900">{{ $variant['name'] }}</div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            Precio: ${{ number_format($variant['price'], 2) }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3 text-sm text-gray-600">
                                <strong>Nota:</strong> Todas las variantes son <span class="text-green-600 font-medium">nuevas</span> y se crearán automáticamente al guardar el producto.
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </x-wire-card>
    @endif

    {{-- Mostrar mensajes de éxito --}}
    @if (session()->has('message'))
        <div class="mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('message') }}
        </div>
    @endif
</div>
