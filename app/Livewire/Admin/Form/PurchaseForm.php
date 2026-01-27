<?php

namespace App\Livewire\Admin\Form;

use App\Facades\Kardex;
use App\Mail\PdfSend;
use App\Models\Journal;
use App\Models\Purchase;
use App\Models\Tax;
use App\Models\Variant;
use App\Models\Warehouse;
use App\Services\SequenceService;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class PurchaseForm extends Component
{
    public $mode = 'create';
    public ?Purchase $purchase = null;

    // Journals and preview
    public $journalOptions = [];
    public $journal_id;
    public $correlative;

    // Core fields
    public $date;
    public $warehouse_id;
    public $supplier_id;
    public $total = 0;
    public $observation;

    // Items
    public $variant_id;
    public $variants = [];
    public $taxes = [];
    public $default_tax_id = null;

    // Status
    public $status;
    public $payment_status;

    // Vendor bill (factura del proveedor)
    public $vendor_bill_number;
    public $vendor_bill_date;

    // Email modal
    public $form = [
        'open' => false,
        'document' => '',
        'client' => '',
        'email' => '',
        'model' => null,
        'view_pdf_patch' => 'admin.purchases.pdf',
    ];

    public function boot()
    {
        $this->withValidator(function ($validator) {
            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                $html = "<ul class='text-left'>";
                foreach ($errors as $error) {
                    $html .= "<li>{$error[0]}</li>";
                }
                $html .= "</ul>";
                $this->dispatch('swal', [
                    'icon' => 'error',
                    'title' => 'Error de validación',
                    'html' => $html,
                ]);
            }
        });
    }

    public function mount(?Purchase $purchase = null, $mode = 'create')
    {
        $this->mode = $mode ?? ($purchase ? 'edit' : 'create');
        $this->purchase = $purchase;

        // Taxes
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

        // Journals
        $journals = Journal::where('type', 'purchase')->with('sequence')->orderBy('name')->get();
        $this->journalOptions = $journals->map(fn ($j) => [
            'id' => $j->id,
            'label' => sprintf('%s (%s)', $j->name, $j->code),
        ])->toArray();

        if ($this->mode === 'edit' && $this->purchase) {
            $p = $this->purchase->load('variants.product', 'supplier', 'warehouse');
            $this->journal_id = $p->journal_id;
            $this->correlative = $p->correlative;
            $this->date = optional($p->date)->format('Y-m-d');
            $this->supplier_id = $p->supplier_id;
            $this->warehouse_id = $p->warehouse_id;
            $this->observation = $p->observation;
            $this->status = $p->status;
            $this->payment_status = $p->payment_status;
            $this->vendor_bill_number = $p->vendor_bill_number;
            $this->vendor_bill_date = optional($p->vendor_bill_date)->format('Y-m-d');

            $taxesCol = collect($this->taxes);
            $this->variants = $p->variants->map(function ($variant) use ($taxesCol) {
                $pivotRate = (float) ($variant->pivot->tax_rate ?? 0);
                $matched = $taxesCol->firstWhere('rate_percent', $pivotRate) ?? $taxesCol->first();
                return [
                    'id' => $variant->id,
                    'name' => $variant->fullName,
                    'quantity' => $variant->pivot->quantity,
                    'price' => $variant->pivot->price,
                    'tax_id' => $matched['id'] ?? null,
                    'tax_rate' => $matched['rate_percent'] ?? 0,
                    'tax_inclusive' => (bool) ($matched['is_price_inclusive'] ?? false),
                    'subtotal' => $variant->pivot->subtotal,
                ];
            })->toArray();

            $this->total = $p->total;
        } else {
            // create defaults
            $this->date = now()->format('Y-m-d');
            $first = $journals->first();
            $this->journal_id = $first?->id;
            $this->updatePreview();

            if (!$this->warehouse_id) {
                $activeCompanyId = session('active_company_id');
                if ($activeCompanyId) {
                    $firstWarehouse = Warehouse::where('company_id', $activeCompanyId)
                        ->orderBy('id', 'asc')
                        ->first();
                    $this->warehouse_id = $firstWarehouse?->id;
                }
            }
        }
    }

    public function updatedJournalId()
    {
        $this->updatePreview();
    }

    protected function updatePreview(): void
    {
        if (!$this->journal_id) {
            $this->correlative = '';
            return;
        }
        $journal = Journal::with('sequence')->find($this->journal_id);
        $sequence = $journal?->sequence;
        $this->correlative = $sequence ? str_pad($sequence->next_number, $sequence->sequence_size, '0', STR_PAD_LEFT) : '';
    }

    public function addProduct()
    {
        $this->validate([
            'variant_id' => 'required|exists:variants,id',
            'warehouse_id' => 'required|exists:warehouses,id',
        ], [], [
            'variant_id' => 'producto',
            'warehouse_id' => 'almacén',
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
        $lastRecord = Kardex::getLastRecord($variant->id, $this->warehouse_id);

        $defaultTax = collect($this->taxes)->firstWhere('id', $this->default_tax_id) ?? collect($this->taxes)->first();
        $rate = (float) ($defaultTax['rate_percent'] ?? 0);
        $inclusive = (bool) ($defaultTax['is_price_inclusive'] ?? false);
        $qty = 1;
        $price = (float) $lastRecord['cost'];
        $lineTotal = $qty * $price;
        $baseSubtotal = ($inclusive && $rate > 0) ? ($lineTotal / (1 + ($rate / 100))) : $lineTotal;

        $this->variants[] = [
            'id' => $variant->id,
            'name' => $variant->fullName,
            'quantity' => $qty,
            'price' => $price,
            'tax_id' => $defaultTax['id'] ?? null,
            'tax_rate' => $rate,
            'tax_inclusive' => $inclusive,
            'subtotal' => $baseSubtotal,
        ];
        $this->reset('variant_id');
    }

    public function scanBarcode($code = null)
    {
        $code = trim($code ?? '');
        if ($code === '') {
            return;
        }

        $variant = Variant::where('barcode', $code)
            ->orWhere('sku', $code)
            ->first();

        if (!$variant) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Código no encontrado',
                'text' => 'No se encontró ningún producto para ese código o SKU.',
            ]);
            return;
        }

        foreach ($this->variants as $index => $row) {
            if (($row['id'] ?? null) === $variant->id) {
                $current = (int) ($this->variants[$index]['quantity'] ?? 0);
                $this->variants[$index]['quantity'] = $current + 1;
                if (array_key_exists('subtotal', $this->variants[$index])) {
                    $price = (float) ($this->variants[$index]['price'] ?? 0);
                    $qty = (int) ($this->variants[$index]['quantity'] ?? 0);
                    $rate = (float) ($this->variants[$index]['tax_rate'] ?? 0);
                    $inclusive = (bool) ($this->variants[$index]['tax_inclusive'] ?? false);
                    $lineTotal = $price * $qty;
                    $this->variants[$index]['subtotal'] = ($inclusive && $rate > 0)
                        ? ($lineTotal / (1 + ($rate / 100)))
                        : $lineTotal;
                }
                return;
            }
        }

        $this->variant_id = $variant->id;
        $this->addProduct();
        $this->reset('variant_id');
    }

    public function save(bool $doRedirect = true)
    {
        if ($this->mode === 'edit' && $this->purchase && $this->purchase->status !== 'draft') {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'No permitido',
                'text' => 'Solo compras en borrador pueden editarse.',
            ]);
            return;
        }
        $rules = [
            'date' => 'nullable|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'total' => 'required|numeric|min:0',
            'observation' => 'nullable|string|max:255',
            'variants' => 'required|array|min:1',
            'variants.*.id' => 'required|exists:variants,id',
            'variants.*.quantity' => 'required|numeric|min:1',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.tax_id' => 'required|exists:taxes,id',
        ];

        if ($this->mode === 'create') {
            $rules = array_merge($rules, [
                'journal_id' => 'required|exists:journals,id',
            ]);
        }

        $this->validate($rules, [], [
            'journal_id' => 'serie',
            'supplier_id' => 'proveedor',
            'warehouse_id' => 'almacén',
            'observation' => 'observación',
            'variants.*.id' => 'producto',
            'variants.*.quantity' => 'cantidad',
            'variants.*.price' => 'precio',
            'variants.*.tax_id' => 'IGV',
        ]);

        // Compute total
        $computedTotal = 0;
        foreach ($this->variants as $variant) {
            $tax = Tax::find($variant['tax_id']);
            $rate = (float) optional($tax)->rate_percent ?? 0;
            $inclusive = (bool) optional($tax)->is_price_inclusive ?? false;
            $lineTotal = ($variant['quantity'] ?? 0) * ($variant['price'] ?? 0);
            $base = ($inclusive && $rate > 0) ? ($lineTotal / (1 + ($rate / 100))) : $lineTotal;
            $taxAmount = $base * ($rate / 100);
            $computedTotal += $base + $taxAmount;
        }
        $this->total = $computedTotal;

        if ($this->mode === 'create') {
            $activeCompanyId = session('active_company_id');
            if (!$activeCompanyId) {
                session()->flash('swalt', [
                    'icon' => 'error',
                    'title' => 'Error',
                    'text' => 'No hay una compañía activa seleccionada. Por favor, seleccione una compañía antes de crear una compra.',
                ]);
                return redirect()->back();
            }

            $parts = app(SequenceService::class)->getNextParts($this->journal_id);
            $purchase = Purchase::create([
                'journal_id' => $this->journal_id,
                'serie' => $parts['serie'],
                'correlative' => $parts['correlative'],
                'date' => $this->date ?? now(),
                'supplier_id' => $this->supplier_id,
                'warehouse_id' => $this->warehouse_id,
                'total' => $this->total,
                'observation' => $this->observation,
                'company_id' => $activeCompanyId,
            ]);

            foreach ($this->variants as $variant) {
                $tax = Tax::find($variant['tax_id']);
                $rate = (float) optional($tax)->rate_percent ?? 0;
                $inclusive = (bool) optional($tax)->is_price_inclusive ?? false;
                $lineTotal = ($variant['quantity'] ?? 0) * ($variant['price'] ?? 0);
                $base = ($inclusive && $rate > 0) ? ($lineTotal / (1 + ($rate / 100))) : $lineTotal;
                $purchase->variants()->attach($variant['id'], [
                    'quantity' => $variant['quantity'],
                    'price' => $variant['price'],
                    'tax_rate' => $rate,
                    'subtotal' => $base,
                ]);
            }

            $purchase->update([
                'status' => 'draft',
                'payment_status' => $purchase->payment_status ?? 'unpaid',
                'total' => $this->total,
            ]);

            if ($doRedirect) {
                session()->flash('swalt', [
                    'icon' => 'success',
                    'title' => '¡Bien hecho!',
                    'text' => 'La compra fue creada en borrador.',
                ]);
                return redirect()->route('admin.purchases.index');
            }
        }

        // Edit mode
        $this->purchase->update([
            'date' => $this->date ?? now(),
            'supplier_id' => $this->supplier_id,
            'warehouse_id' => $this->warehouse_id,
            'total' => $this->total,
            'observation' => $this->observation,
            'vendor_bill_number' => $this->vendor_bill_number,
            'vendor_bill_date' => $this->vendor_bill_date,
        ]);

        $syncData = [];
        foreach ($this->variants as $variant) {
            $tax = Tax::find($variant['tax_id']);
            $rate = (float) optional($tax)->rate_percent ?? 0;
            $inclusive = (bool) optional($tax)->is_price_inclusive ?? false;
            $lineTotal = ($variant['quantity'] ?? 0) * ($variant['price'] ?? 0);
            $base = ($inclusive && $rate > 0) ? ($lineTotal / (1 + ($rate / 100))) : $lineTotal;
            $syncData[$variant['id']] = [
                'quantity' => $variant['quantity'],
                'price' => $variant['price'],
                'tax_rate' => $rate,
                'subtotal' => $base,
            ];
        }
        $this->purchase->variants()->sync($syncData);

        if ($doRedirect) {
            session()->flash('swalt', [
                'icon' => 'success',
                'title' => '¡Bien hecho!',
                'text' => 'La compra fue actualizada exitosamente.',
            ]);
            return redirect()->route('admin.purchases.index');
        }
    }

    public function post()
    {
        if (!$this->purchase || $this->purchase->status !== 'draft') {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'No permitido',
                'text' => 'Solo compras en borrador pueden contabilizarse.',
            ]);
            return;
        }

        $this->validate([
            'vendor_bill_number' => 'nullable|string|max:50',
            'vendor_bill_date' => 'nullable|date',
        ]);

        // Persist current changes without redirect
        $this->save(false);

        // Registrar entradas en Kardex a partir de las líneas ya guardadas
        $purchase = $this->purchase->fresh(['variants']);
        $warehouseId = $purchase->warehouse_id;
        foreach ($purchase->variants as $variant) {
            Kardex::registerEntry(
                $purchase,
                [
                    'id' => $variant->id,
                    'quantity' => (float) ($variant->pivot->quantity ?? 0),
                    'price' => (float) ($variant->pivot->price ?? 0),
                    'subtotal' => (float) ($variant->pivot->subtotal ?? 0),
                ],
                $warehouseId,
                'Compra'
            );
        }

        // Cambiar estado a publicado
        $this->purchase->update(['status' => 'posted']);
        $this->status = 'posted';

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Compra contabilizada',
            'text' => 'La compra fue publicada correctamente.',
        ]);
    }

    public function cancel()
    {
        if (!$this->purchase) {
            return;
        }

        // Bloquear si tiene pagos registrados (parcial o completo)
        if (in_array($this->purchase->payment_status, ['partial', 'paid'])) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No permitido',
                'text' => 'La compra tiene pagos registrados. Anule los pagos antes de cancelar.',
            ]);
            return;
        }

        // Si está publicada, revertir inventario (salida) por cada línea
        if ($this->purchase->status === 'posted') {
            foreach ($this->variants as $variant) {
                Kardex::registerExit(
                    $this->purchase,
                    [
                        'id' => $variant['id'],
                        'quantity' => $variant['quantity'],
                        'price' => $variant['price'] ?? 0,
                        'subtotal' => $variant['subtotal'] ?? null,
                    ],
                    $this->warehouse_id,
                    'Anulación de compra'
                );
            }
        }

        // Cambiar estado a cancelado
        $this->purchase->update(['status' => 'cancelled']);
        $this->status = 'cancelled';

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Compra cancelada',
            'text' => 'Se anuló la compra exitosamente.',
        ]);
    }

    public function reopen()
    {
        if (!$this->purchase || !in_array($this->purchase->status, ['posted', 'cancelled'])) {
            return;
        }
        $this->purchase->update(['status' => 'draft']);
        $this->status = 'draft';

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Compra reabierta',
            'text' => 'La compra volvió a estado borrador.',
        ]);
    }

    public function markPaid()
    {
        if (!$this->purchase) {
            return;
        }

        // Solo permitir pago si está publicada y no está ya pagada
        if ($this->purchase->status !== 'posted') {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Acción no válida',
                'text' => 'Solo se puede registrar pago para compras publicadas.',
            ]);
            return;
        }

        if ($this->purchase->payment_status === 'paid') {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'Ya está pagada',
                'text' => 'La compra ya está marcada como pagada.',
            ]);
            return;
        }

        $this->purchase->update(['payment_status' => 'paid']);
        $this->payment_status = 'paid';

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Pago registrado',
            'text' => 'La compra quedó como pagada.',
        ]);
    }

    public function markUnpaid()
    {
        if (!$this->purchase) {
            return;
        }

        if ($this->purchase->status !== 'posted') {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Acción no válida',
                'text' => 'Solo se puede anular el pago en compras publicadas.',
            ]);
            return;
        }

        if ($this->purchase->payment_status === 'unpaid') {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'Sin pagos',
                'text' => 'La compra ya está como no pagada.',
            ]);
            return;
        }

        $this->purchase->update(['payment_status' => 'unpaid']);
        $this->payment_status = 'unpaid';

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Pago anulado',
            'text' => 'Se anuló el pago. Ahora puede cancelar la compra si lo requiere.',
        ]);
    }

    public function openModal(Purchase $purchase)
    {
        $supplier = $purchase->supplier;
        $this->form['open'] = true;
        $this->form['document'] = 'Compra ' . ' ' . ($purchase->serie ?? '') . ' ' . ($purchase->correlative ?? '');
        $this->form['client'] = optional($supplier)->document_number . ' - ' . optional($supplier)->name;
        $this->form['email'] = optional($supplier)->email;
        $this->form['model'] = $purchase;
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

    public function render()
    {
        return view('livewire.admin.form.purchase-form');
    }
}
