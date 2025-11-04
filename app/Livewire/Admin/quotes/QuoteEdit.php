<?php

namespace App\Livewire\Admin\quotes;

use App\Models\Journal;
use Livewire\Component;
use App\Models\Quote;
use App\Models\Variant;
use Illuminate\Support\Facades\Mail;
use App\Mail\PdfSend;
use App\Models\Tax;

class QuoteEdit extends Component
{

    public $journals = [];
    public $journal_id;

    public $correlative;
    public $date;
    public $customer_id;
    public $total = 0;
    public $observation;

    public $variant_id;
    public $variants = [];
    public $taxes = [];
    public $default_tax_id = null;

    public $quote;

    public $form = [
        'open' => false,
        'document' => '',
        'client' => '',
        'email' => '',
        'model' => null,
        'view_pdf_patch' => 'admin.quotes.pdf',
    ];

    public function mount(Quote $quote)
    {
        $this->quote = $quote->load('variants.product', 'variants.attributeValues', 'customer');
        // Cargar impuestos activos (dinámicos) y definir uno por defecto
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

        // 1. Cargar los journals de tipo 'quote' para mostrarlos en el select (aunque esté deshabilitado)
        $this->journals = Journal::where('type', 'quote')->get();

        // 2. Asignar el journal_id y el correlativo desde la cotización existente
        $this->journal_id = $quote->journal_id;
        $this->correlative = $quote->correlative;
        $this->date = optional($quote->date)->format('Y-m-d');
        $this->customer_id = $quote->customer_id;
        $this->observation = $quote->observation;

        $taxesCol = collect($this->taxes);
        $this->variants = $quote->variants->map(function ($variant) use ($taxesCol) {
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

        $this->total = $quote->total;
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

        // Impuesto por defecto
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
        $this->validate(
            [
                'date' => 'nullable|date',
                'customer_id' => 'required|exists:customers,id',
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
            $lineSubtotal = (float)($variant['quantity'] ?? 0) * (float)($variant['price'] ?? 0);
            $base = ($inclusive && $rate > 0) ? ($lineSubtotal / (1 + ($rate / 100))) : $lineSubtotal;
            $computedTotal += $base * (1 + ($rate / 100));
        }
        $this->total = $computedTotal;

        // 3. Actualizar solo los campos editables. La serie y el correlativo no deben cambiar.
        $this->quote->update([
            'date' => $this->date ?? now(),
            'customer_id' => $this->customer_id,
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
        $this->quote->variants()->sync($syncData);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'La cotización fue actualizada exitosamente.',
        ]);

        return redirect()->route('admin.quotes.index');
    }

    // ===== Modal de envío de correo =====
    public function openModal(Quote $quote)
    {
        $customer = $quote->customer;
        $this->form['open'] = true;
        $this->form['document'] = 'Cotización ' . ' ' . ($quote->serie ?? '') . ' ' . ($quote->correlative ?? '');
        $this->form['client'] = optional($customer)->document_number . ' - ' . optional($customer)->name;
        $this->form['email'] = optional($customer)->email;
        $this->form['model'] = $quote;
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
        return view('livewire.admin.quotes.quote-edit');
    }
}
