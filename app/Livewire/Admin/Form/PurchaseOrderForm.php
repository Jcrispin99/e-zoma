<?php

namespace App\Livewire\Admin\Form;

use App\Mail\PdfSend;
use App\Models\Journal;
use App\Models\PurchaseOrder;
use App\Models\Tax;
use App\Models\Variant;
use App\Services\SequenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class PurchaseOrderForm extends Component
{
    public ?PurchaseOrder $purchaseOrder = null;
    public string $mode = 'create';

    public bool $hasPurchase = false;
    public ?int $purchaseId = null;

    public $journals = [];
    public $journal_id;
    public $correlative;
    public $journalOptions = [];

    public $date;
    public $supplier_id;
    public $total = 0;
    public $observation;

    public $variant_id;
    public $variants = [];
    public $taxes = [];

    public $form = [
        'open' => false,
        'document' => '',
        'client' => '',
        'email' => '',
        'model' => null,
        'view_pdf_patch' => 'admin.purchases-orders.pdf',
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

    public function mount(?PurchaseOrder $purchaseOrder = null, string $mode = 'create')
    {
        $this->mode = $mode;
        $this->date = now()->format('Y-m-d');

        // Cargar impuestos activos
        $taxes = Tax::active()
            ->orderBy('name')
            ->get(['id', 'name', 'invoice_label', 'rate_percent', 'is_price_inclusive']);
        $this->taxes = $taxes->toArray();

        if ($purchaseOrder) {
            $this->purchaseOrder = $purchaseOrder->load('variants.product', 'variants.attributeValues', 'purchase', 'supplier');

            // Journals y datos base
            $this->journals = Journal::where('type', 'purchase-order')->get();
            $this->journalOptions = $this->mapJournalOptions($this->journals);
            $this->journal_id = $purchaseOrder->journal_id;
            $this->correlative = $purchaseOrder->correlative;

            $this->date = optional($purchaseOrder->date)->format('Y-m-d');
            $this->supplier_id = $purchaseOrder->supplier_id;
            $this->observation = $purchaseOrder->observation;

            // Variantes con impuesto mapeado
            $this->variants = $purchaseOrder->variants->map(function ($variant) use ($taxes) {
                $matched = $taxes->where('rate_percent', (float) $variant->pivot->tax_rate)
                    ->sortBy('is_price_inclusive')
                    ->first();

                return [
                    'id' => $variant->id,
                    'name' => $variant->fullName,
                    'quantity' => $variant->pivot->quantity,
                    'price' => $variant->pivot->price,
                    'tax_id' => optional($matched)->id,
                    'tax_rate' => (float) (optional($matched)->rate_percent ?? $variant->pivot->tax_rate),
                    'tax_inclusive' => (bool) (optional($matched)->is_price_inclusive ?? false),
                    'subtotal' => $variant->pivot->subtotal,
                ];
            })->toArray();

            $this->total = (float) $purchaseOrder->total;
            $this->hasPurchase = (bool) $this->purchaseOrder->purchase;
            $this->purchaseId = optional($this->purchaseOrder->purchase)->id;
        } else {
            // Modo create
            $this->journals = Journal::where('type', 'purchase-order')
                ->with('sequence')
                ->orderBy('name')
                ->get();
            $this->journalOptions = $this->mapJournalOptions($this->journals);

            $first = collect($this->journals)->first();
            $this->journal_id = $first ? $first->id : null;

            // No previsualizar correlativo para evitar conflictos
            $this->correlative = 'Asignado al guardar';
        }
    }

    protected function mapJournalOptions($journals): array
    {
        return collect($journals)->map(function ($j) {
            if (is_array($j)) {
                $id = $j['id'] ?? null;
                $name = $j['name'] ?? '';
                $code = $j['code'] ?? '';
            } else {
                $id = $j->id ?? null;
                $name = $j->name ?? '';
                $code = $j->code ?? '';
            }

            return [
                'id' => $id,
                'label' => trim($name . ' (' . $code . ')'),
            ];
        })->filter(function ($o) {
            return !is_null($o['id']);
        })->values()->toArray();
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

        $defaultTax = Tax::where('is_active', true)->where('is_default', true)->first();
        if (!$defaultTax) {
            $defaultTax = Tax::active()->orderBy('name')->first();
        }

        $defaultPrice = ($this->mode === 'edit') ? ($variant->purchase_price ?? 0) : 0;

        $this->variants[] = [
            'id' => $variant->id,
            'name' => $variant->fullName,
            'quantity' => 1,
            'price' => $defaultPrice,
            'tax_id' => optional($defaultTax)->id,
            'tax_rate' => optional($defaultTax)->rate_percent ?? 0,
            'tax_inclusive' => (bool) (optional($defaultTax)->is_price_inclusive ?? false),
            'subtotal' => $defaultPrice,
        ];

        $this->reset('variant_id');
    }

    public function scanBarcode($code = null)
    {
        $code = trim((string) ($code ?? ''));
        if ($code === '') {
            return;
        }

        $variant = Variant::with('product')
            ->where('barcode', $code)
            ->orWhere('sku', $code)
            ->first();

        if (!$variant) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Producto no encontrado',
                'text' => 'No se encontró un producto con ese código.',
            ]);
            return;
        }

        $existingIndex = collect($this->variants)->search(function ($v) use ($variant) {
            return ($v['id'] ?? null) === $variant->id;
        });

        if ($existingIndex !== false) {
            $this->variants[$existingIndex]['quantity'] = (int) ($this->variants[$existingIndex]['quantity'] ?? 0) + 1;
        } else {
            $defaultTax = Tax::where('is_active', true)->where('is_default', true)->first();
            if (!$defaultTax) {
                $defaultTax = Tax::active()->orderBy('name')->first();
            }

            $defaultPrice = ($this->mode === 'edit') ? ($variant->purchase_price ?? 0) : 0;

            $this->variants[] = [
                'id' => $variant->id,
                'name' => $variant->fullName,
                'quantity' => 1,
                'price' => $defaultPrice,
                'tax_id' => optional($defaultTax)->id,
                'tax_rate' => optional($defaultTax)->rate_percent ?? 0,
                'tax_inclusive' => (bool) (optional($defaultTax)->is_price_inclusive ?? false),
                'subtotal' => $defaultPrice,
            ];
        }
    }

    public function save()
    {
        $this->validate(
            [
                'journal_id' => 'required|exists:journals,id',
                'date' => 'nullable|date',
                'supplier_id' => 'required|exists:suppliers,id',
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
                'journal_id' => 'serie',
                'supplier_id' => 'proveedor',
                'observation' => 'observación',
                'variants.*.id' => 'producto',
                'variants.*.quantity' => 'cantidad',
                'variants.*.price' => 'precio',
                'variants.*.tax_id' => 'impuesto',
            ]
        );

        if ($this->mode === 'edit') {
            if ($this->purchaseOrder->status === 'cancelled') {
                $this->dispatch('swal', [
                    'icon' => 'error',
                    'title' => 'No permitido',
                    'text' => 'No se puede editar una orden de compra cancelada.',
                ]);
                return redirect()->route('admin.purchases-orders.index');
            }

            $this->purchaseOrder->update([
                'date' => $this->date ?? now(),
                'supplier_id' => $this->supplier_id,
                'total' => $this->total,
                'observation' => $this->observation,
            ]);

            $syncData = [];
            $totalCalculado = 0;
            foreach ($this->variants as $variant) {
                $qty = (float) ($variant['quantity'] ?? 0);
                $price = (float) ($variant['price'] ?? 0);
                $tax = Tax::find($variant['tax_id']);
                $rate = (float) (optional($tax)->rate_percent ?? 0);
                $inclusive = (bool) (optional($tax)->is_price_inclusive ?? false);

                $gross = $qty * $price;
                if ($inclusive && $rate > 0) {
                    $base = $gross / (1 + ($rate / 100));
                    $taxAmount = $gross - $base;
                    $lineTotal = $gross;
                } else {
                    $base = $gross;
                    $taxAmount = $base * ($rate / 100);
                    $lineTotal = $base + $taxAmount;
                }

                $totalCalculado += $lineTotal;

                $syncData[$variant['id']] = [
                    'quantity' => $qty,
                    'price' => $price,
                    'tax_rate' => $rate,
                    'subtotal' => $base,
                ];
            }
            $this->purchaseOrder->variants()->sync($syncData);

            $orderedQty = DB::table('variantables')
                ->where('variantable_type', PurchaseOrder::class)
                ->where('variantable_id', $this->purchaseOrder->id)
                ->sum('quantity');

            $this->purchaseOrder->update([
                'ordered_qty_total' => (float) $orderedQty,
                'total' => $totalCalculado,
            ]);

            session()->flash('swalt', [
                'icon' => 'success',
                'title' => '¡Bien hecho!',
                'text' => 'Orden de compra actualizada exitosamente.',
            ]);

            return redirect()->route('admin.purchases-orders.index');
        } else {
            $activeCompanyId = session('active_company_id');

            if (!$activeCompanyId) {
                $this->dispatch('swal', [
                    'icon' => 'error',
                    'title' => 'Error',
                    'text' => 'No hay una compañía activa seleccionada. Por favor, seleccione una compañía antes de crear una orden de compra.',
                ]);
                return;
            }

            $totalCalculado = 0;
            $linesPivot = [];
            foreach ($this->variants as $variant) {
                $qty = (float) $variant['quantity'];
                $price = (float) $variant['price'];

                $tax = Tax::find($variant['tax_id']);
                $rate = (float) optional($tax)->rate_percent ?? 0.0;
                $inclusive = (bool) optional($tax)->is_price_inclusive ?? false;

                $gross = $qty * $price;
                if ($inclusive && $rate > 0) {
                    $base = $gross / (1 + ($rate / 100));
                    $taxAmount = $gross - $base;
                    $lineTotal = $gross;
                } else {
                    $base = $gross;
                    $taxAmount = $base * ($rate / 100);
                    $lineTotal = $base + $taxAmount;
                }

                $totalCalculado += $lineTotal;

                $linesPivot[] = [
                    'variant_id' => $variant['id'],
                    'quantity' => $qty,
                    'price' => $price,
                    'tax_rate' => $rate,
                    'subtotal' => $base,
                ];
            }

            // Consumir secuencia solo al guardar
            $parts = app(SequenceService::class)->getNextParts($this->journal_id);

            $purchaseOrder = PurchaseOrder::create([
                'serie' => $parts['serie'],
                'correlative' => $parts['correlative'],
                'date' => $this->date ?? now(),
                'supplier_id' => $this->supplier_id,
                'total' => $totalCalculado,
                'observation' => $this->observation,
                'company_id' => $activeCompanyId,
                'journal_id' => $this->journal_id,
            ]);

            foreach ($linesPivot as $line) {
                $purchaseOrder->variants()->attach($line['variant_id'], [
                    'quantity' => $line['quantity'],
                    'price' => $line['price'],
                    'tax_rate' => $line['tax_rate'],
                    'subtotal' => $line['subtotal'],
                ]);
            }

            $orderedQty = DB::table('variantables')
                ->where('variantable_type', PurchaseOrder::class)
                ->where('variantable_id', $purchaseOrder->id)
                ->sum('quantity');

            $purchaseOrder->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'ordered_qty_total' => (float) $orderedQty,
            ]);

            session()->flash('swalt', [
                'icon' => 'success',
                'title' => '¡Bien hecho!',
                'text' => 'Orden de compra creada exitosamente.',
            ]);

            return redirect()->route('admin.purchases-orders.edit', $purchaseOrder);
        }
    }

    public function confirmOrder()
    {
        if ($this->mode !== 'edit') {
            return;
        }
        if ($this->purchaseOrder->status !== 'draft') {
            return;
        }

        $this->purchaseOrder->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => '¡Orden Confirmada!',
            'text' => 'La orden de compra ha sido confirmada y ya no se puede editar.',
        ]);
    }

    public function cancelOrder()
    {
        if ($this->mode !== 'edit') {
            return;
        }
        if ($this->purchaseOrder->status === 'done' || $this->purchaseOrder->status === 'cancelled') {
            return;
        }

        $this->purchaseOrder->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->dispatch('swal', [
            'icon' => 'warning',
            'title' => '¡Orden Cancelada!',
            'text' => 'La orden de compra ha sido cancelada.',
        ]);
    }

    public function createPurchase()
    {
        if ($this->mode !== 'edit') {
            return;
        }
        if ($this->hasPurchase && $this->purchaseId) {
            return redirect('/admin/purchases/' . $this->purchaseId . '/edit');
        }

        return redirect()->route('admin.purchases.create', ['purchase_order_id' => $this->purchaseOrder->id]);
    }

    public function viewPurchase()
    {
        if ($this->mode !== 'edit') {
            return;
        }
        if (! $this->hasPurchase || ! $this->purchaseId) {
            return;
        }

        return redirect('/admin/purchases/' . $this->purchaseId . '/edit');
    }

    public function openModal(PurchaseOrder $purchaseOrder)
    {
        if ($this->mode !== 'edit') {
            return;
        }

        $this->form['open'] = true;
        $this->form['document'] = 'Orden de Compra ' . ' ' . $purchaseOrder->serie . ' ' . $purchaseOrder->correlative;
        $this->form['client'] =  $purchaseOrder->supplier->document_number . ' - ' . $purchaseOrder->supplier->name;
        $this->form['email'] = $purchaseOrder->supplier->email;
        $this->form['model'] = $purchaseOrder;
    }

    public function sendEmail()
    {
        if ($this->mode !== 'edit') {
            return;
        }

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
        return view('livewire.admin.purchaseOrders.purchase-order-form');
    }
}
