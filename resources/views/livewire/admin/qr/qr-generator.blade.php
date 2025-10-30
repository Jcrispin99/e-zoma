<div>
    <style>
        @media print {
            .print-hidden {
                display: none !important;
            }

            .print-only {
                display: block !important;
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
                <x-wire-native-select label="Columnas" wire:model="columns" class="w-28">
                    @for($i = 1; $i <= 6; $i++) <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                </x-wire-native-select>

                <x-wire-input type="number" min="100" max="600" step="10" label="Tamaño QR (px)" wire:model.live="qrSize" class="w-36" />

                <div class="flex items-center gap-2">
                    <x-wire-checkbox id="showSku" wire:model="showSku" />
                    <x-label for="showSku">Mostrar SKU</x-label>
                </div>
                <div class="flex items-center gap-2">
                    <x-wire-checkbox id="showBarcodeText" wire:model="showBarcodeText" />
                    <x-label for="showBarcodeText">Mostrar Código de Barras</x-label>
                </div>
                <div class="flex items-center gap-2">
                    <x-wire-checkbox id="showPrice" wire:model="showPrice" />
                    <x-label for="showPrice">Mostrar Precio</x-label>
                </div>

                <x-wire-button light gray onclick="window.print()">
                    Imprimir etiquetas
                </x-wire-button>
                <x-wire-button light gray wire:click="openQty">
                    Cantidades
                </x-wire-button>
            </div>
        </div>
    </x-wire-card>

    <div class="grid gap-4 print-hidden" style="grid-template-columns: repeat({{ $columns }}, minmax(0, 1fr));">
        @foreach($labels as $index => $label)
        @php($qty = (int) ($label['qty'] ?? 1))
        @for($i = 0; $i < max(0, $qty); $i++) <div class="p-3 border rounded bg-white print:shadow-none print:border-0">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <div class="text-sm font-semibold">{{ $label['product_name'] }}</div>
                    <div class="text-xs text-gray-600">{{ $label['description'] }}</div>
                </div>
                @if($showPrice)
                <div class="text-sm font-bold">S/ {{ number_format($label['price'] ?? 0, 2) }}</div>
                @endif
            </div>

            <div class="flex items-center justify-center">
                @php($size = $qrSize . 'x' . $qrSize)
                <img src="https://api.qrserver.com/v1/create-qr-code/?size={{ $size }}&data={{ rawurlencode($label['payload']) }}" alt="QR" style="width: {{ $qrSize }}px; height: {{ $qrSize }}px;" />
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
    </div>
    @endfor
    @endforeach
</div>

<!-- Contenedor exclusivo de impresión -->
<div class="print-only">
    <div class="grid gap-4" style="grid-template-columns: repeat({{ $columns }}, minmax(0, 1fr));">
        @foreach($labels as $label)
        @php($qty = (int) ($label['qty'] ?? 1))
        @for($i = 0; $i < max(0, $qty); $i++) <div class="p-3 border rounded bg-white print:shadow-none print:border-0">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <div class="text-sm font-semibold">{{ $label['product_name'] }}</div>
                    <div class="text-xs text-gray-600">{{ $label['description'] }}</div>
                </div>
                @if($showPrice)
                <div class="text-sm font-bold">S/ {{ number_format($label['price'] ?? 0, 2) }}</div>
                @endif
            </div>

            <div class="flex items-center justify-center">
                @php($size = $qrSize . 'x' . $qrSize)
                <img src="https://api.qrserver.com/v1/create-qr-code/?size={{ $size }}&data={{ rawurlencode($label['payload']) }}" alt="QR" style="width: {{ $qrSize }}px; height: {{ $qrSize }}px;" />
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
