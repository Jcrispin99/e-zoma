<?php

namespace App\Livewire\Admin;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Variant;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Support\Facades\Mail;
use App\Mail\PdfSend;

class PurchaseOrderEdit extends Component
{
    public PurchaseOrder $purchaseOrder;

    public bool $hasPurchase = false;
    public ?int $purchaseId = null;

    public $voucher_type = 1;
    public $serie = 'A';
    public $correlative;

    public $date;
    public $supplier_id;
    public $total = 0;
    public $observation;

    public $variant_id;
    public $variants = [];

    //Propiedades para el modal de envío de correo
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

    public function mount(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder->load('variants.product', 'variants.attributeValues', 'purchase');

        // Pre-cargar datos base
        $this->voucher_type = $purchaseOrder->voucher_type;
        $this->serie = $purchaseOrder->serie;
        $this->correlative = $purchaseOrder->correlative;
        $this->date = optional($purchaseOrder->date)->format('Y-m-d');
        $this->supplier_id = $purchaseOrder->supplier_id;
        $this->observation = $purchaseOrder->observation;

        // Mapear líneas existentes a estructura de variants del form
        $this->variants = $purchaseOrder->variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'name' => $variant->fullName,
                'quantity' => $variant->pivot->quantity,
                'price' => $variant->pivot->price,
                'tax_rate' => (int) $variant->pivot->tax_rate, // Forzar a entero para que coincida con el select
                'subtotal' => $variant->pivot->subtotal,
            ];
        })->toArray();

        // Cargar total
        $this->total = $this->purchaseOrder->total;

        // Detectar si ya existe una compra (factura) asociada
        $this->hasPurchase = (bool) $this->purchaseOrder->purchase;
        $this->purchaseId = optional($this->purchaseOrder->purchase)->id;
    }

    public function addProduct()
    {
        $variant = Variant::find($this->variant_id);

        $this->variants[] = [
            'id' => $variant->id,
            'name' => $variant->fullName,
            'quantity' => 1,
            'price' => $variant->purchase_price ?? 0,
            'tax_rate' => 18, // IGV por defecto
            'subtotal' => $variant->purchase_price ?? 0,
        ];
    }

    public function save()
    {
        if ($this->purchaseOrder->status === 'cancelled') {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No permitido',
                'text' => 'No se puede editar una orden de compra cancelada.',
            ]);
            return redirect()->route('admin.purchases-orders.index');
        }

        $this->validate(
            [
                'voucher_type' => 'required|in:1,2',
                'date' => 'nullable|date',
                'supplier_id' => 'required|exists:suppliers,id',
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
                'supplier_id' => 'proveedor',
                'observation' => 'observación',
                'variants.*.id' => 'producto',
                'variants.*.quantity' => 'cantidad',
                'variants.*.price' => 'precio',
            ]
        );

        // Actualizar datos base (serie/correlativo permanecen sin cambios)
        $this->purchaseOrder->update([
            'voucher_type' => $this->voucher_type,
            'date' => $this->date ?? now(),
            'supplier_id' => $this->supplier_id,
            'total' => $this->total,
            'observation' => $this->observation,
        ]);

        // Sincronizar líneas de la OC
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
        $this->purchaseOrder->variants()->sync($syncData);

        // Recalcular cantidad ordenada desde pivot
        $orderedQty = DB::table('variantables')
            ->where('variantable_type', PurchaseOrder::class)
            ->where('variantable_id', $this->purchaseOrder->id)
            ->sum('quantity');
        $this->purchaseOrder->update([
            'ordered_qty_total' => (float) $orderedQty,
        ]);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Orden de compra actualizada exitosamente.',
        ]);

        return redirect()->route('admin.purchases-orders.index');
    }

    public function confirmOrder()
    {
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
        // Si ya existe factura asociada, redirigir a la edición de la compra
        if ($this->hasPurchase && $this->purchaseId) {
            return redirect('/admin/purchases/' . $this->purchaseId . '/edit');
        }

        // Redirigir a la página de creación de compras, pasando el ID de la orden de compra
        return redirect()->route('admin.purchases.create', ['purchase_order_id' => $this->purchaseOrder->id]);
    }

    public function viewPurchase()
    {
        if (! $this->hasPurchase || ! $this->purchaseId) {
            return;
        }

        // Redirigir a la edición de la compra existente (aunque ahora no exista la ruta/componente)
        return redirect('/admin/purchases/' . $this->purchaseId . '/edit');
    }

    //Métodos para el modal de envío de correo
    public function openModal(PurchaseOrder $purchaseOrder)
    {
        $this->form['open'] = true;
        $this->form['document'] = 'Orden de Compra ' . ' ' . $purchaseOrder->serie . ' ' . $purchaseOrder->correlative;
        $this->form['client'] =  $purchaseOrder->supplier->document_number . ' - ' . $purchaseOrder->supplier->name;
        $this->form['email'] = $purchaseOrder->supplier->email;
        $this->form['model'] = $purchaseOrder;
    }

    public function sendEmail()
    {
        $this->validate(
            [
                'form.email' => 'required|email',
            ]
        );

        //Llamar a un mailable
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
        return view('livewire.admin.purchase-order-edit');
    }
}
