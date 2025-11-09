<?php

namespace App\Livewire\Admin\sales;

use App\Facades\Kardex;
use App\Models\Sale;
use App\Models\Variant;
use App\Models\Warehouse;
use App\Models\Customer;
use Livewire\Component;
use Illuminate\Support\Facades\Mail;
use App\Mail\PdfSend;
use App\Jobs\SendSunatInvoice;
use App\Services\GreenterInvoiceService;
use App\Models\Tax;

class SaleEdit extends Component
{
    public Sale $sale;

    public $serie;
    public $correlative;
    public $date;
    public $warehouse_id;
    public $customer_id;
    public $total = 0;
    public $observation;

    public $variant_id;
    public $variants = [];
    public $taxes = [];
    public $default_tax_id = null;

    public $status;
    public $payment_status;
    public $sunat_status;

    // Propiedades para el modal de envío de correo
    public $form = [
        'open' => false,
        'document' => '',
        'client' => '',
        'email' => '',
        'model' => null,
        'view_pdf_patch' => 'admin.sales.pdf',
    ];

    // Estado para el flujo de creación de notas (Crédito/Débito/Venta)
    public $noteForm = [
        'open' => false,
        'type' => null, // 'credit' | 'debit' | 'sales_note'
        'title' => null,
        'reason_code' => null, // 01 | 02 (SUNAT)
        'reason_label' => null,
        'mode' => 'total', // 'total' por ahora
        'observation' => null,
    ];

