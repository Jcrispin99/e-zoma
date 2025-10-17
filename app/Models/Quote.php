<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Sale;

class Quote extends Model
{
    protected $fillable = [
        'voucher_type',
        'serie',
        'correlative',
        'date',
        'customer_id',
        'total',
        'observation',
        'company_id',
        'status',
    ];
    protected $casts = [
        'date' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function variants()
    {
        return $this->morphToMany(Variant::class, 'variantable')
            ->withPivot('quantity', 'price', 'subtotal')
            ->withTimestamps();
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
