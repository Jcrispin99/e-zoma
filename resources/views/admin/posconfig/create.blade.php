<x-admin-layout title="Configuración de POS" :breadcrumbs="[
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
        'icon' => 'fa-solid fa-gauge',
    ],
    [
        'name' => 'Configuración de POS',
        'icon' => 'fa-regular fa-file-lines',
        'href' => route('admin.posconfig.index'),
    ],
    [
        'name' => 'Nuevo',
    ],
]">

    <x-wire-card>

        <form action="{{ route('admin.posconfig.store') }}" method="post" class="space-y-4">

            @csrf
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">


                <x-wire-input label="Nombre" name="name" value="{{ old('name') }}" />



                <x-wire-native-select label="Almacén" name="warehouse_id">
                    <option value="">Seleccione un almacén</option>
                    @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected(old('warehouse_id',
                        $defaultWarehouseId)==$warehouse->id)>
                        {{ $warehouse->name }}
                    </option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-native-select label="Cliente por defecto" name="default_customer_id">
                    <option value="">Seleccione un cliente</option>
                    @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected(old('default_customer_id')==$customer->id)>
                        {{ $customer->name }}
                    </option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-native-select label="Diario de Boletas" name="receipt_journal_id">
                    <option value="">Seleccione un diario</option>
                    @foreach ($journals as $journal)
                    <option value="{{ $journal->id }}" @selected(old('receipt_journal_id')==$journal->id)>
                        {{ $journal->name }} ({{ $journal->code }})
                        @php
                        $seq = $journal->sequence;
                        @endphp
                        @if($seq)
                        — Secuencia: #{{ $seq->id }} Próximo {{ str_pad($seq->next_number, $seq->sequence_size, '0',
                        STR_PAD_LEFT) }}
                        @endif
                    </option>
                    @endforeach
                </x-wire-native-select>

                <x-wire-native-select label="Diario de Facturas" name="invoice_journal_id">
                    <option value="">Seleccione un diario</option>
                    @foreach ($journals as $journal)
                    <option value="{{ $journal->id }}" @selected(old('invoice_journal_id')==$journal->id)>
                        {{ $journal->name }} ({{ $journal->code }})
                        @php
                        $seq = $journal->sequence;
                        @endphp
                        @if($seq)
                        — Secuencia: #{{ $seq->id }} Próximo {{ str_pad($seq->next_number, $seq->sequence_size, '0',
                        STR_PAD_LEFT) }}
                        @endif
                    </option>
                    @endforeach
                </x-wire-native-select>

                <div class="flex items-center col-span-1 md:col-span-2">
                    <x-wire-toggle label="Activo" name="is_active" value="1" :checked="old('is_active', true)" />
                </div>
            </div>


            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-wire-native-select label="Impuesto por defecto (modelo Tax)" name="default_tax_id">
                    <option value="">Sin impuesto por defecto</option>
                    @foreach ($taxes as $tax)
                    <option value="{{ $tax->id }}" data-rate-decimal="{{ number_format($tax->rate_percent / 100, 2) }}"
                        data-price-inclusive="{{ $tax->is_price_inclusive ? 1 : 0 }}"
                        @selected(old('default_tax_id')==$tax->id)
                        >
                        {{ $tax->name }} — {{ number_format($tax->rate_percent, 2) }}% {{ $tax->is_price_inclusive ?
                        'incluido' : 'excluido' }} — {{ $tax->affectation_type_code }}
                        @if($tax->is_default) (por defecto) @endif
                    </option>
                    @endforeach
                </x-wire-native-select>

                <p class="text-sm text-gray-500 md:col-span-2">Si seleccionas un impuesto del catálogo, sincronizaremos
                    automáticamente la tasa y si los precios incluyen IGV.</p>

                <x-wire-input type="number" step="0.01" min="0" max="1" label="Tasa IGV (0.18 = 18%)" name="tax_rate"
                    value="{{ old('tax_rate', 0.18) }}" readonly />

                <div class="flex items-center">
                    <x-wire-toggle label="Aplicar IGV" name="apply_tax" value="1" :checked="old('apply_tax', true)" />
                </div>

                <div class="flex items-center">
                    <x-wire-toggle label="Precios incluyen IGV" name="prices_include_tax" value="1"
                        :checked="old('prices_include_tax', false)" />
                </div>
            </div>


            <div class="flex justify-end">
                <x-wire-button type="submit" green label="Guardar" />
            </div>

        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const taxSelect = document.querySelector('select[name="default_tax_id"]');
                const taxInput = document.querySelector('input[name="tax_rate"]');
                const applyInput = document.querySelector('[name="apply_tax"]');
                const includeInput = document.querySelector('[name="prices_include_tax"]');

                function syncFromOption(opt) {
                    if (!opt || !opt.dataset) return;
                    const rateDecimal = parseFloat(opt.dataset.rateDecimal || 'NaN');
                    const priceInclusive = opt.dataset.priceInclusive === '1';
                    if (!isNaN(rateDecimal) && taxInput) {
                        taxInput.value = rateDecimal.toFixed(2);
                    }
                    if (applyInput) {
                        const isZero = isNaN(rateDecimal) ? false : rateDecimal === 0;
                        if (applyInput.type === 'checkbox') {
                            applyInput.checked = !isZero;
                        }
                        applyInput.value = isZero ? 0 : 1;
                    }
                    if (includeInput) {
                        if (includeInput.type === 'checkbox') {
                            includeInput.checked = priceInclusive;
                        }
                        includeInput.value = priceInclusive ? 1 : 0;
                    }
                }

                taxSelect?.addEventListener('change', function() {
                    const opt = this.options[this.selectedIndex];
                    if (this.value === '') return; // no impuesto seleccionado
                    syncFromOption(opt);
                });

                // Pre-sincronizar si viene seleccionada una opción (edición o old())
                if (taxSelect && taxSelect.value !== '') {
                    const opt = taxSelect.options[taxSelect.selectedIndex];
                    syncFromOption(opt);
                }
            });

        </script>

    </x-wire-card>

</x-admin-layout>