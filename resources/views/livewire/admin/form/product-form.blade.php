<div class="space-y-6" x-data="{ activeTab: 'general' }">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <x-wire-button color="primary" icon="check" spinner wire:click="save">
                {{ $isEditing ? 'Actualizar' : 'Guardar' }}
            </x-wire-button>
            <x-wire-button color="secondary" icon="x-mark" href="{{ route('admin.products.index') }}" wire:navigate>
                Cancelar
            </x-wire-button>
            @if($isEditing && $productId)
            <x-wire-button light gray label="Generar QR"
                href="{{ route('admin.qr.labels', ['type' => 'product', 'id' => $productId]) }}" />
            @endif
        </div>
        <h2 class="text-lg font-semibold">
            {{ $isEditing ? 'Editar producto' : 'Nuevo producto' }}
        </h2>
    </div>

    <div class="border-b mb-4">
        <nav class="flex space-x-2">
            <button type="button" @click="activeTab = 'general'"
                :class="activeTab === 'general' ? 'px-4 py-2 text-sm font-medium border-b-2 border-blue-600 text-blue-700' : 'px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800'">
                Información General
            </button>
            <button type="button" @click="activeTab = 'attributes'"
                :class="activeTab === 'attributes' ? 'px-4 py-2 text-sm font-medium border-b-2 border-blue-600 text-blue-700' : 'px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800'">
                Atributos y Variantes
            </button>
            <button type="button" @click="activeTab = 'images'"
                :class="activeTab === 'images' ? 'px-4 py-2 text-sm font-medium border-b-2 border-blue-600 text-blue-700' : 'px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800'">
                Imágenes
            </button>
        </nav>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">
        <!-- Datos básicos del producto -->
        <div x-show="activeTab === 'general'" class="space-y-4">
            <x-wire-input label="Nombre del Producto" wire:model.defer="name" placeholder="Nombre del producto" />

            <x-wire-textarea label="Descripción" wire:model.defer="description"
                placeholder="Descripción del producto" />

            <x-wire-input type="number" label="Precio" wire:model.defer="price" placeholder="Precio del producto"
                step="0.01" />

            <x-wire-select label="Categoría" wire:model.defer="category_id" placeholder="Seleccionar categoría..."
                :options="$categories" option-label="full_name" option-value="id" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-wire-input label="SKU (Información General)" wire:model.defer="generalSku"
                        placeholder="Ej: PROD-001-DEFAULT" />
                    <p class="text-xs text-gray-500 mt-1">Este SKU se aplicará por defecto a las variantes nuevas.</p>
                </div>
                <div>
                    <x-wire-input label="Código de Barras (Información General)"
                        wire:model.defer="variantsData.0.barcode" placeholder="EAN/UPC del producto base" />
                </div>
            </div>
        </div>

        <!-- Sección de Atributos -->
        <div class="border-t pt-6" x-show="activeTab === 'attributes'">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Atributos del Producto</h3>
                    <p class="text-sm text-gray-600">Define los atributos para generar variantes automáticamente</p>
                </div>
                <x-button type="button" wire:click="addAttribute" outline>
                    + Agregar Atributo
                </x-button>
            </div>

            @if (count($selectedAttributes ?? []) === 0)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                    <span class="text-sm text-blue-800">Aún no has agregado atributos. Usa "Agregar Atributo" para crear
                        variantes.</span>
                </div>
            </div>
            @endif

            <!-- Lista de atributos seleccionados -->
            @foreach($selectedAttributes as $index => $selectedAttribute)
            <div class="border rounded-lg p-4 mb-4 bg-gray-50">
                <div class="flex justify-between items-start mb-3">
                    <h4 class="font-medium text-gray-900">Atributo {{ $index + 1 }}</h4>
                    <button type="button" wire:click="removeAttribute({{ $index }})"
                        class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-wire-select wire:key="attribute-{{ $index }}" label="Tipo de Atributo"
                            wire:model.live="selectedAttributes.{{ $index }}.attribute_id"
                            placeholder="Seleccione un atributo..." :async-data="[
                                        'api' => route('api.attributes.index'),
                                        'method' => 'POST',
                                    ]" option-label="name" option-value="id" />
                    </div>

                    <div>
                        @if(isset($selectedAttribute['attribute_id']) && $selectedAttribute['attribute_id'])
                        <x-wire-select wire:key="attribute-values-{{ $index }}-{{ $selectedAttribute['attribute_id'] }}"
                            label="Valores del Atributo" wire:model.live="selectedAttributes.{{ $index }}.values"
                            placeholder="Seleccione valores..." :async-data="[
                                                'api' => route('api.attribute-values.show', ['attributeId' => $selectedAttribute['attribute_id']]),
                                                'method' => 'POST',
                                            ]" option-label="value" option-value="id" multiselect />
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
            @php $hasValues = false; foreach(($selectedAttributes ?? []) as $a){ if(!empty($a['values'])) { $hasValues =
            true; break; } } @endphp
            @if(count($variantsData ?? []) > 0)
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Detalles de las Variantes Generadas</h3>
                <div class="space-y-4">
                    @foreach($variantsData as $index => $variant)
                    <div class="border rounded-lg p-4 bg-gray-50"
                        wire:key="variant-{{ $index }}-{{ isset($variant['attribute_values']) ? implode('-', $variant['attribute_values']) : 'default' }}">
                        <p class="font-medium text-gray-800 mb-3">
                            Variante: {{ $variant['description'] }}
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <x-wire-input label="SKU" wire:model="variantsData.{{ $index }}.sku"
                                placeholder="SKU de la variante" />
                            <x-wire-input type="number" label="Precio" wire:model="variantsData.{{ $index }}.price"
                                placeholder="Precio de la variante" step="0.01" />
                            <x-wire-input label="Código de Barras" wire:model="variantsData.{{ $index }}.barcode"
                                placeholder="Código de Barras (EAN, UPC, etc.)" />
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Stock</label>
                                <div class="mt-1 bg-gray-100 border border-gray-300 rounded px-3 py-2 text-gray-700">
                                    {{ $variant['stock'] ?? 0 }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @elseif(count($selectedAttributes ?? []) > 0 && !$hasValues)
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
    </form>

    <!-- Pestaña de Imágenes (fuera del <form> para evitar formularios anidados) -->
    <div x-show="activeTab === 'images'" class="space-y-4">
        @if(!$isEditing)
        <div class="p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded">
            Primero guarda el producto para poder subir y gestionar imágenes.
        </div>
        @else
        @push('css')
        <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css">
        @endpush

        <form action="{{ route('admin.products.dropzone', $productId) }}" id="product-dropzone"
            class="dropzone rounded border border-gray-200 bg-gray-50" method="post" enctype="multipart/form-data">
            @csrf
        </form>

        @if($product && $product->images && $product->images->count())
        <div class="mt-2">
            <h3 class="text-sm font-medium text-gray-700 mb-2">Imágenes existentes</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($product->images as $image)
                <div class="relative rounded border border-gray-200 bg-white p-2">
                    <img src="{{ Storage::url($image->path) }}" alt="Imagen" class="w-full h-32 object-cover rounded">
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @push('js')
        <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                        const el = document.getElementById('product-dropzone');
                        if (!el) return;

                        const dz = new Dropzone(el, {
                            paramName: 'file',
                            maxFilesize: 4,
                            acceptedFiles: 'image/*',
                            addRemoveLinks: true,
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        });

                        dz.on('success', function(file, response) {
                            // Guardar el id de la imagen para poder eliminarla luego
                            file.id = response.id;
                        });

                        dz.on('removedfile', function(file) {
                            if (file && file.id) {
                                axios.delete(`/admin/images/${file.id}`)
                                    .catch(() => {});
                            }
                        });
                    });
        </script>
        @endpush
        @endif
    </div>
</div>