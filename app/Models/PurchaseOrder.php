<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'voucher_type',
        'serie',
        'correlative',
        'supplier_id',
        'total',
        'observation',
        'company_id',
        // nuevos campos
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


    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function variants()
    {
        return $this->morphToMany(Variant::class, 'variantable')
            ->withPivot('quantity', 'price', 'subtotal')
            ->withTimestamps();
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function isBilled(): bool
    {
        return $this->billing_status === 'complete';
    }
}
