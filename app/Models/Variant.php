<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Variant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'barcode',
        'stock',
    ];

    protected $casts = [
        'price' => 'decimal:2'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function image(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->images()->count() ? Storage::url($this->images()->first()->path) : asset('storage/images/images.png'),
        );
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

    /**
     * Accesor para obtener el nombre completo de la variante.
     * Combina el nombre del producto con los valores de los atributos.
     *
     * IMPORTANTE: Para un rendimiento óptimo, asegúrate de cargar las relaciones
     * 'product' y 'attributeValues' con Eager Loading (->with(['product', 'attributeValues'])).
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
}
