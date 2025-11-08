<div class="space-y-6" x-data="{ activeTab: 'general' }">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <x-wire-button color="primary" icon="check" spinner wire:click="save">
                Actualizar
            </x-wire-button>
            <x-wire-button color="secondary" icon="x-mark" href="{{ route('admin.variants.index') }}" wire:navigate>
                Cancelar
            </x-wire-button>
        </div>
        <h2 class="text-lg font-semibold">
            {{ $variant?->fullName }}
        </h2>
    </div>

    <div class="border-b mb-4">
        <nav class="flex space-x-2">
            <button type="button" @click="activeTab = 'general'"
                :class="activeTab === 'general' ? 'px-4 py-2 text-sm font-medium border-b-2 border-blue-600 text-blue-700' : 'px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800'">
                Información General
            </button>
            <button type="button" @click="activeTab = 'images'"
                :class="activeTab === 'images' ? 'px-4 py-2 text-sm font-medium border-b-2 border-blue-600 text-blue-700' : 'px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800'">
                Imágenes
            </button>
        </nav>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">
        <div x-show="activeTab === 'general'" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-wire-input label="SKU" wire:model.defer="sku" placeholder="SKU de la variante" />
                <div class="flex items-end gap-2">
                    <x-wire-input label="Código de Barras" wire:model.defer="barcode" placeholder="EAN/UPC" />
                    <x-wire-button sm outline wire:click="generateBarcode">Generar</x-wire-button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-wire-input type="number" label="Precio" wire:model.defer="price" step="0.01"
                    placeholder="Precio de la variante" />
                <div>
                    <label class="block text-sm font-medium text-gray-700">Stock (informativo)</label>
                    <div class="mt-1 bg-gray-100 border border-gray-300 rounded px-3 py-2 text-gray-700">
                        {{ $stock ?? 0 }}
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div x-show="activeTab === 'images'" class="space-y-4">
        @if(!$variantId)
        <div class="p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded">
            Primero guarda la variante para poder subir imágenes.
        </div>
        @else
        @push('css')
        <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css">
        @endpush

        <form action="{{ route('admin.variants.dropzone', $variantId) }}" id="variant-dropzone"
            class="dropzone rounded border border-gray-200 bg-gray-50" method="post" enctype="multipart/form-data">
            @csrf
        </form>

        @if($variant && $variant->images && $variant->images->count())
        <div class="mt-2">
            <h3 class="text-sm font-medium text-gray-700 mb-2">Imágenes existentes</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($variant->images as $image)
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
                    const el = document.getElementById('variant-dropzone');
                    if (!el) return;

                    const dz = new Dropzone(el, {
                        paramName: 'file',
                        maxFilesize: 4,
                        acceptedFiles: 'image/*',
                        addRemoveLinks: true,
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    });

                    dz.on('success', function(file, response) {
                        file.id = response.id;
                    });

                    dz.on('removedfile', function(file) {
                        if (file && file.id) {
                            axios.delete(`/admin/images/${file.id}`).catch(() => {});
                        }
                    });
                });
        </script>
        @endpush
        @endif
    </div>
</div>