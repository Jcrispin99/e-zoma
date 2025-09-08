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

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'attribute_value_variant');
    }

    public function variantables(): MorphMany
    {
        return $this->morphMany(Variantable::class, 'variantable');
    }

    public function purchases()
    {
        return $this->morphedByMany(Purchase::class, 'variantable');
    }

    public function sales()
    {
        return $this->morphedByMany(Sale::class, 'variantable');
    }

    public function quotes()
    {
        return $this->morphedByMany(Quote::class, 'variantable');
    }
}
