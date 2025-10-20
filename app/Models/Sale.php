<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'serie',
        'correlative',
        'journal_id',
        'date',
        'quote_id',
        'customer_id',
        'warehouse_id',
        'total',
        'observation',
        'company_id',
        'pos_order_id',
        'status',
        'payment_status',
        'original_sale_id',
        'reason_id',
        'original_document_type_code',
        'original_serie',
        'original_correlative',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function originalSale()
    {
        return $this->belongsTo(Sale::class, 'original_sale_id');
    }

    public function reason()
    {
        return $this->belongsTo(Reason::class);
    }

    public function variants()
    {
        return $this->morphToMany(Variant::class, 'variantable')
            ->withPivot('quantity', 'price', 'subtotal')
            ->withTimestamps();
    }
    public function inventories()
    {
        return $this->morphMany(Inventory::class, 'inventoryable');
    }
}
