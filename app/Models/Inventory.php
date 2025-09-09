<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'detail',
        'quantity_in',
        'const_in',
        'total_in',
        'quantity_out',
        'const_out',
        'total_out',
        'quantity_balance',
        'const_balance',
        'total_balance',
        'variant_id',
        'warehouse_id',
        'inventoryable_id',
        'inventoryable_type',
    ];

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inventoryable()
    {
        return $this->morphTo();
    }
}
