<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'identity_id',
        'document_number',
        'name',
        'address',
        'email',
        'phone',
    ];
    //Relaciones
    public function identity()
    {
        return $this->belongsTo(Identity::class);
    }

    public function purchasesOrder()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
