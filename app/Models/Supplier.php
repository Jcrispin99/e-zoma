<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{

    use HasFactory;
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
