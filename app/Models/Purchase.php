<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'journal_id',
        'serie',
        'correlative',
        'date',
        'purchase_order_id',
        'supplier_id',
        'warehouse_id',
        'total',
        'observation',
        'company_id',
        'status',
        'payment_status',
        'vendor_bill_number',
        'vendor_bill_date',
    ];

    protected $casts = [
        'date' => 'datetime',
        'vendor_bill_date' => 'date',
    ];

     public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function variants()
    {
        return $this->morphToMany(Variant::class, 'variantable')
            ->withPivot('quantity', 'price', 'tax_rate', 'subtotal')
            ->withTimestamps();
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inventories()
    {
        return $this->morphMany(Inventory::class, 'inventoryable');
    }
}
