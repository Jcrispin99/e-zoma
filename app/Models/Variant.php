<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Variant extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
        'stock'
    ];

    protected $casts = [
        'price' => 'decimal:2'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'attribute_value_variant');
    }

    public function variantables()
    {
        return $this->morphMany(Variantable::class, 'variantable');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
