<div>
    <style>
        @media print {

            /* Oculta todo el cuerpo por defecto */
            body * {
                visibility: hidden;
            }

            /* Muestra solo el contenedor de impresión y su contenido */
            .print-only,
            .print-only * {
                visibility: visible;
            }

            /* Asegura que el contenedor de impresión ocupe toda la página */
            .print-only {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .print-hidden {
                display: none !important;
            }
        }

        @media screen {
            .print-only {
                display: none;
            }
        }

    </style>
    <x-wire-card class="mb-4 print-hidden">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold">Generador de Códigos QR</h2>
                <p class="text-sm text-gray-600">Fuente: {{ strtoupper($type) }} #{{ $id }}</p>
            </div>
            <div class="flex flex-wrap items-end gap-3">
                <x-wire-native-select label="Estilo de Etiqueta" wire:model.live="selectedStyleId">
                    @foreach($styles as $style)
                    <option value="{{ $style->id }}">{{ $style->name }}</option>
                    @endforeach
                </x-wire-native-select>

                {{-- Aquí podrías agregar un botón para administrar los estilos --}}
                {{-- <x-wire-button light gray :href="route('admin.qr-styles.index')">Administrar Estilos</x-wire-button> --}}

                <x-wire-button light gray onclick="window.print()">
                    Imprimir etiquetas
                </x-wire-button>
                <x-wire-button light gray wire:click="openQty">
                    Cantidades
                </x-wire-button>
            </div>
        </div>
    </x-wire-card>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 print-hidden">
        @foreach($labels as $index => $label)
        @php($qty = (int) ($label['qty'] ?? 1))
        @for($i = 0; $i < max(0, $qty); $i++) {{-- Contenedor de la etiqueta con dimensiones y estilos base --}} <div class="p-3 border rounded bg-white print:shadow-none print:border-0" style="width: {{ $label_width }}mm; height: {{ $label_height }}mm; display: flex; flex-direction: column; justify-content: space-between;">

            {{-- LAYOUT POR DEFECTO (CUADRADO) --}}
            @if ($layout_type === 'default')
            <div>
                <div class="flex items-start justify-between mb-2">
                    <div>
                        @if($show_product_name)
                        <div class="text-sm font-semibold">{{ $label['product_name'] }}</div>
                        @endif
                        @if($show_description)
                        <div class="text-xs text-gray-600">{{ $label['description'] }}</div>
                        @endif
                    </div>
                    @if($showPrice)
                    <div class="text-sm font-bold">S/ {{ number_format($label['price'] ?? 0, 2) }}</div>
                    @endif
                </div>

                <div class="flex items-center justify-center">
                    @php($size = $qrSize . 'x' . $qrSize)
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size={{ $size }}&data={{ rawurlencode($label['payload']) }}" alt="QR" style="width: {{ $qrSize }}px; height: {{ $qrSize }}px;" />
                </div>
            </div>

            @if($showSku || $showBarcodeText)
            <div class="mt-2 text-center text-xs text-gray-700">
                @if($showSku)
                <div>SKU: {{ $label['sku'] }}</div>
                @endif
                @if($showBarcodeText)
                <div>BC: {{ $label['barcode'] }}</div>
                @endif
            </div>
            @endif
            @endif

            {{-- LAYOUT QR A LA IZQUIERDA (RECTANGULAR) --}}
            @if ($layout_type === 'qr_left')
            <div class="flex items-center gap-4 h-full">
                {{-- Columna QR --}}
                <div class="flex-shrink-0">
                    @php($size = $qrSize . 'x' . $qrSize)
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size={{ $size }}&data={{ rawurlencode($label['payload']) }}" alt="QR" style="width: {{ $qrSize }}px; height: {{ $qrSize }}px;" />
                </div>
                {{-- Columna de texto --}}
                <div class="flex flex-col justify-between h-full flex-grow">
                    <div>
                        @if($show_product_name)
                        <div class="text-sm font-semibold">{{ $label['product_name'] }}</div>
                        @endif
                        @if($show_description)
                        <div class="text-xs text-gray-600">{{ $label['description'] }}</div>
                        @endif
                    </div>
                    <div class="text-right">
                        @if($showPrice)
                        <div class="text-sm font-bold">S/ {{ number_format($label['price'] ?? 0, 2) }}</div>
                        @endif
                        @if($showSku)
                        <div class="text-xs text-gray-700">SKU: {{ $label['sku'] }}</div>
                        @endif
                        @if($showBarcodeText)
                        <div class="text-xs text-gray-700">BC: {{ $label['barcode'] }}</div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

    </div>
    @endfor
    @endforeach
</div>

<!-- Contenedor exclusivo de impresión -->
<div class="print-only">
    <div>
        @foreach($labels as $label)
        @php($qty = (int) ($label['qty'] ?? 1))
        @for($i = 0; $i < max(0, $qty); $i++) <div class="p-3 bg-white print:shadow-none print:border-0 print:break-after-page" style="width: {{ $label_width }}mm; height: {{ $label_height }}mm; display: flex; flex-direction: column; justify-content: space-between;">

            {{-- LAYOUT POR DEFECTO (CUADRADO) --}}
            @if ($layout_type === 'default')
            <div>
                <div class="flex items-start justify-between mb-2">
                    <div>
                        @if($show_product_name)
                        <div class="text-sm font-semibold">{{ $label['product_name'] }}</div>
                        @endif
                        @if($show_description)
                        <div class="text-xs text-gray-600">{{ $label['description'] }}</div>
                        @endif
                    </div>
                    @if($showPrice)
                    <div class="text-sm font-bold">S/ {{ number_format($label['price'] ?? 0, 2) }}</div>
                    @endif
                </div>

                <div class="flex items-center justify-center">
                    @php($size = $qrSize . 'x' . $qrSize)
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size={{ $size }}&data={{ rawurlencode($label['payload']) }}" alt="QR" style="width: {{ $qrSize }}px; height: {{ $qrSize }}px;" />
                </div>
            </div>

            @if($showSku || $showBarcodeText)
            <div class="mt-2 text-center text-xs text-gray-700">
                @if($showSku)
                <div>SKU: {{ $label['sku'] }}</div>
                @endif
                @if($showBarcodeText)
                <div>BC: {{ $label['barcode'] }}</div>
                @endif
            </div>
            @endif
            @endif

            {{-- LAYOUT QR A LA IZQUIERDA (RECTANGULAR) --}}
            @if ($layout_type === 'qr_left')
            <div class="flex items-center gap-4 h-full">
                {{-- Columna QR --}}
                <div class="flex-shrink-0">
                    @php($size = $qrSize . 'x' . $qrSize)
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size={{ $size }}&data={{ rawurlencode($label['payload']) }}" alt="QR" style="width: {{ $qrSize }}px; height: {{ $qrSize }}px;" />
                </div>
                {{-- Columna de texto --}}
                <div class="flex flex-col justify-between h-full flex-grow">
                    <div>
                        @if($show_product_name)
                        <div class="text-sm font-semibold">{{ $label['product_name'] }}</div>
                        @endif
                        @if($show_description)
                        <div class="text-xs text-gray-600">{{ $label['description'] }}</div>
                        @endif
                    </div>
                    <div class="text-right">
                        @if($showPrice)
                        <div class="text-sm font-bold">S/ {{ number_format($label['price'] ?? 0, 2) }}</div>
                        @endif
                        @if($showSku)
                        <div class="text-xs text-gray-700">SKU: {{ $label['sku'] }}</div>
                        @endif
                        @if($showBarcodeText)
                        <div class="text-xs text-gray-700">BC: {{ $label['barcode'] }}</div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

    </div>
    @endfor
    @endforeach
</div>
</div>

<!-- Modal de cantidades global -->
<div class="print-hidden">
    <x-wire-modal-card wire:model="qtyOpen" width="4xl">
        <x-slot name="title">Cantidades por etiqueta</x-slot>
        <div class="space-y-3">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="text-gray-700 border-b">
                            <th class="px-3 py-2">Código</th>
                            <th class="px-3 py-2">Descripción</th>
                            <th class="px-3 py-2">Attr</th>
                            <th class="px-3 py-2">C. Fab</th>
                            <th class="px-3 py-2 w-40">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($modalLabels as $index => $label)
                        <tr class="border-b">
                            <td class="px-3 py-2">{{ $label['sku'] }}</td>
                            <td class="px-3 py-2">{{ $label['product_name'] }}</td>
                            <td class="px-3 py-2">{{ $label['description'] }}</td>
                            <td class="px-3 py-2">{{ $label['barcode'] }}</td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <x-wire-mini-button icon="minus" primary wire:click="dec({{ $index }})" />
                                    <input type="number" min="0" class="w-16 border rounded px-2 py-1" wire:model.live="modalLabels.{{ $index }}.qty">
                                    <x-wire-mini-button icon="plus" primary wire:click="inc({{ $index }})" />
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <x-slot name="footer">
            <div class="flex justify-between items-center w-full">
                <div>
                    <x-wire-button gray wire:click="setAllZero">Poner todo a cero</x-wire-button>
                </div>
                <div class="flex gap-2">
                    <x-wire-button gray wire:click="closeQty">Cancelar</x-wire-button>
                    <x-wire-button primary wire:click="saveQty">Aceptar</x-wire-button>
                </div>
            </div>
        </x-slot>
    </x-wire-modal-card>
</div>
</div>
