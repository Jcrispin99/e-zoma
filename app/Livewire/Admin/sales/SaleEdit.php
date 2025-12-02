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
use App\Models\SunatConnection;

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
    public $hasSunatConnection = false;
    public $hasReturnChildren = false;
    public $creatingReturn = false;

    // Propiedades para el modal de envío de correo
    public $form = [
        'open' => false,
        'document' => '',
        'client' => '',
        'email' => '',
        'model' => null,
        'view_pdf_patch' => 'admin.sales.pdf',
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

        // Detectar si ya existen documentos derivados (devoluciones/nota) desde esta venta
        $this->hasReturnChildren = $sale->derivedSales()->exists();

        // Determinar si existe conexión SUNAT válida para la compañía
        $this->hasSunatConnection = SunatConnection::query()
            ->where('company_id', (int) ($sale->company_id ?? 0))
            ->whereNotNull('token_ikoodev')
            ->where('token_ikoodev', '!=', '')
            ->exists();

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
        // Si es borrador y NO fiscal (Nota de Venta), contabilizar primero
        if ($this->sale->status === 'draft') {
            $docType = (string) optional($this->sale->journal)->document_type_code;
            $isFiscal = (bool) optional($this->sale->journal)->is_fiscal;
            $isNonFiscalNv = (!$isFiscal) && ($docType === '' || $docType === null);
            if ($isNonFiscalNv) {
                // Contabiliza (registrará entrada/salida en Kardex según devolución o venta)
                $this->post();
                // Refrescar estado local
                $this->sale->refresh();
                $this->status = (string) $this->sale->status;
            } else {
                // Bloquear pago para borrador fiscal o tipos no permitidos
                $this->dispatch('swal', [
                    'icon' => 'error',
                    'title' => 'Acción no válida',
                    'text' => 'Solo se puede registrar pago en borrador para Notas de Venta (no fiscal).',
                ]);
                return;
            }
        }
        // A partir de aquí debe estar publicada
        if ($this->sale->status !== 'posted') {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Acción no válida',
                'text' => 'La venta debe estar publicada para registrar pago.',
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

        // Movimiento de inventario según tipo de documento
        $docType = (string) optional($this->sale->journal)->document_type_code;
        $isFiscal = (bool) optional($this->sale->journal)->is_fiscal;

        if (in_array($docType, ['01', '03'], true)) {
            // Factura/Boleta: salida de inventario
            foreach ($this->variants as $variant) {
                Kardex::registerExit($this->sale, $variant, $this->warehouse_id, 'Venta');
            }
        } elseif ($docType === '07') {
            // Nota de Crédito: entrada por devolución
            foreach ($this->variants as $variant) {
                Kardex::registerEntry($this->sale, $variant, $this->warehouse_id, 'Devolución por Nota de Crédito');
            }
        } elseif ($docType === '08') {
            // Nota de Débito: sin movimiento por defecto
        } else {
            // No fiscal (Nota de Venta u otros):
            // Si es devolución parcial (tiene venta original), registrar entrada; caso contrario, salida.
            $isReturn = !empty($this->sale->original_sale_id);
            foreach ($this->variants as $variant) {
                if ($isReturn) {
                    Kardex::registerEntry($this->sale, $variant, $this->warehouse_id, 'Devolución interna');
                } else {
                    Kardex::registerExit($this->sale, $variant, $this->warehouse_id, 'Venta (no fiscal)');
                }
            }
        }

        // Autoenvío a SUNAT para documentos fiscales (01, 03, 07, 08)
        try {
            if ($isFiscal && in_array($docType, ['01', '03', '07', '08'], true)) {
                // Evitar duplicados si ya aceptado o en curso
                $currentSunat = (string) ($this->sale->sunat_status ?? '');
                if (! in_array($currentSunat, ['accepted', 'queued', 'processing'], true)) {
                    if ($this->hasSunatConnection) {
                        // Marcar en cola y despachar el job
                        $this->sale->sunat_status = 'queued';
                        $this->sale->save();
                        SendSunatInvoice::dispatch($this->sale->id)->afterCommit();
                        $this->sunat_status = 'queued';
                    } else {
                        // Sin conexión SUNAT: dejar como pendiente para envío manual
                        $this->sale->sunat_status = $this->sale->sunat_status ?: 'pending';
                        $this->sale->save();
                        $this->sunat_status = $this->sale->sunat_status;
                    }
                }
            }
        } catch (\Throwable $e) {
            // No bloquear contabilización si falla el autoenvío
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

    /**
     * Devolver productos sin SUNAT (flujo interno total: usa cancelación).
     */
    public function internalReturn()
    {
        if ($this->sale->status !== 'posted') {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'No permitido',
                'text' => 'Solo ventas publicadas pueden devolver productos.',
            ]);
            return;
        }

        if ($this->hasSunatConnection) {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'SUNAT activo',
                'text' => 'Con SUNAT activo, use Nota de Crédito/Débito.',
            ]);
            return;
        }

        // Reutiliza la lógica de cancelación para reversar inventario
        $this->cancel();
    }

    /**
     * Iniciar devolución parcial creando un borrador NO fiscal (Nota de Venta).
     * Prefill de líneas con cantidad 0 para edición segura.
     */
    public function startReturnDraft()
    {
        if ($this->sale->status !== 'posted') {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'No permitido',
                'text' => 'Solo ventas publicadas pueden iniciar una devolución.',
            ]);
            return;
        }

        // Evitar doble clic/disparos concurrentes
        if ($this->creatingReturn) {
            return;
        }
        $this->creatingReturn = true;

        // Bloqueo general: si ya existe cualquier documento derivado, no permitir otra devolución
        if (Sale::query()->where('original_sale_id', $this->sale->id)->exists()) {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'Devolución ya registrada',
                'text' => 'Ya existe un documento de devolución asociado a esta venta.',
            ]);
            $this->creatingReturn = false;
            return;
        }

        // Nota: Para boletas se mantiene la política de única devolución; el bloqueo anterior ya cubre todos los tipos

        try {
            $companyId = (int) ($this->sale->company_id ?? 0);
            $journal = $this->findJournalForType($companyId, null);
            if (! $journal) {
                $this->dispatch('swal', [
                    'icon' => 'error',
                    'title' => 'Diario no fiscal faltante',
                    'text' => 'Configure una Nota de Venta (diario no fiscal) para esta compañía.',
                ]);
                return;
            }

            // Siguiente serie y correlativo para NV
            $parts = \App\Services\SequenceService::getNextParts($journal->id);

            // Crear venta borrador enlazada a la original
            $newSale = \App\Models\Sale::create([
                'serie' => $parts['serie'],
                'correlative' => $parts['correlative'],
                'date' => now(),
                'quote_id' => null,
                'customer_id' => $this->sale->customer_id,
                'warehouse_id' => $this->sale->warehouse_id,
                'total' => 0,
                'observation' => 'Borrador de devolución parcial de ' . $this->sale->serie . '-' . $this->sale->correlative,
                'company_id' => $companyId,
                'journal_id' => $journal->id,
                'status' => 'draft',
                'sunat_status' => 'skipped',
                // referencia
                'original_sale_id' => $this->sale->id,
                'original_document_type_code' => (string) optional($this->sale->journal)->document_type_code,
                'original_serie' => (string) $this->sale->serie,
                'original_correlative' => (string) $this->sale->correlative,
            ]);

            // Prefill de líneas copiando cantidades originales para edición rápida
            $syncData = [];
            foreach ($this->variants as $variant) {
                $rate = (float) ($variant['tax_rate'] ?? 0);
                $syncData[$variant['id']] = [
                    'quantity' => (int) ($variant['quantity'] ?? 0),
                    'price' => (float) $variant['price'],
                    'tax_rate' => $rate,
                    'subtotal' => ((float) ($variant['quantity'] ?? 0)) * ((float) $variant['price']),
                ];
            }
            $newSale->variants()->sync($syncData);

            // Registrar referencia del nuevo documento en observaciones de la venta original
            try {
                $ref = trim((string) $newSale->serie . '-' . (string) $newSale->correlative);
                $label = 'Devolución creada: NV ' . $ref;
                $prev = trim((string) ($this->sale->observation ?? ''));
                $newObs = trim($prev !== '' ? ($prev . ' | ' . $label) : $label);
                // Limitar a 255 caracteres para mantener consistencia con validación
                if (function_exists('mb_substr')) {
                    $newObs = mb_substr($newObs, 0, 255);
                } else {
                    $newObs = substr($newObs, 0, 255);
                }
                $this->sale->update(['observation' => $newObs]);
            } catch (\Throwable $e) {
                // Silencioso: no bloquear por fallo al escribir observación
            }

            $this->dispatch('swal', [
                'icon' => 'success',
                'title' => 'Borrador creado',
                'text' => 'Ajusta cantidades a devolver y contabiliza cuando esté listo.',
            ]);

            return redirect()->route('admin.sales.edit', $newSale->id);
        } catch (\Throwable $e) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No se pudo crear el borrador',
                'text' => 'Error: ' . $e->getMessage(),
            ]);
            $this->creatingReturn = false;
        }
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
            // Bloquear si no hay conexión SUNAT configurada
            if (! $this->hasSunatConnection) {
                $this->dispatch('swal', [
                    'icon' => 'error',
                    'title' => 'SUNAT no disponible',
                    'text' => 'Configura el token de SUNAT en Conexión antes de enviar.',
                ]);
                return;
            }
            // Bloquear si el documento base no es fiscal (01/03)
            $docType = (string) optional($this->sale->journal)->document_type_code;
            $isFiscalBase = (bool) optional($this->sale->journal)->is_fiscal;
            if (! $isFiscalBase || ! in_array($docType, ['01', '03'], true)) {
                $this->dispatch('swal', [
                    'icon' => 'info',
                    'title' => 'Documento no fiscal',
                    'text' => 'Esta venta no es factura/boleta fiscal; no se envía a SUNAT.',
                ]);
                return;
            }
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
            // La venta base debe ser fiscal (journal is_fiscal) y con documento 01/03
            $isFiscalBase = (bool) optional($this->sale->journal)->is_fiscal;
            if (! $isFiscalBase || ! in_array($affectedType, ['01', '03'], true)) {
                $this->dispatch('swal', [
                    'icon' => 'error',
                    'title' => 'Venta base no fiscal',
                    'text' => 'Solo se pueden iniciar notas desde facturas o boletas fiscales.',
                ]);
                return;
            }

            // Restringir: boleta solo una devolución (NC/ND o NV), si ya existe hijo bloquear
            if ($affectedType === '03') {
                $alreadyHasReturn = Sale::query()->where('original_sale_id', $this->sale->id)->exists();
                if ($alreadyHasReturn) {
                    $this->dispatch('swal', [
                        'icon' => 'info',
                        'title' => 'Devolución ya registrada',
                        'text' => 'Las boletas permiten una sola devolución. Ya existe un documento asociado.',
                    ]);
                    return;
                }
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

            // Construir nueva venta (nota) en borrador
            $newSale = \App\Models\Sale::create([
                'serie' => $parts['serie'],
                'correlative' => $parts['correlative'],
                'date' => now(),
                'quote_id' => null,
                'customer_id' => $this->sale->customer_id,
                'warehouse_id' => $this->sale->warehouse_id,
                'total' => 0,
                'observation' => 'Borrador de Nota ' . $docType . ' desde ' . $this->sale->serie . '-' . $this->sale->correlative,
                'company_id' => $companyId,
                'journal_id' => $journal->id,
                'status' => 'draft',
                'sunat_status' => 'pending',
                // SUNAT referencia a documento afectado
                'original_sale_id' => $this->sale->id,
                'original_document_type_code' => (string) optional($this->sale->journal)->document_type_code,
                'original_serie' => (string) $this->sale->serie,
                'original_correlative' => (string) $this->sale->correlative,
            ]);

            // Prefill de líneas copiando cantidades originales para edición rápida
            $syncData = [];
            foreach ($this->variants as $variant) {
                $syncData[$variant['id']] = [
                    'quantity' => (int) ($variant['quantity'] ?? 0),
                    'price' => (float) $variant['price'],
                    'tax_rate' => (float) ($variant['tax_rate'] ?? 0),
                    'subtotal' => ((float) ($variant['quantity'] ?? 0)) * ((float) $variant['price']),
                ];
            }
            $newSale->variants()->sync($syncData);

            // Registrar referencia del nuevo documento en observaciones de la venta original
            try {
                $ref = trim((string) $newSale->serie . '-' . (string) $newSale->correlative);
                $prefix = $docType === '07' ? 'NC' : ($docType === '08' ? 'ND' : 'Doc');
                $label = 'Devolución creada: ' . $prefix . ' ' . $ref;
                $prev = trim((string) ($this->sale->observation ?? ''));
                $newObs = trim($prev !== '' ? ($prev . ' | ' . $label) : $label);
                if (function_exists('mb_substr')) {
                    $newObs = mb_substr($newObs, 0, 255);
                } else {
                    $newObs = substr($newObs, 0, 255);
                }
                $this->sale->update(['observation' => $newObs]);
            } catch (\Throwable $e) {
                // Silencioso
            }

            // No mover inventario ni enviar automáticamente.
            // El movimiento se realizará al contabilizar (post), y el envío será manual.
            $this->dispatch('swal', [
                'icon' => 'success',
                'title' => 'Nota creada en borrador',
                'text' => 'Ajusta cantidades y contabiliza; luego podrás enviar a SUNAT.',
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
