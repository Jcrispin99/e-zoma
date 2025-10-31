<?php

namespace App\Livewire\Admin\purchases;

use App\Models\Journal;
use App\Facades\Kardex;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\Variant;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use App\Mail\PdfSend;

class PurchaseEdit extends Component
{
    public Purchase $purchase;

    public $journals = [];
    public $journal_id;
    public $correlative;
    public $date;
    public $warehouse_id;
    public $supplier_id;
    public $total = 0;
    public $observation;

    public $variant_id;
    public $variants = [];

    // Estados y datos del comprobante del proveedor
    public $status;
    public $payment_status;
    public $vendor_bill_number;
    public $vendor_bill_date;

    // Propiedades para el modal de envío de correo
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

    public function mount(Purchase $purchase)
    {
        $this->purchase = $purchase->load('variants.product', 'variants.attributeValues', 'supplier', 'warehouse', 'purchaseOrder');

        // 1. Cargar journals y asignar el de la compra existente
        $this->journals = Journal::where('type', 'purchase')->get();
        $this->journal_id = $purchase->journal_id;

        $this->correlative = $purchase->correlative;

        $this->date = optional($purchase->date)->format('Y-m-d');
        $this->supplier_id = $purchase->supplier_id;
        $this->warehouse_id = $purchase->warehouse_id;
        $this->observation = $purchase->observation;
        $this->status = $purchase->status;
        $this->payment_status = $purchase->payment_status;
        $this->vendor_bill_number = $purchase->vendor_bill_number;
        $this->vendor_bill_date = optional($purchase->vendor_bill_date)->format('Y-m-d');

        $this->variants = $purchase->variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'name' => $variant->fullName,
                'quantity' => $variant->pivot->quantity,
                'price' => $variant->pivot->price,
                'tax_rate' => (int) ($variant->pivot->tax_rate ?? 0),
                'subtotal' => $variant->pivot->subtotal,
            ];
        })->toArray();

        $this->total = $purchase->total;
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

        $this->variants[] = [
            'id' => $variant->id,
            'name' => $variant->fullName,
            'quantity' => 1,
            'price' => $lastRecord['cost'],
            'tax_rate' => 18,
            'subtotal' => $lastRecord['cost'] * 1,
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

        if (! $variant) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Código no encontrado',
                'text' => 'No se encontró ningún producto para ese código o SKU.',
            ]);
            return;
        }

        // Si ya existe en la tabla, incrementar cantidad
        foreach ($this->variants as $index => $row) {
            if (($row['id'] ?? null) === $variant->id) {
                $current = (int) ($this->variants[$index]['quantity'] ?? 0);
                $this->variants[$index]['quantity'] = $current + 1;
                if (array_key_exists('subtotal', $this->variants[$index])) {
                    $price = (float) ($this->variants[$index]['price'] ?? 0);
                    $qty = (int) ($this->variants[$index]['quantity'] ?? 0);
                    $this->variants[$index]['subtotal'] = $price * $qty;
                }
                return;
            }
        }

        // Caso contrario, usar la lógica existente de addProduct
        $this->variant_id = $variant->id;
        $this->addProduct();
        $this->reset('variant_id');
    }

    public function save()
    {
        $this->validate(
            [
                'date' => 'nullable|date',
                'supplier_id' => 'required|exists:suppliers,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'total' => 'required|numeric|min:0',
                'observation' => 'nullable|string|max:255',
                'variants' => 'required|array|min:1',
                'variants.*.id' => 'required|exists:variants,id',
                'variants.*.quantity' => 'required|numeric|min:1',
                'variants.*.price' => 'required|numeric|min:0',
                'variants.*.tax_rate' => 'required|numeric|min:0',
            ],
            [],
            [
                'supplier_id' => 'proveedor',
                'warehouse_id' => 'almacén',
                'observation' => 'observación',
                'variants.*.id' => 'producto',
                'variants.*.quantity' => 'cantidad',
                'variants.*.price' => 'precio',
                'variants.*.tax_rate' => 'IGV',
            ]
        );

        // Calcular total basado en líneas y IGV
        $computedTotal = 0;
        foreach ($this->variants as $variant) {
            $lineSubtotal = ($variant['quantity'] ?? 0) * ($variant['price'] ?? 0);
            $lineTax = $lineSubtotal * (($variant['tax_rate'] ?? 0) / 100);
            $computedTotal += $lineSubtotal + $lineTax;
        }
        $this->total = $computedTotal;

        // 2. Actualizar solo los campos editables. La serie y el correlativo no deben cambiar.
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
            $subtotal = $variant['quantity'] * $variant['price'];
            $syncData[$variant['id']] = [
                'quantity' => $variant['quantity'],
                'price' => $variant['price'],
                'tax_rate' => $variant['tax_rate'],
                'subtotal' => $subtotal,
            ];
        }
        $this->purchase->variants()->sync($syncData);

        // Recalcular métricas de la OC relacionada (si aplica)
        if ($this->purchase->purchase_order_id) {
            $po = $this->purchase->purchaseOrder;
            if ($po) {
                $this->recalcPoMetrics($po);
            }
        }

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'La compra fue actualizada exitosamente.',
        ]);

        return redirect()->route('admin.purchases.index');
    }

    /**
     * Contabilizar la compra: pasa de borrador a publicada.
     */
    public function post()
    {
        if ($this->purchase->status !== 'draft') {
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

        // Recalcular total por seguridad
        $this->save();

        $this->purchase->update([
            'status' => 'posted',
        ]);
        $this->status = 'posted';

        // Recalcular métricas del PO asociado
        if ($this->purchase->purchase_order_id && $this->purchase->purchaseOrder) {
            $this->recalcPoMetrics($this->purchase->purchaseOrder);
        }

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Compra contabilizada',
            'text' => 'La compra fue publicada correctamente.',
        ]);
    }

    /**
     * Cancelar la compra.
     */
    public function cancel()
    {
        if ($this->purchase->status === 'cancelled') {
            return;
        }

        $this->purchase->update(['status' => 'cancelled']);
        $this->status = 'cancelled';

        if ($this->purchase->purchase_order_id && $this->purchase->purchaseOrder) {
            $this->recalcPoMetrics($this->purchase->purchaseOrder);
        }

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Compra cancelada',
            'text' => 'Se anuló la compra exitosamente.',
        ]);
    }

    /**
     * Reabrir compra cancelada o publicada a borrador.
     */
    public function reopen()
    {
        if (! in_array($this->purchase->status, ['posted', 'cancelled'])) {
            return;
        }

        $this->purchase->update(['status' => 'draft']);
        $this->status = 'draft';

        if ($this->purchase->purchase_order_id && $this->purchase->purchaseOrder) {
            $this->recalcPoMetrics($this->purchase->purchaseOrder);
        }

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Compra reabierta',
            'text' => 'La compra volvió a estado borrador.',
        ]);
    }

    /**
     * Marcar pago como completo.
     */
    public function markPaid()
    {
        $this->purchase->update(['payment_status' => 'paid']);
        $this->payment_status = 'paid';

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Pago registrado',
            'text' => 'La compra quedó como pagada.',
        ]);
    }

    /**
     * Recalcular totales e indicadores de facturación de la Orden de Compra.
     */
    private function recalcPoMetrics(PurchaseOrder $po): void
    {
        $orderedQty = DB::table('variantables')
            ->where('variantable_type', PurchaseOrder::class)
            ->where('variantable_id', $po->id)
            ->sum('quantity');

        $billedQty = DB::table('variantables')
            ->join('purchases', 'variantables.variantable_id', '=', 'purchases.id')
            ->where('variantables.variantable_type', Purchase::class)
            ->where('purchases.purchase_order_id', $po->id)
            ->where('purchases.status', '<>', 'cancelled')
            ->sum('variantables.quantity');

        $purchasesCount = Purchase::query()
            ->where('purchase_order_id', $po->id)
            ->where('status', '<>', 'cancelled')
            ->count();

        $billingStatus = $billedQty <= 0
            ? 'none'
            : ($billedQty < $orderedQty ? 'partial' : 'complete');

        $po->update([
            'ordered_qty_total' => (float) $orderedQty,
            'billed_qty_total' => (float) $billedQty,
            'purchases_count' => $purchasesCount,
            'billing_status' => $billingStatus,
            'billed_at' => $billingStatus === 'complete' ? now() : null,
        ]);
    }

    public function render()
    {
        return view('livewire.admin.purchases.purchase-edit');
    }

    // ===== Métodos para el modal de envío de correo =====
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
}
