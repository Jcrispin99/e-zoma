<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'journal_id',
        'serie',
        'correlative',
        'supplier_id',
        'total',
        'observation',
        'company_id',
        'status',
        'receiving_status',
        'billing_status',
        'payment_status',
        'confirmed_at',
        'received_at',
        'billed_at',
        'closed_at',
        'cancelled_at',
        'purchases_count',
        'ordered_qty_total',
        'received_qty_total',
        'billed_qty_total',
    ];

    protected $casts = [
        'date' => 'datetime',
        'confirmed_at' => 'datetime',
        'received_at' => 'datetime',
        'billed_at' => 'datetime',
        'closed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function variants()
    {
        return $this->morphToMany(Variant::class, 'variantable')
            ->withPivot('quantity', 'price', 'tax_rate', 'subtotal')
            ->withTimestamps();
    }

    public function purchase()
    {
        // Una orden de compra puede tener una compra (factura) asociada
        return $this->hasOne(Purchase::class, 'purchase_order_id');
    }

    public function getSubtotalAttribute()
    {
        return $this->billing_status === 'complete';
    }
}
