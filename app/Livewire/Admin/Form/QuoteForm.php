<?php

namespace App\Livewire\Admin\Form;

use App\Mail\PdfSend;
use App\Models\Journal;
use App\Models\Quote;
use App\Models\Tax;
use App\Models\Variant;
use App\Services\SequenceService;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class QuoteForm extends Component
{
    public $mode = 'create';
    public ?Quote $quote = null;

    // Journals and preview
    public $journalOptions = [];
    public $journal_id;
    public $correlative;

    // Core fields
    public $date;
    public $customer_id;
    public $total = 0;
    public $observation;

    // Items
    public $variant_id;
    public $variants = [];
    public $taxes = [];
    public $default_tax_id = null;

    // Status
    public $status;

    // Email modal
    public $form = [
        'open' => false,
        'document' => '',
        'client' => '',
        'email' => '',
        'model' => null,
        'view_pdf_patch' => 'admin.quotes.pdf',
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

    public function mount(Quote $quote = null, $mode = 'create')
    {
        $this->mode = $mode ?? ($quote ? 'edit' : 'create');
        $this->quote = $quote;

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
        $journals = Journal::where('type', 'quote')->with('sequence')->orderBy('name')->get();
        $this->journalOptions = $journals->map(fn($j) => [
            'id' => $j->id,
            'label' => sprintf('%s (%s)', $j->name, $j->code),
        ])->toArray();

        if ($this->mode === 'edit' && $this->quote) {
            $q = $this->quote->load('variants.product', 'customer', 'journal');
            $this->journal_id = $q->journal_id;
            $this->correlative = $q->correlative;
            $this->date = optional($q->date)->format('Y-m-d');
            $this->customer_id = $q->customer_id;
            $this->observation = $q->observation;
            $this->status = $q->status;

            $taxesCol = collect($this->taxes);
            $this->variants = $q->variants->map(function ($variant) use ($taxesCol) {
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

            $this->total = $q->total;
        } else {
            // create defaults
            $this->date = now()->format('Y-m-d');
            $first = $journals->first();
            $this->journal_id = $first?->id;
            $this->updatePreview();
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

        $defaultTax = collect($this->taxes)->firstWhere('id', $this->default_tax_id) ?? collect($this->taxes)->first();
        $rate = (float) ($defaultTax['rate_percent'] ?? 0);
        $inclusive = (bool) ($defaultTax['is_price_inclusive'] ?? false);
        $qty = 1;
        $price = (float) ($variant->price ?? 0);
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
        if ($this->mode === 'edit' && $this->quote && $this->quote->status !== 'draft') {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'No permitido',
                'text' => 'Solo cotizaciones en borrador pueden editarse.',
            ]);
            return;
        }

        $rules = [
            'date' => 'nullable|date',
            'customer_id' => 'required|exists:customers,id',
            'total' => 'required|numeric|min:0',
            'observation' => 'nullable|string|max:255',
            'variants' => 'required|array|min:1',
            'variants.*.id' => 'required|exists:variants,id',
            'variants.*.quantity' => 'required|numeric|min:1',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.tax_id' => 'required|exists:taxes,id',
        ];

        if ($this->mode === 'create') {
            $rules['journal_id'] = 'required|exists:journals,id';
        }

        $this->validate($rules, [], [
            'journal_id' => 'serie',
            'customer_id' => 'cliente',
            'observation' => 'observación',
            'variants.*.id' => 'producto',
            'variants.*.quantity' => 'cantidad',
            'variants.*.price' => 'precio',
            'variants.*.tax_id' => 'impuesto',
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
                    'text' => 'No hay una compañía activa seleccionada. Por favor, seleccione una compañía antes de crear una cotización.',
                ]);
                return redirect()->back();
            }

            $parts = app(SequenceService::class)->getNextParts($this->journal_id);
            $quote = Quote::create([
                'journal_id' => $this->journal_id,
                'serie' => $parts['serie'],
                'correlative' => $parts['correlative'],
                'date' => $this->date ?? now(),
                'customer_id' => $this->customer_id,
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
                $quote->variants()->attach($variant['id'], [
                    'quantity' => $variant['quantity'],
                    'price' => $variant['price'],
                    'tax_rate' => $rate,
                    'subtotal' => $base,
                ]);
            }

            $quote->update([
                'status' => 'draft',
                'total' => $this->total,
            ]);

            if ($doRedirect) {
                session()->flash('swalt', [
                    'icon' => 'success',
                    'title' => '¡Bien hecho!',
                    'text' => 'La cotización fue creada en borrador.',
                ]);
                return redirect()->route('admin.quotes.index');
            }
        }

        // Edit mode
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
            $lineTotal = ($variant['quantity'] ?? 0) * ($variant['price'] ?? 0);
            $base = ($inclusive && $rate > 0) ? ($lineTotal / (1 + ($rate / 100))) : $lineTotal;
            $syncData[$variant['id']] = [
                'quantity' => $variant['quantity'],
                'price' => $variant['price'],
                'tax_rate' => $rate,
                'subtotal' => $base,
            ];
        }
        $this->quote->variants()->sync($syncData);

        if ($doRedirect) {
            session()->flash('swalt', [
                'icon' => 'success',
                'title' => '¡Bien hecho!',
                'text' => 'La cotización fue actualizada exitosamente.',
            ]);
            return redirect()->route('admin.quotes.index');
        }
    }

    public function post()
    {
        if (!$this->quote || $this->quote->status !== 'draft') {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'No permitido',
                'text' => 'Solo cotizaciones en borrador pueden publicarse.',
            ]);
            return;
        }

        // Persist current changes without redirect
        $this->save(false);

        // Cambiar estado a publicado
        $this->quote->update(['status' => 'posted']);
        $this->status = 'posted';

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Cotización publicada',
            'text' => 'La cotización fue publicada correctamente.',
        ]);
    }

    public function cancel()
    {
        if (!$this->quote) {
            return;
        }

        // Cambiar estado a cancelado
        $this->quote->update(['status' => 'cancelled']);
        $this->status = 'cancelled';

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Cotización cancelada',
            'text' => 'Se anuló la cotización exitosamente.',
        ]);
    }

    public function reopen()
    {
        if (!$this->quote || !in_array($this->quote->status, ['posted', 'cancelled'])) {
            return;
        }
        $this->quote->update(['status' => 'draft']);
        $this->status = 'draft';

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Cotización reabierta',
            'text' => 'La cotización volvió a estado borrador.',
        ]);
    }

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
        return view('livewire.admin.form.quote-form');
    }
}
