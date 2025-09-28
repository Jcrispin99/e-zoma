<div>
    <form wire:submit="update" class="space-y-6">
        
        <!-- Datos básicos del producto -->
        <div class="space-y-4">
            <x-wire-input 
                label="Nombre" 
                wire:model="name" 
                placeholder="Nombre del producto" 
            />
            
            <x-wire-textarea 
                label="Descripción" 
                wire:model="description" 
                placeholder="Descripción del producto"
            />
            
            <x-wire-input 
                type="number" 
                label="Precio" 
                wire:model="price" 
                placeholder="Precio del producto" 
                step="0.01"
            />
            
            <x-wire-native-select label="Categoría" wire:model="category_id">
                <option value="">Seleccionar categoría...</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->full_name }}</option>
                @endforeach
            </x-wire-native-select>
        </div>

        <!-- Sección de Atributos -->
        <div class="border-t pt-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Atributos del Producto</h3>
                    <p class="text-sm text-gray-600">Modifica los atributos para actualizar las variantes automáticamente</p>
                </div>
                <x-button type="button" wire:click="addAttribute" outline>
                    + Agregar Atributo
                </x-button>
            </div>

            <!-- Lista de atributos seleccionados -->
            @foreach($selectedAttributes as $index => $selectedAttribute) 
                <div class="border rounded-lg p-4 mb-4 bg-gray-50">
                    <div class="flex justify-between items-start mb-3">
                        <h4 class="font-medium text-gray-900">Atributo {{ $index + 1 }}</h4>
                        <button 
                            type="button" 
                            wire:click="removeAttribute({{ $index }})"
                            class="text-red-600 hover:text-red-800"
                        >
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Selector de Atributo -->
                        <div>
                            <x-wire-select 
                                wire:key="attribute-{{ $index }}"
                                label="Tipo de Atributo" 
                                wire:model.live="selectedAttributes.{{ $index }}.attribute_id"
                                placeholder="Seleccione un atributo..."
                                :async-data="[
                                    'api' => route('api.attributes.index'),
                                    'method' => 'POST',
                                ]"
                                option-label="name" 
                                option-value="id"
                            />
                        </div>

                        <div>
                            @if(isset($selectedAttribute['attribute_id']) && $selectedAttribute['attribute_id'])
                                <x-wire-select 
                                    wire:key="attribute-values-{{ $index }}-{{ $selectedAttribute['attribute_id'] }}"
                                    label="Valores del Atributo" 
                                    wire:model.live="selectedAttributes.{{ $index }}.values"
                                    placeholder="Seleccione valores..."
                                    :async-data="[
                                        'api' => route('api.attribute-values.show', ['attributeId' => $selectedAttribute['attribute_id']]),
                                        'method' => 'POST',
                                    ]"
                                    option-label="value" 
                                    option-value="id"
                                    multiselect
                                />
                            @else
                                <x-wire-native-select label="Valores del Atributo" disabled>
                                    <option value="">Primero seleccione un atributo...</option>
                                </x-wire-native-select>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Preview de variantes -->
            @if(!empty($variantsData))
                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Gestión de Variantes</h3>
                    <div class="space-y-4">
                        @foreach($variantsData as $index => $variant)
                            <div class="border rounded-lg p-4 {{ $variant['is_existing'] ? 'bg-green-50 border-green-200' : 'bg-blue-50 border-blue-200' }}">
                                <div class="flex justify-between items-center mb-3">
                                    <p class="font-medium text-gray-800">
                                        Variante: {{ $variant['description'] }}
                                    </p>
                                    <div class="flex items-center space-x-2">
                                        @if($variant['is_existing'])
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check mr-1"></i>
                                                Existente
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <i class="fas fa-plus mr-1"></i>
                                                Nueva
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <x-wire-input 
                                        label="SKU"
                                        wire:model="variantsData.{{ $index }}.sku" 
                                        placeholder="SKU de la variante"
                                    />
                                    <x-wire-input 
                                        type="number"
                                        label="Precio" 
                                        wire:model="variantsData.{{ $index }}.price" 
                                        placeholder="Precio de la variante"
                                        step="0.01"
                                    />
                                    <x-wire-input 
                                        label="Código de Barras"
                                        wire:model="variantsData.{{ $index }}.barcode" 
                                        placeholder="Código de Barras (EAN, UPC, etc.)"
                                    />
                                    @if($variant['is_existing'])
                                        <x-wire-input 
                                            type="number"
                                            label="Stock Actual" 
                                            value="{{ $variant['stock'] ?? 0 }}" 
                                            readonly
                                            class="bg-gray-100"
                                        />
                                    @else
                                        <div class="flex items-end">
                                            <span class="text-sm text-gray-500 bg-gray-100 px-3 py-2 rounded-md border">
                                                Stock: 0 (nueva variante)
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @elseif(count($selectedAttributes) > 0)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        <span class="text-sm text-blue-800">
                            Seleccione valores para los atributos para generar las variantes.
                        </span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Información adicional -->
        @if(!empty($variantsData))
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">Resumen de Cambios</h4>
                        <p class="text-sm text-gray-600">
                            Total de variantes: {{ count($variantsData) }} 
                            ({{ collect($variantsData)->where('is_existing', true)->count() }} existentes, 
                            {{ collect($variantsData)->where('is_existing', false)->count() }} nuevas)
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">
                            Las variantes eliminadas se borrarán permanentemente
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Botones de acción -->
        <div class="flex justify-end space-x-3">
            <x-button type="button" outline href="{{ route('admin.products.index') }}">
                Cancelar
            </x-button>
            <x-button type="submit">
                Actualizar Producto
            </x-button>
        </div>
    </form>
</div>
