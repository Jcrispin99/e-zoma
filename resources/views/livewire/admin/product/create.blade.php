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
                                    Atributo
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Valores
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
                                        <x-wire-select wire:model="productAttributes.{{ $index }}.name"
                                            placeholder="Buscar o crear atributo..." :async-data="[
                                                'api' => route('admin.search-attributes'),
                                                'method' => 'GET',
                                                'params' => ['query' => 'search'],
                                            ]"
                                            option-label="label" option-value="value" :clearable="false"
                                            class="w-full" />
                                    </td>
                                    <td class="px-6 py-4">
                                        @if(!empty($attribute['name']))
                                            {{-- Valores clickeables como chips --}}
                                            <div class="space-y-3">
                                                {{-- Valores disponibles --}}
                                                @php
                                                    $availableValues = [];
                                                    $attributeModel = \App\Models\Attribute::where('name', $attribute['name'])->first();
                                                    if ($attributeModel) {
                                                        $availableValues = \App\Models\AttributeValue::where('attribute_id', $attributeModel->id)->get();
                                                    }
                                                @endphp
                                                
                                                @if($availableValues->count() > 0)
                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach($availableValues as $value)
                                                            @php
                                                                $isSelected = in_array($value->value, $attribute['selectedValues'] ?? []);
                                                            @endphp
                                                            <button type="button"
                                                                wire:click="toggleValue({{ $index }}, '{{ $value->value }}')"
                                                                class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $isSelected ? 'bg-blue-100 text-blue-800 border-2 border-blue-300 shadow-sm' : 'bg-gray-100 text-gray-700 border-2 border-gray-200 hover:bg-gray-200' }}">
                                                                @if($isSelected)
                                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                                    </svg>
                                                                @endif
                                                                {{ $value->value }}
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                
                                                {{-- Input para agregar nuevos valores --}}
                                                <div class="flex items-center space-x-2">
                                                    <input type="text" 
                                                           id="newValue_{{ $index }}"
                                                           placeholder="Escribir nuevo valor y presionar Enter..."
                                                           class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                                           wire:keydown.enter="addNewValue({{ $index }}, $event.target.value); $event.target.value = '';"
                                                           wire:ignore>
                                                    <span class="text-xs text-gray-500">Presiona Enter</span>
                                                </div>
                                                
                                                {{-- Valores seleccionados (solo para mostrar) --}}
                                                @if(!empty($attribute['selectedValues']))
                                                    <div class="mt-2">
                                                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Seleccionados:</span>
                                                        <div class="flex flex-wrap gap-1 mt-1">
                                                            @foreach($attribute['selectedValues'] as $selectedValue)
                                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                                                    {{ $selectedValue }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-400 italic">Selecciona un atributo primero</span>
                                        @endif
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
                </div>

                {{-- Tabla Profesional de Variantes --}}
                @if ($showVariants && count($generatedVariants) > 0)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">Variantes del Producto</h3>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    {{ count($generatedVariants) }} variante{{ count($generatedVariants) > 1 ? 's' : '' }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Variante
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            SKU
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Precio
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Stock
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Estado
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($generatedVariants as $index => $variant)
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                                                            <span class="text-white text-sm font-medium">{{ substr($variant['name'], 0, 2) }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">{{ $variant['name'] }}</div>
                                                        <div class="text-sm text-gray-500">{{ $variant['combination'] ?? 'Combinación de atributos' }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="text" 
                                                       wire:model.lazy="variantSkus.{{ $index }}"
                                                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                                       placeholder="SKU-{{ $index + 1 }}">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-sm text-gray-500">$</span>
                                                    <input type="number" 
                                                           wire:model.lazy="variantPrices.{{ $index }}"
                                                           step="0.01" 
                                                           min="0"
                                                           class="w-24 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                                           placeholder="0.00">
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="number" 
                                                       wire:model.lazy="variantStock.{{ $index }}"
                                                       min="0"
                                                       class="w-20 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                                       placeholder="0">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Nueva
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex items-center justify-end space-x-2">
                                                    <button type="button" 
                                                            class="text-blue-600 hover:text-blue-900 transition-colors duration-150"
                                                            title="Editar variante">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </button>
                                                    <button type="button" 
                                                            class="text-gray-400 hover:text-gray-600 transition-colors duration-150"
                                                            title="Duplicar variante">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                            <div class="flex items-center justify-between text-sm text-gray-600">
                                <div class="flex items-center space-x-4">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        Todas las variantes se crearán automáticamente
                                    </span>
                                </div>
                                <div class="text-right">
                                    <span class="font-medium">Total: {{ count($generatedVariants) }} variante{{ count($generatedVariants) > 1 ? 's' : '' }}</span>
                                </div>
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