    public function mount(Sale $sale)
    {
        $this->sale = $sale->load('variants.product', 'variants.attributeValues', 'customer', 'warehouse');

        $this->serie = $sale->serie;
        $this->correlative = $sale->correlative;
        $this->date = optional($sale->date)->format('Y-m-d');
        $this->customer_id = $sale->customer_id;
        $this->warehouse_id = $sale->warehouse_id;
        $this->observation = $sale->observation;
        $this->status = $sale->status;
        $this->payment_status = $sale->payment_status;
        $this->sunat_status = $sale->sunat_status;

        // Cargar impuestos activos y definir por defecto
        $this->taxes = Tax::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'rate_percent' => (float) $t->rate_percent,
                    'is_price_inclusive' => (bool) $t->is_price_inclusive,
                    'is_default' => (bool) $t->is_default,
                    'invoice_label' => $t->invoice_label ?? null,
                ];
            })
            ->toArray();
        $default = collect($this->taxes)->firstWhere('is_default', true) ?? collect($this->taxes)->first();
        $this->default_tax_id = $default['id'] ?? null;

        $taxesCol = collect($this->taxes);
        $this->variants = $sale->variants->map(function ($variant) use ($taxesCol) {
            $pivotRate = (float) ($variant->pivot->tax_rate ?? 0);
            $matched = $taxesCol->firstWhere('rate_percent', $pivotRate) ?? $taxesCol->first();
            $rate = (float) ($matched['rate_percent'] ?? 0);
            $inclusive = (bool) ($matched['is_price_inclusive'] ?? false);
            $lineTotal = (float) ($variant->pivot->quantity ?? 0) * (float) ($variant->pivot->price ?? 0);
            $base = ($inclusive && $rate > 0) ? ($lineTotal / (1 + ($rate / 100))) : $lineTotal;
            return [
                'id' => $variant->id,
                'name' => $variant->fullName,
                'quantity' => $variant->pivot->quantity,
                'price' => $variant->pivot->price,
                'tax_id' => $matched['id'] ?? null,
                'tax_rate' => $rate,
                'tax_inclusive' => $inclusive,
                'subtotal' => $base,
            ];
        })->toArray();

        $this->total = $sale->total;
    }

    public function addProduct()
    {
        $this->validate([
            'variant_id' => 'required|exists:variants,id',
        ], [], [
            'variant_id' => 'producto',
        ]);

        $existing = collect($this->variants)->firstWhere('id', $this->variant_id);
        if ($existing) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'El producto ya fue agregado',
                'text' => 'El producto ya se encuentra en la fila',
            ]);
            return;
        }

        $variant = Variant::with('product')->find($this->variant_id);

        $tax = $this->default_tax_id ? Tax::find($this->default_tax_id) : null;
        $rate = (float) optional($tax)->rate_percent ?? 0;
        $inclusive = (bool) optional($tax)->is_price_inclusive ?? false;
        $lineTotal = 1 * ((float) ($variant->price ?? 0));
        $base = ($inclusive && $rate > 0) ? ($lineTotal / (1 + ($rate / 100))) : $lineTotal;

        $this->variants[] = [
            'id' => $variant->id,
            'name' => $variant->fullName,
            'quantity' => 1,
            'price' => $variant->price,
            'tax_id' => $this->default_tax_id,
            'tax_rate' => $rate,
            'tax_inclusive' => $inclusive,
            'subtotal' => $base,
        ];
        $this->reset('variant_id');
    }

    public function scanBarcode($code = null)
    {
        $code = trim((string)($code ?? ''));
        if ($code === '') {
            return;
        }

        $variant = Variant::where('barcode', $code)
            ->orWhere('sku', $code)
            ->first();

        if (!$variant) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Producto no encontrado',
                'text' => 'No existe un producto con ese código o SKU.',
            ]);
            return;
        }

        $index = collect($this->variants)->search(fn($v) => ($v['id'] ?? null) === $variant->id);
        if ($index !== false) {
            $current = $this->variants[$index];
            $current['quantity'] = (int)($current['quantity'] ?? 0) + 1;
            $rate = (float) ($current['tax_rate'] ?? 0);
            $inclusive = (bool) ($current['tax_inclusive'] ?? false);
            $lineTotal = (float)($current['quantity'] ?? 0) * (float)($current['price'] ?? 0);
            $current['subtotal'] = ($inclusive && $rate > 0)
                ? ($lineTotal / (1 + ($rate / 100)))
                : $lineTotal;
            $this->variants[$index] = $current;
            return;
        }

        $this->variant_id = $variant->id;
        $this->addProduct();
        $this->reset('variant_id');
    }

    public function save()
    {
        // Evaluar permisos de edición según matriz de estados
        $status = (string) ($this->sale->status ?? 'draft');
        $sunat = (string) ($this->sale->sunat_status ?? 'pending');

        // Bloque total: documento cancelado o baja SUNAT
        if ($status === 'cancelled' || in_array($sunat, ['cancelled'], true)) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Edición no permitida',
                'text' => 'El documento está anulado; no se puede editar.',
            ]);
            return;
        }

        // Bloque en estados SUNAT que impiden edición
        $blockedSunatForEdit = ['queued', 'processing', 'accepted', 'observed', 'sent'];
        $isBlockedEdit = ($status === 'posted' && in_array($sunat, $blockedSunatForEdit, true));
        if ($isBlockedEdit) {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'Edición bloqueada',
                'text' => 'No se puede editar en el estado SUNAT/contable actual.',
            ]);
            return;
        }

        // Edición limitada: posted + pending (o skipped)
        $isLimitedEdit = ($status === 'posted' && in_array($sunat, ['pending', 'skipped'], true));
        if ($isLimitedEdit) {
            $this->validate([
                'warehouse_id' => 'required|exists:warehouses,id',
                'observation' => 'nullable|string|max:255',
            ], [], [
                'warehouse_id' => 'almacén',
                'observation' => 'observación',
            ]);

            $this->sale->update([
                'warehouse_id' => $this->warehouse_id,
                'observation' => $this->observation,
            ]);

            $this->dispatch('swal', [
                'icon' => 'success',
                'title' => 'Edición limitada aplicada',
                'text' => 'Se actualizaron observaciones y almacén. Los productos no pueden cambiarse.',
            ]);
            return;
        }

        // Edición completa: draft, o posted con error/rejected
        $this->validate(
            [
                'date' => 'nullable|date',
                'customer_id' => 'required|exists:customers,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'total' => 'required|numeric|min:0',
                'observation' => 'nullable|string|max:255',
                'variants' => 'required|array|min:1',
                'variants.*.id' => 'required|exists:variants,id',
                'variants.*.quantity' => 'required|numeric|min:1',
                'variants.*.price' => 'required|numeric|min:0',
                'variants.*.tax_id' => 'required|exists:taxes,id',
            ],
            [],
            [
                'customer_id' => 'cliente',
                'warehouse_id' => 'almacén',
                'observation' => 'observación',
                'variants.*.id' => 'producto',
                'variants.*.quantity' => 'cantidad',
                'variants.*.price' => 'precio',
                'variants.*.tax_id' => 'impuesto',
            ]
        );

        $computedTotal = 0;
        foreach ($this->variants as $variant) {
            $tax = Tax::find($variant['tax_id']);
            $rate = (float) optional($tax)->rate_percent ?? 0;
            $inclusive = (bool) optional($tax)->is_price_inclusive ?? false;
            $subLine = (float)($variant['quantity'] ?? 0) * (float)($variant['price'] ?? 0);
            $base = ($inclusive && $rate > 0) ? ($subLine / (1 + ($rate / 100))) : $subLine;
            $computedTotal += $base * (1 + ($rate / 100));
        }
        $this->total = $computedTotal;

        $this->sale->update([
            'serie' => $this->serie,
            'correlative' => $this->correlative,
            'date' => $this->date ?? now(),
            'customer_id' => $this->customer_id,
            'warehouse_id' => $this->warehouse_id,
            'total' => $this->total,
            'observation' => $this->observation,
        ]);

        $syncData = [];
        foreach ($this->variants as $variant) {
            $tax = Tax::find($variant['tax_id']);
            $rate = (float) optional($tax)->rate_percent ?? 0;
            $inclusive = (bool) optional($tax)->is_price_inclusive ?? false;
            $lineSubtotal = (float)($variant['quantity'] ?? 0) * (float)($variant['price'] ?? 0);
            $base = ($inclusive && $rate > 0) ? ($lineSubtotal / (1 + ($rate / 100))) : $lineSubtotal;

            $syncData[$variant['id']] = [
                'quantity' => $variant['quantity'],
                'price' => $variant['price'],
                'tax_rate' => $rate,
                'subtotal' => $base,
            ];
        }
        $this->sale->variants()->sync($syncData);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'La venta fue actualizada exitosamente.',
        ]);

        return redirect()->route('admin.sales.index');
    }

    /**
     * Reabrir venta a borrador desde published/cancelled.
     */
    public function reopen()
    {
        // Solo permitir reapertura desde 'posted' y con estados SUNAT permitidos
        if ($this->sale->status !== 'posted') {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'No permitido',
                'text' => 'Solo ventas publicadas pueden reabrirse a borrador.',
            ]);
            return;
        }

        $blockedSunat = ['accepted', 'queued', 'processing', 'cancelled', 'sent', 'observed'];
        if (in_array((string) $this->sale->sunat_status, $blockedSunat, true)) {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'Reapertura bloqueada',
                'text' => 'No se puede reabrir en el estado SUNAT actual.',
            ]);
            return;
        }

        $this->sale->update(['status' => 'draft']);
        $this->status = 'draft';

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Venta reabierta',
            'text' => 'La venta volvió a estado borrador.',
        ]);
    }

    /**
     * Marcar pago como completo.
     */
    public function markPaid()
    {
        // Permitir pago solo si está publicada
        if ($this->sale->status !== 'posted') {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Acción no válida',
                'text' => 'Solo se puede registrar pago para ventas publicadas.',
            ]);
            return;
        }
        if ($this->sale->payment_status === 'paid') {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'Ya está pagada',
                'text' => 'La venta ya está marcada como pagada.',
            ]);
            return;
        }

        $this->sale->update(['payment_status' => 'paid']);
        $this->payment_status = 'paid';

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Pago registrado',
            'text' => 'La venta quedó como pagada.',
        ]);
    }

    /**
     * Marcar venta como no pagada.
     */
    public function markUnpaid()
    {
        if ($this->sale->status === 'cancelled') {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'Documento anulado',
                'text' => 'No se puede cambiar el estado de pago.',
            ]);
            return;
        }
        $this->sale->update(['payment_status' => 'unpaid']);
        $this->payment_status = 'unpaid';

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Pago desmarcado',
            'text' => 'La venta quedó como no pagada.',
        ]);
    }

    /**
     * Contabilizar la venta: pasa de borrador a publicada.
     */
    public function post()
    {
        if ($this->sale->status !== 'draft') {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'No permitido',
                'text' => 'Solo ventas en borrador pueden contabilizarse.',
            ]);
            return;
        }

        // Recalcular y guardar cambios base
        $this->save();

        // Cambiar estado a publicada
        $this->sale->update(['status' => 'posted']);
        $this->status = 'posted';

        foreach ($this->variants as $variant) {
            Kardex::registerExit($this->sale, $variant, $this->warehouse_id, 'Venta');
        }

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Venta contabilizada',
            'text' => 'La venta fue publicada y se registró la salida en Kardex.',
        ]);
    }

    /**
     * Cancelar la venta.
     */
    public function cancel()
    {
        if ($this->sale->status === 'cancelled') {
            return;
        }

        // Bloquear si tiene pagos (parcial o completo)
        if (in_array((string) $this->sale->payment_status, ['partial', 'paid'], true)) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No permitido',
                'text' => 'La venta tiene pagos registrados. Anule los pagos antes de cancelar.',
            ]);
            return;
        }

        // Bloquear según estado SUNAT (cuando está publicada)
        $blockedSunat = ['accepted', 'queued', 'processing', 'cancelled', 'sent', 'observed'];
        if ($this->sale->status === 'posted' && in_array((string) $this->sale->sunat_status, $blockedSunat, true)) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Cancelación bloqueada',
                'text' => 'No se puede cancelar en el estado SUNAT actual.',
            ]);
            return;
        }

        // Revertir inventario si estaba publicada
        if ($this->sale->status === 'posted') {
            foreach ($this->variants as $variant) {
                Kardex::registerEntry($this->sale, $variant, $this->warehouse_id, 'Anulación de venta');
            }
        }

        $this->sale->update(['status' => 'cancelled']);
        $this->status = 'cancelled';

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Venta cancelada',
            'text' => 'Se anuló la venta exitosamente.',
        ]);
    }

    // ===== Modal de envío de correo =====
    public function openModal(Sale $sale)
    {
        $customer = $sale->customer;
        $this->form['open'] = true;
        $this->form['document'] = 'Venta ' . ' ' . ($sale->serie ?? '') . ' ' . ($sale->correlative ?? '');
        $this->form['client'] = optional($customer)->document_number . ' - ' . optional($customer)->name;
        $this->form['email'] = optional($customer)->email;
        $this->form['model'] = $sale;
    }

    public function sendEmail()
    {
        $this->validate([
            'form.email' => 'required|email',
        ]);

        Mail::to($this->form['email'])
            ->send(new PdfSend($this->form));

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'El email ha sido enviado correctamente',
        ]);
        $this->reset('form');
    }

    /**
     * Enviar manualmente la venta a SUNAT.
     */
    public function sendSunat()
    {
        try {
            // Evitar reenvío si ya está aceptado
            if ($this->sale->sunat_status === 'accepted') {
                $this->dispatch('swal', [
                    'icon' => 'info',
                    'title' => 'Ya aceptada por SUNAT',
                    'text' => 'La venta ya fue aceptada. No se reenviará.',
                ]);
                return;
            }
            // Evitar duplicar si está en curso
            if (in_array($this->sale->sunat_status, ['processing', 'queued'])) {
                $this->dispatch('swal', [
                    'icon' => 'info',
                    'title' => 'Envío en curso',
                    'text' => 'Ya hay un envío en curso a SUNAT. Espere el resultado.',
                ]);
                return;
            }
            // Marcar como en cola y despachar job
            $this->sale->sunat_status = 'queued';
            $this->sale->save();

            SendSunatInvoice::dispatch($this->sale->id)->afterCommit();

            $this->sunat_status = 'queued';
            $this->dispatch('swal', [
                'icon' => 'success',
                'title' => 'Envío iniciado',
                'text' => 'Se programó el envío a SUNAT. Verifique el estado en unos segundos.',
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No se pudo enviar',
                'text' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Abrir modal para Nota de Crédito (07).
     */
    public function openCreditNoteModal()
    {
        $this->noteForm = [
            'open' => true,
            'type' => 'credit',
            'title' => 'Nota de Crédito (07)',
            'reason_code' => '01',
            'reason_label' => 'ANULACION DE LA OPERACION',
            'mode' => 'total',
            'observation' => 'Generada desde venta ' . $this->sale->serie . '-' . $this->sale->correlative,
        ];
    }

    /**
     * Abrir modal para Nota de Débito (08).
     */
    public function openDebitNoteModal()
    {
        $this->noteForm = [
            'open' => true,
            'type' => 'debit',
            'title' => 'Nota de Débito (08)',
            'reason_code' => '02',
            'reason_label' => 'AUMENTO EN EL VALOR',
            'mode' => 'total',
            'observation' => 'Generada desde venta ' . $this->sale->serie . '-' . $this->sale->correlative,
        ];
    }

    /**
     * Crear Nota de Crédito (07) y enviar con payload estático.
     */
    public function sendStaticCreditNote()
    {
        $this->sendStaticNote('07');
    }

    /**
     * Crear Nota de Débito (08) y enviar con payload estático.
     */
    public function sendStaticDebitNote()
    {
        $this->sendStaticNote('08');
    }

    /**
     * Helper para crear la venta de nota y enviar a SUNAT con payload estático.
     */
    protected function sendStaticNote(string $docType)
    {
        try {
            $companyId = (int) ($this->sale->company_id ?? 0);
            // Determinar tipo de documento afectado (01 factura, 03 boleta)
            $affectedType = (string) (optional($this->sale->journal)->document_type_code ?? '');
            if (! in_array($affectedType, ['01', '03'], true)) {
                $serieGuess = (string) ($this->sale->serie ?? '');
                $affectedType = str_starts_with($serieGuess, 'F') ? '01' : (str_starts_with($serieGuess, 'B') ? '03' : '01');
            }

            $journal = $this->findJournalForType($companyId, $docType, $affectedType);
            if (! $journal) {
                $this->dispatch('swal', [
                    'icon' => 'error',
                    'title' => 'Journal no encontrado',
                    'text' => 'Configure un diario fiscal de venta para el tipo ' . $docType . ' en la compañía.',
                ]);
                return;
            }

            // Obtener serie y correlativo
            $parts = \App\Services\SequenceService::getNextParts($journal->id);

            // Construir nueva venta (nota)
            $newSale = \App\Models\Sale::create([
                'serie' => $parts['serie'],
                'correlative' => $parts['correlative'],
                'date' => now(),
                'quote_id' => null,
                'customer_id' => $this->sale->customer_id,
                'warehouse_id' => $this->sale->warehouse_id,
                'total' => $this->sale->total,
                'observation' => 'Generada desde venta ' . $this->sale->serie . '-' . $this->sale->correlative,
                'company_id' => $companyId,
                'journal_id' => $journal->id,
                // SUNAT referencia a documento afectado
                'original_sale_id' => $this->sale->id,
                'original_document_type_code' => (string) optional($this->sale->journal)->document_type_code,
                'original_serie' => (string) $this->sale->serie,
                'original_correlative' => (string) $this->sale->correlative,
            ]);

            // Adjuntar variantes replicando cantidades y precios
            $syncData = [];
            foreach ($this->variants as $variant) {
                $syncData[$variant['id']] = [
                    'quantity' => $variant['quantity'],
                    'price' => $variant['price'],
                    'subtotal' => $variant['quantity'] * $variant['price'],
                ];
            }
            $newSale->variants()->sync($syncData);

            // Movimiento de inventario para NC (devolución)
            if ($docType === '07') {
                foreach ($this->variants as $variant) {
                    Kardex::registerEntry($newSale, $variant, $this->warehouse_id, 'Devolución por Nota de Crédito');
                }
            }

            // Enviar a SUNAT con payload estático
            $svc = new GreenterInvoiceService();
            $ok = $svc->sendInvoiceFromSale($newSale);

            $this->dispatch('swal', [
                'icon' => $ok ? 'success' : 'warning',
                'title' => $ok ? 'Nota enviada a SUNAT' : 'Envío realizado con observaciones',
                'text' => $ok ? 'Se envió la nota con datos estáticos.' : 'Revise el estado de SUNAT en la nueva nota.',
            ]);

            // Ir a la nueva venta/nota
            return redirect()->route('admin.sales.edit', $newSale->id);
        } catch (\Throwable $e) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No se pudo enviar la nota',
                'text' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Abrir modal para Nota de Venta (no fiscal).
     */
    public function openSalesNoteModal()
    {
        $this->noteForm = [
            'open' => true,
            'type' => 'sales_note',
            'title' => 'Nota de Venta (No fiscal)',
            'reason_code' => null,
            'reason_label' => null,
            'mode' => 'total',
            'observation' => 'Generada desde venta ' . $this->sale->serie . '-' . $this->sale->correlative,
        ];
    }

    public function closeNoteModal()
    {
        $this->noteForm['open'] = false;
    }

    /**
     * Confirmar creación de la nota según tipo seleccionado.
     * - NC (07): crea venta con journal fiscal 07, referencia documento original y registra ENTRADA en inventario.
     * - ND (08): crea venta con journal fiscal 08, referencia documento original (sin movimiento de inventario).
     * - NV: crea venta con journal no fiscal (sin movimiento de inventario).
     */
    public function confirmCreateNote()
    {
        try {
            $type = (string) ($this->noteForm['type'] ?? '');
            if (! in_array($type, ['credit', 'debit', 'sales_note'], true)) {
                $this->dispatch('swal', [
                    'icon' => 'warning',
                    'title' => 'Tipo inválido',
                    'text' => 'Seleccione un tipo de nota válido.',
                ]);
                return;
            }

            // Seleccionar Journal adecuado por tipo
            $companyId = (int) ($this->sale->company_id ?? 0);
            $targetDocType = $type === 'credit' ? '07' : ($type === 'debit' ? '08' : null);
            // Determinar tipo afectado para seleccionar el prefijo de serie correcto
            $affectedType = (string) (optional($this->sale->journal)->document_type_code ?? '');
            if (! in_array($affectedType, ['01', '03'], true)) {
                $serieGuess = (string) ($this->sale->serie ?? '');
                $affectedType = str_starts_with($serieGuess, 'F') ? '01' : (str_starts_with($serieGuess, 'B') ? '03' : '01');
            }
            $journal = $this->findJournalForType($companyId, $targetDocType, $affectedType);
            if (! $journal) {
                $this->dispatch('swal', [
                    'icon' => 'error',
                    'title' => 'Journal no encontrado',
                    'text' => $targetDocType
                        ? 'Configure un diario fiscal de venta para el tipo ' . $targetDocType . ' en la compañía.'
                        : 'Configure un diario de venta NO fiscal para notas de venta en la compañía.',
                ]);
                return;
            }

            // Obtener serie y correlativo
            $parts = \App\Services\SequenceService::getNextParts($journal->id);

            // Construir nueva venta (nota)
            $newSale = Sale::create([
                'serie' => $parts['serie'],
                'correlative' => $parts['correlative'],
                'date' => now(),
                'quote_id' => null,
                'customer_id' => $this->sale->customer_id,
                'warehouse_id' => $this->sale->warehouse_id,
                'total' => $this->sale->total,
                'observation' => $this->noteForm['observation'] ?? null,
                'company_id' => $companyId,
                'journal_id' => $journal->id,
                // SUNAT referencia a documento afectado (solo NC/ND)
                'original_sale_id' => in_array($type, ['credit', 'debit'], true) ? $this->sale->id : null,
                'original_document_type_code' => in_array($type, ['credit', 'debit'], true)
                    ? (string) optional($this->sale->journal)->document_type_code
                    : null,
                'original_serie' => in_array($type, ['credit', 'debit'], true) ? (string) $this->sale->serie : null,
                'original_correlative' => in_array($type, ['credit', 'debit'], true) ? (string) $this->sale->correlative : null,
            ]);

            // Adjuntar variantes replicando cantidades y precios
            $syncData = [];
            foreach ($this->variants as $variant) {
                $syncData[$variant['id']] = [
                    'quantity' => $variant['quantity'],
                    'price' => $variant['price'],
                    'subtotal' => $variant['quantity'] * $variant['price'],
                ];
            }
            $newSale->variants()->sync($syncData);

            // Movimiento de inventario
            if ($type === 'credit') {
                // Devolución: ENTRADA al inventario
                foreach ($this->variants as $variant) {
                    Kardex::registerEntry($newSale, $variant, $this->warehouse_id, 'Devolución por Nota de Crédito');
                }
            }
            // ND y NV: sin movimientos por defecto (ajusta valor / documento no fiscal)

            $this->dispatch('swal', [
                'icon' => 'success',
                'title' => 'Nota creada',
                'text' => 'Se creó la ' . ($this->noteForm['title'] ?? 'nota') . ' correctamente.',
            ]);
            $this->noteForm['open'] = false;

            // Redirigir a edición de la nueva venta/nota
            return redirect()->route('admin.sales.edit', $newSale->id);
        } catch (\Throwable $e) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No se pudo crear la nota',
                'text' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Seleccionar el Journal según tipo de documento objetivo.
     * - '07' / '08': fiscal, type 'sale', company igual a la venta.
     * - null: no fiscal (document_type_code null), type 'sale'.
     */
    protected function findJournalForType(int $companyId, ?string $docType, ?string $affectedType = null)
    {
        $query = \App\Models\Journal::query()
            ->where('company_id', $companyId)
            ->where('type', 'sale');
        if ($docType) {
            $query->where('document_type_code', $docType)->where('is_fiscal', true);
            // Para NC/ND, elegir prefijo de serie según afectado: FC/BC o FD/BD
            if (in_array($docType, ['07', '08'], true) && in_array($affectedType, ['01', '03'], true)) {
                $prefix = ($docType === '07')
                    ? ($affectedType === '01' ? 'FC' : 'BC')
                    : ($affectedType === '01' ? 'FD' : 'BD');
                $query->where('code', 'like', $prefix . '%');
            }
        } else {
            // Nota de Venta: diario de venta no fiscal sin tipo SUNAT
            $query->where(function ($q) {
                $q->whereNull('document_type_code')->orWhere('document_type_code', '');
            })->where('is_fiscal', false);
        }
        return $query->orderBy('id')->first();
    }

    public function render()
    {
        return view('livewire.admin.sales.sale-edit');
    }

    /**
     * Actualizar datos desde BD periódicamente para reflejar estado SUNAT.
     */
    public function refreshSale()
    {
        $this->sale->refresh();
        $this->sunat_status = $this->sale->sunat_status;
    }
}
