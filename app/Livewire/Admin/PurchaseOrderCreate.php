<?php

namespace App\Livewire\Admin;

use App\Models\Product;
use Livewire\Component;
use App\Models\PurchaseOrder;

class PurchaseOrderCreate extends Component
{
    public $voucher_type = 1;
    public $serie = 'A';
    public $correlative;

    public $date;
    public $supplier_id;
    public $total = 0;
    public $observation;

    public $product_id;
    public $products = [];

    public function mount()
    {
        $this->correlative = PurchaseOrder::max('correlative') + 1;
    }

    public function addProduct()
    {
        $this->validate([
            'product_id' => 'required|exists:products,id',
        ], [], [
            'product_id' => 'producto',
        ]);

        $existing = collect($this->products)->firstwhere('id', $this->product_id);

        if ($existing) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'El producto ya fue agregado',
                'text' => 'El producto ya se encuentra en la fila',
            ]);
            return;
        }

        $product = Product::find($this->product_id);
        $this->products[] = [
            'id' => $product->id,
            'name' => $product->name,
            'quantity' => 1,
            'price' => 0,
            'subtotal' => 0,
        ];
        $this->reset('product_id');
    }

    public function save()
    {
        $this->validate([
            'voucher_type' => 'required| in:1,2',
            'date' => 'nullable|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'total' => 'required|numeric|min:0',
            'observation' => 'nullable|string|max:255',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|numeric|min:1',
            'products.*.price' => 'required|numeric|min:0',
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'voucher_type' => $this->voucher_type,
            'serie' => $this->serie,
            'correlative' => $this->correlative,
            'date' => $this->date ?? now(),
            'supplier_id' => $this->supplier_id,
            'total' => $this->total,
            'observation' => $this->observation,
        ]);
    }

    public function render()
    {
        return view('livewire.admin.purchase-order-create');
    }
}
