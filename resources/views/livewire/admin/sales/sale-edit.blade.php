<div x-data="{
    variants: @entangle('variants'),
    taxes: @entangle('taxes'),
    total: @entangle('total'),
    subtotal: 0,
    taxTotal: 0,
    scanBuffer: '',
    lastKeyTime: 0,
    removeVariant(index) { this.variants.splice(index, 1); },
    init() {
        const calc = () => {
            let subtotal = 0;
            let taxTotal = 0;
            (this.variants || []).forEach(v => {
                const qty = Number(v.quantity) || 0;
                const price = Number(v.price) || 0;
                const line = qty * price;
                const tax = (this.taxes || []).find(t => String(t.id) === String(v.tax_id)) || null;
                const rate = tax ? Number(tax.rate_percent) || 0 : 0;
                const inclusive = tax ? Boolean(tax.is_price_inclusive) : false;
                const base = (inclusive && rate > 0) ? (line / (1 + (rate / 100))) : line;
                const taxAmt = base * (rate / 100);
                subtotal += base;
                taxTotal += taxAmt;
            });
            $data.subtotal = subtotal;
            $data.taxTotal = taxTotal;
            $data.total = subtotal + taxTotal;
        };
        calc();
        this.$watch('variants', () => calc(), { deep: true });
    },
    handleScanner(e) {
        const key = e.key;
        const now = Date.now();
        if (key === 'Enter') {
            if (this.scanBuffer.length > 0) {
                this.$wire.scanBarcode(this.scanBuffer);
                this.scanBuffer = '';
                e.preventDefault();
            }
            return;
        }
        if (key === 'Backspace') { this.scanBuffer = this.scanBuffer.slice(0, -1); return; }
        if (/^[A-Za-z0-9\-_.]$/.test(key)) {
            if (this.lastKeyTime && now - this.lastKeyTime > 300) { this.scanBuffer = ''; }
            this.scanBuffer += key; this.lastKeyTime = now;
        }
    }
}" x-on:keydown.window="handleScanner($event)">
    @php
    $status = (string) ($sale->status ?? 'draft');
    $sunat = (string) ($sale->sunat_status ?? 'pending');
    $payment = (string) ($sale->payment_status ?? 'unpaid');
    $docCode = (string) (optional($sale->journal)->document_type_code ?? '');
    $isFiscalJournal = (bool) (optional($sale->journal)->is_fiscal ?? false);
    // Base SUNAT válida: factura (01) o boleta (03) y diario fiscal
    $isSunatBaseDoc = $isFiscalJournal && in_array($docCode, ['01','03'], true);
    $blockedSunatForEdit = ['queued','processing','accepted','observed','cancelled','sent'];
    $isCancelledDoc = ($status === 'cancelled' || $sunat === 'cancelled');
    $isBoleta = ($docCode === '03');
    $isBlockedEdit = ($status === 'posted' && in_array($sunat, $blockedSunatForEdit));
    $isLimitedEdit = ($status === 'posted' && in_array($sunat, ['pending','skipped']));
    $isFullEdit = (!$isCancelledDoc && !$isBlockedEdit && !$isLimitedEdit);
    $canEdit = !$isCancelledDoc && ($isLimitedEdit || $isFullEdit);

    $hasPayments = in_array($payment, ['partial','paid']);
    $canCancel = ($status === 'draft') || ($status === 'posted' && in_array($sunat,
    ['pending','error','rejected','skipped']) &&
    !$hasPayments);
    $canReopen = ($status === 'posted' && ! in_array($sunat,
    ['accepted','queued','processing','cancelled','sent','observed']));
    $canRegisterPayment = ($status === 'posted' && $payment !== 'paid');
    $canSendSunat = ($status === 'posted' && in_array($sunat, ['pending','error','rejected','observed']));
    $canCreateNotes = ($status === 'posted' && in_array($sunat, ['accepted','observed']));
    // Nota de Venta (no fiscal): document_type_code vacío y journal no fiscal
    $isNonFiscalNv = (!$isFiscalJournal && ($docCode === '' || $docCode === null));
    @endphp

    <x-wire-card class="mb-3">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-2">
                <x-wire-button :label="$isLimitedEdit ? 'Guardar (limitado)' : 'Guardar'" right-icon="check" positive
                    wire:click="save" :disabled="!$canEdit" />
                <x-wire-badge :label="str($sale->status)->upper()"
                    :color="$sale->status === 'draft' ? 'slate' : ($sale->status === 'posted' ? 'emerald' : 'rose')" />
                @if($sale->payment_status)
                <x-wire-badge :label="str($sale->payment_status)->upper()"
                    :color="$sale->payment_status === 'paid' ? 'emerald' : ($sale->payment_status === 'partial' ? 'amber' : 'slate')" />
                @endif
                @if($sale->sunat_status)
                @php
                $sunatColor = match($sale->sunat_status) {
                'pending' => 'slate',
                'queued' => 'blue',
                'processing' => 'amber',
                'accepted' => 'emerald',
                'rejected' => 'rose',
                'observed' => 'orange',
                'error' => 'red',
                'cancelled' => 'purple',
                'sent' => 'sky',
                default => 'gray',
                };
                @endphp
                <x-wire-badge :label="'SUNAT: ' . str($sale->sunat_status)->upper()" :color="$sunatColor" />
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-wire-dropdown icon="bars-3" align="right">
                    @if($sale->status === 'draft')
                    <x-wire-dropdown.item label="Recibir" wire:click="post" />
                    @if($isNonFiscalNv)
                    <x-wire-dropdown.item label="Registrar pago" wire:click="markPaid" spinner />
                    @endif
                    <x-wire-dropdown.item label="Cancelar" wire:click="cancel" />
                    @elseif($sale->status === 'posted')
                    @if($canRegisterPayment)
                    <x-wire-dropdown.item label="Registrar pago" wire:click="markPaid" />
                    @endif
                    @if($canCancel)
                    <x-wire-dropdown.item label="Anular" wire:click="cancel" />
                    @endif
                    @if($canReopen)
                    <x-wire-dropdown.item label="Reabrir" wire:click="reopen" />
                    @endif
                    @elseif($sale->status === 'cancelled')
                    @endif

                    <x-wire-dropdown.header separator label="Acciones" />
                    <x-wire-dropdown.item label="Enviar factura por correo" wire:click="openModal({{ $sale }})" />
                    <x-wire-dropdown.item label="Descargar PDF" :href="route('admin.sales.pdf', $sale)" />
                    <x-wire-dropdown.item label="Ver PDF (vista)" :href="route('admin.sales.pdf.view', $sale)" />

                    @if($hasSunatConnection && $canSendSunat && $isSunatBaseDoc)
                    <x-wire-dropdown.item label="Enviar SUNAT" wire:click="sendSunat" spinner />
                    @endif

                    @if($hasSunatConnection && $canCreateNotes && $isSunatBaseDoc && !($isBoleta && $hasReturnChildren))
                    <x-wire-dropdown.item label="Nota de Crédito" wire:click="sendStaticCreditNote" />
                    <x-wire-dropdown.item label="Nota de Débito" wire:click="sendStaticDebitNote" />
                    @endif

                    @if($sale->status === 'posted' && (!$hasSunatConnection || !$isSunatBaseDoc) && !$hasReturnChildren)
                    <x-wire-dropdown.item label="Devolver productos" wire:click="startReturnDraft" spinner />
                    @endif

                    <x-wire-dropdown.item label="Volver" :href="route('admin.sales.index')" />
                </x-wire-dropdown>
            </div>
        </div>
        @php $pollStatuses = ['queued','processing']; @endphp
        @if(in_array($sale->sunat_status, $pollStatuses))
        <div class="hidden" wire:poll.15s="refreshSale"></div>
        @endif
    </x-wire-card>



    @php
    $docType = (string) (optional($sale->journal)->document_type_code ?? '');
    $isNote = in_array($docType, ['07','08'], true);
    $isCredit = $docType === '07';
    // Motivo SUNAT dinámico: NC 06 (total) vs 07 (por ítem); ND 02 por defecto
    $motivoCode = null; $motivoLabel = null;
    if ($isNote) {
        if ($isCredit) {
            // Comparar cantidades con la venta original para decidir 06 vs 07
            $origSale = $sale->originalSale;
            $origMap = []; $noteMap = [];
            if ($origSale) {
                foreach ($origSale->variants as $v) { $origMap[(string)$v->id] = (int)($v->pivot->quantity ?? 0); }
                foreach ($sale->variants as $v) { $noteMap[(string)$v->id] = (int)($v->pivot->quantity ?? 0); }
                $isFull = (count($origMap) > 0) && (count($origMap) === count($noteMap));
                foreach ($origMap as $id => $qty) {
                    if (!isset($noteMap[$id]) || $noteMap[$id] !== $qty) { $isFull = false; break; }
                }
                if ($isFull) { $motivoCode = '06'; $motivoLabel = 'DEVOLUCIÓN TOTAL'; }
                else { $motivoCode = '07'; $motivoLabel = 'DEVOLUCIÓN POR ÍTEM'; }
            } else { $motivoCode = '01'; $motivoLabel = 'ANULACION DE LA OPERACION'; }
        } else {
            $motivoCode = '02'; $motivoLabel = 'AUMENTO EN EL VALOR';
        }
    }
    // Documento afectado (tipo y número) desde los campos persistidos o la relación
    $affType = (string) ($sale->original_document_type_code ??
    (optional(optional($sale->originalSale)->journal)->document_type_code ?? ''));
    $affSerie = (string) ($sale->original_serie ?? (optional($sale->originalSale)->serie ?? ''));
    $affCorr = (string) ($sale->original_correlative ?? (optional($sale->originalSale)->correlative ?? ''));
    $affNumber = trim(($affSerie !== '' && $affCorr !== '') ? ($affSerie . '-' . $affCorr) : '');
    if ($affType === '' && $affSerie !== '') {
    $affType = str_starts_with($affSerie, 'F') ? '01' : (str_starts_with($affSerie, 'B') ? '03' : '');
    }
    @endphp

    @if($isNote)
    <x-wire-card>
        <div class="grid lg:grid-cols-4 gap-4">
            <div>
                <x-label>Tipo de comprobante</x-label>
                <div class="text-sm text-gray-800">{{ $isCredit ? 'Nota de Crédito (07)' : 'Nota de Débito (08)' }}
                </div>
            </div>
            <div>
                <x-label>Documento afectado (tipo)</x-label>
                <div class="text-sm text-gray-800">{{ $affType !== '' ? $affType : '—' }}</div>
            </div>
            <div>
                <x-label>Documento afectado (número)</x-label>
                <div class="text-sm text-gray-800">{{ $affNumber !== '' ? $affNumber : '—' }}</div>
            </div>
            <div>
                <x-label>Motivo SUNAT</x-label>
                <div class="text-sm text-gray-800">{{ $motivoCode && $motivoLabel ? ($motivoCode . ' - ' . $motivoLabel)
                    : '—' }}</div>
            </div>
        </div>
    </x-wire-card>
    @endif

    <x-wire-card>
        <form wire:submit="save" class="space-y-4" x-on:keydown.enter.prevent>
            <div class="grid lg:grid-cols-4 gap-4">
                <x-wire-input label="Serie" wire:model="serie" disabled />
                <x-wire-input label="Correlativo" wire:model="correlative" disabled />
                <x-wire-input label="Fecha" wire:model.live="date" type="date" :disabled="$isLimitedEdit" />

                <div class="col-span-2">
                    <x-wire-select label="Cliente" wire:model="customer_id" placeholder="Seleccione un cliente"
                        :async-data="['api' => route('api.customers.index'), 'method' => 'POST']" option-label="name"
                        option-value="id" class="flex-1" option-description="description" :disabled="$isLimitedEdit" />
                </div>
                <div class="col-span-2">
                    <x-wire-select label="Almacenes" wire:model="warehouse_id" placeholder="Seleccione un almacén"
                        :async-data="['api' => route('api.warehouse.index'), 'method' => 'POST', 'params' => ['company_ids' => session()->get('selected_company_ids', [])]]"
                        option-label="name" option-value="id" class="flex-1" option-description="description" />
                </div>
            </div>

            <div class="lg:flex lg:space-x-4">
                <x-wire-select label="Producto" wire:model="variant_id" placeholder="Seleccione un producto"
                    :disabled="$isLimitedEdit" :async-data="['api' => route('api.product.index'), 'method' => 'POST']"
                    option-label="name" option-value="id" class="flex-1" />
                <div class="flex-shrink-0">
                    <x-wire-button wire:click="addProduct" class="mt-4 w-full lg:mt-6.5" spinner
                        :disabled="$isLimitedEdit">Agregar producto
                    </x-wire-button>
                </div>
            </div>

            <div class="overflow-x-auto w-full">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="text-gray-700 border-y bg-blue-50">
                            <th class="px-6 py-2">Producto</th>
                            <th class="px-6 py-2">Cantidad</th>
                            <th class="px-6 py-2">Precio</th>
                            <th class="px-6 py-2">Impuesto</th>
                            <th class="px-6 py-2">Sin Imp.</th>
                            <th class="px-6 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(variant, index) in variants" :key="variant.id">
                            <tr class="border-b">
                                <td class="px-4 py-1" x-text="variant.name" />
                                <td class="px-4 py-1">
                                    <x-wire-input type="number" x-model="variant.quantity" :disabled="$isLimitedEdit" />
                                </td>
                                <td class="px-4 py-1">
                                    <x-wire-input type="number" x-model="variant.price" step="0.01" class="w-20"
                                        :disabled="$isLimitedEdit" />
                                </td>
                                <td class="px-4 py-1">
                                    <x-wire-native-select x-model="variant.tax_id" :disabled="$isLimitedEdit">
                                        <template x-for="tax in taxes" :key="tax.id">
                                            <option :value="tax.id"
                                                x-text="`${tax.invoice_label ?? tax.name}${tax.is_price_inclusive ? ' (TTC)' : ''}`">
                                            </option>
                                        </template>
                                    </x-wire-native-select>
                                </td>
                                <td class="px-4 py-1"
                                    x-text="(() => { const t = (taxes||[]).find(tt => String(tt.id)===String(variant.tax_id)); const r = t ? Number(t.rate_percent)||0 : 0; const inc = t ? Boolean(t.is_price_inclusive) : false; const line = (Number(variant.quantity)||0) * (Number(variant.price)||0); const base = (inc && r>0) ? (line/(1+(r/100))) : line; return base.toFixed(2); })()">
                                </td>
                                <td class="px-4 py-1">
                                    <x-wire-mini-button rounded x-on:click="removeVariant(index)" icon="trash" red
                                        :disabled="$isLimitedEdit" />
                                </td>
                            </tr>
                        </template>
                        <template x-if="variants.length === 0">
                            <tr>
                                <td colspan="6" class="text-center text-gray-500 py-4">No hay productos agregados</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="fex items-center space-x-4">
                <x-label>Observaciones</x-label>
                <x-wire-input wire:model="observation" placeholder="Ingrese observaciones" class="flex-1" />
            </div>
            <div>
                Subtotal: $<span x-text="Number(subtotal ?? 0).toFixed(2)"></span><br>
                Impuestos: $<span x-text="Number(taxTotal ?? 0).toFixed(2)"></span><br>
                Total: $<span x-text="Number(total ?? 0).toFixed(2)"></span>
            </div>


        </form>
    </x-wire-card>

    <x-wire-modal-card wire:model="form.open" width="lg">
        <x-slot name="title">
            <p class="text-xl text-center mb-2">Enviar email</p>
            <p class="text-lg text-center uppercase font-bold mb-2">{{ $form['document'] }}</p>
            <p class="text-lg text-center mb-2">{{ $form['client'] }}</p>
        </x-slot>

        <form wire:submit="sendEmail">
            <x-wire-input label="Correo" wire:model="form.email" class="mb-4" value="{{ $form['email'] }}" />
            <x-wire-button type="submit" class="w-full">
                Enviar
            </x-wire-button>
        </form>
    </x-wire-modal-card>
</div>
