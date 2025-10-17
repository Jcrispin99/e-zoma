<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Quote;
use App\Models\Variant;
use Illuminate\Support\Facades\Mail;
use App\Mail\PdfSend;

class QuoteEdit extends Component
{
    public Quote $quote;

    public $voucher_type = 1;
    public $serie;
    public $correlative;
    public $date;
    public $customer_id;
    public $total = 0;
    public $observation;

    public $variant_id;
    public $variants = [];

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

        $this->voucher_type = $quote->voucher_type;
        $this->serie = $quote->serie;
        $this->correlative = $quote->correlative;
        $this->date = optional($quote->date)->format('Y-m-d');
        $this->customer_id = $quote->customer_id;
        $this->observation = $quote->observation;

        $this->variants = $quote->variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'name' => $variant->fullName,
                'quantity' => $variant->pivot->quantity,
                'price' => $variant->pivot->price,
                'subtotal' => $variant->pivot->subtotal,
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

        $this->variants[] = [
            'id' => $variant->id,
            'name' => $variant->fullName,
            'quantity' => 1,
            'price' => $variant->price,
            'subtotal' => $variant->price,
        ];
        $this->reset('variant_id');
    }

    public function save()
    {
        $this->validate(
            [
                'voucher_type' => 'required|in:1,2',
                'date' => 'nullable|date',
                'customer_id' => 'required|exists:customers,id',
                'total' => 'required|numeric|min:0',
                'observation' => 'nullable|string|max:255',
                'variants' => 'required|array|min:1',
                'variants.*.id' => 'required|exists:variants,id',
                'variants.*.quantity' => 'required|numeric|min:1',
                'variants.*.price' => 'required|numeric|min:0',
            ],
            [],
            [
                'voucher_type' => 'tipo de comprobante',
                'customer_id' => 'cliente',
                'observation' => 'observación',
                'variants.*.id' => 'producto',
                'variants.*.quantity' => 'cantidad',
                'variants.*.price' => 'precio',
            ]
        );

        $computedTotal = 0;
        foreach ($this->variants as $variant) {
            $computedTotal += (float)($variant['quantity'] ?? 0) * (float)($variant['price'] ?? 0);
        }
        $this->total = $computedTotal;

        $this->quote->update([
            'voucher_type' => $this->voucher_type,
            'serie' => $this->serie,
            'correlative' => $this->correlative,
            'date' => $this->date ?? now(),
            'customer_id' => $this->customer_id,
            'total' => $this->total,
            'observation' => $this->observation,
        ]);

        $syncData = [];
        foreach ($this->variants as $variant) {
            $syncData[$variant['id']] = [
                'quantity' => $variant['quantity'],
                'price' => $variant['price'],
                'subtotal' => $variant['quantity'] * $variant['price'],
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
        return view('livewire.admin.quote-edit');
    }
}
