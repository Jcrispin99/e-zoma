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

    public $status;
    public $payment_status;

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

        $this->variants = $sale->variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'name' => $variant->fullName,
                'quantity' => $variant->pivot->quantity,
                'price' => $variant->pivot->price,
                'subtotal' => $variant->pivot->subtotal,
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

        $this->variants[] = [
            'id' => $variant->id,
            'name' => $variant->fullName,
            'quantity' => 1,
            'price' => $variant->price,
            'subtotal' => $variant->price,
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

        $index = collect($this->variants)->search(fn ($v) => ($v['id'] ?? null) === $variant->id);
        if ($index !== false) {
            $current = $this->variants[$index];
            $current['quantity'] = (int)($current['quantity'] ?? 0) + 1;
            $current['subtotal'] = (float)($current['quantity'] ?? 0) * (float)($current['price'] ?? 0);
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
                'warehouse_id' => 'required|exists:warehouses,id',
                'total' => 'required|numeric|min:0',
                'observation' => 'nullable|string|max:255',
                'variants' => 'required|array|min:1',
                'variants.*.id' => 'required|exists:variants,id',
                'variants.*.quantity' => 'required|numeric|min:1',
                'variants.*.price' => 'required|numeric|min:0',
            ],
            [],
            [
                'customer_id' => 'cliente',
                'warehouse_id' => 'almacén',
                'observation' => 'observación',
                'variants.*.id' => 'producto',
                'variants.*.quantity' => 'cantidad',
                'variants.*.price' => 'precio',
            ]
        );

        $computedTotal = 0;
        foreach ($this->variants as $variant) {
            $computedTotal += ($variant['quantity'] ?? 0) * ($variant['price'] ?? 0);
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
            $syncData[$variant['id']] = [
                'quantity' => $variant['quantity'],
                'price' => $variant['price'],
                'subtotal' => $variant['quantity'] * $variant['price'],
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
        if (! in_array($this->sale->status, ['posted', 'cancelled'])) {
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
        $this->sale->update(['payment_status' => 'paid']);
        $this->payment_status = 'paid';

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Pago registrado',
            'text' => 'La venta quedó como pagada.',
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

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Venta contabilizada',
            'text' => 'La venta fue publicada correctamente.',
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

    public function render()
    {
        return view('livewire.admin.sales.sale-edit');
    }
}
