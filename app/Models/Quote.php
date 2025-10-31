<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Sale;

class Quote extends Model
{
    protected $fillable = [
        'journal_id',
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
    
    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

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
