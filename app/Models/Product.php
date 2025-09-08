<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'base_price'
    ];

    protected $casts = [
        'base_price' => 'decimal:2'
    ];

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }
}
